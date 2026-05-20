<?php

namespace App\Controller\Api;

use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Handles PayMongo payment intent / source creation and webhook verification.
 *
 * Supported methods: gcash, maya (paymaya), card.
 * Cash orders skip this controller entirely — they are handled by the order API.
 *
 * Prerequisites:
 *   - Set PAYMONGO_SECRET_KEY in .env.local
 *   - Set PAYMONGO_WEBHOOK_SECRET in .env.local (from PayMongo dashboard webhook page)
 *   - Set APP_URL to the public base URL of this server
 *
 * PayMongo docs: https://developers.paymongo.com/
 */
#[Route('/api/payment', name: 'api_payment_')]
class PaymentController extends AbstractController
{
    private const PAYMONGO_BASE = 'https://api.paymongo.com/v1';

    public function __construct(private readonly HttpClientInterface $httpClient) {}

    // ──────────────────────────────────────────────────────────────────────
    //  POST /api/payment/create
    //  Called by the mobile app after orders are persisted in the DB.
    //  Returns { type: 'redirect', checkout_url } or { type: 'success' }.
    // ──────────────────────────────────────────────────────────────────────
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, OrdersRepository $ordersRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $paymentMethod = strtolower(trim((string) ($data['payment_method'] ?? '')));
        $totalAmount   = (float) ($data['total_amount'] ?? 0);
        $amountCents   = (int) round($totalAmount * 100);
        $description   = trim((string) ($data['description'] ?? 'Divino Order Payment'));
        $customerName  = trim((string) ($data['customer_name'] ?? 'Customer'));
        $customerEmail = trim((string) ($data['customer_email'] ?? ''));
        $customerPhone = trim((string) ($data['customer_phone'] ?? ''));
        $orderIds      = array_map('intval', (array) ($data['order_ids'] ?? []));

        if ($amountCents < 10000) {
            // PayMongo minimum is PHP 100.00
            return $this->json(['success' => false, 'message' => 'Minimum payable amount is ₱100.00'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($paymentMethod, ['gcash', 'maya', 'card'], true)) {
            return $this->json(['success' => false, 'message' => 'Unsupported payment method. Use gcash, maya, or card.'], Response::HTTP_BAD_REQUEST);
        }

        $secretKey   = $_ENV['PAYMONGO_SECRET_KEY'] ?? '';
        $appUrl      = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');

        if (empty($secretKey) || str_contains($secretKey, 'your_secret_key_here')) {
            return $this->json(['success' => false, 'message' => 'Payment gateway is not configured. Set PAYMONGO_SECRET_KEY in .env.local.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            if ($paymentMethod === 'gcash' || $paymentMethod === 'maya') {
                $sourceType = $paymentMethod === 'maya' ? 'paymaya' : 'gcash';
                $sourceData = $this->createSource(
                    $secretKey,
                    $sourceType,
                    $amountCents,
                    $description,
                    $customerName,
                    $customerEmail,
                    $customerPhone,
                    $appUrl
                );

                $checkoutUrl = $sourceData['redirect']['checkout_url'] ?? null;
                $sourceId    = $sourceData['id'] ?? null;

                if (!$checkoutUrl) {
                    return $this->json(['success' => false, 'message' => 'Failed to get checkout URL from payment provider.'], Response::HTTP_BAD_GATEWAY);
                }

                // Tag orders with payment intent ID for webhook reconciliation
                $this->tagOrders($ordersRepository, $em, $orderIds, $sourceId, 'pending');

                return $this->json([
                    'success'      => true,
                    'type'         => 'redirect',
                    'checkout_url' => $checkoutUrl,
                    'source_id'    => $sourceId,
                ]);
            }

            // ── Card payment ──────────────────────────────────────────────
            $cardNumber = preg_replace('/\s+/', '', (string) ($data['card_number'] ?? ''));
            $cardHolder = trim((string) ($data['card_holder'] ?? $customerName));
            $cvc        = trim((string) ($data['cvc'] ?? ''));

            // Parse MM / YY expiry from frontend format
            $expiryRaw = preg_replace('/\s/', '', (string) ($data['exp_month_year'] ?? ''));
            $expiryParts = explode('/', $expiryRaw);
            $expMonth    = (int) ($expiryParts[0] ?? 0);
            $expYear     = isset($expiryParts[1]) ? (int) ('20' . ltrim($expiryParts[1], '0')) : 0;

            if (!$cardNumber || !$cvc || !$expMonth || !$expYear) {
                return $this->json(['success' => false, 'message' => 'Card details are incomplete.'], Response::HTTP_BAD_REQUEST);
            }

            // 1. Create Payment Method
            $pm = $this->createCardPaymentMethod(
                $secretKey,
                $cardNumber,
                $expMonth,
                $expYear,
                $cvc,
                $cardHolder,
                $customerEmail,
                $customerPhone
            );

            // 2. Create Payment Intent
            $intent = $this->createPaymentIntent($secretKey, $amountCents, $description);

            // 3. Attach Payment Method → Intent
            $attached = $this->attachPaymentMethod(
                $secretKey,
                $intent['id'],
                $pm['id'],
                $appUrl . '/payment-success'
            );

            $status = $attached['status'] ?? '';

            if ($status === 'succeeded') {
                // Card charged immediately (no 3DS required)
                $this->tagOrders($ordersRepository, $em, $orderIds, $intent['id'], 'paid', 'Processing');

                return $this->json(['success' => true, 'type' => 'success', 'status' => 'paid']);
            }

            if ($status === 'awaiting_next_action') {
                // 3D-Secure challenge required
                $redirectUrl = $attached['next_action']['redirect']['url'] ?? null;

                if (!$redirectUrl) {
                    return $this->json(['success' => false, 'message' => '3DS redirect URL missing from payment provider.'], Response::HTTP_BAD_GATEWAY);
                }

                $this->tagOrders($ordersRepository, $em, $orderIds, $intent['id'], 'pending');

                return $this->json([
                    'success'      => true,
                    'type'         => 'redirect',
                    'checkout_url' => $redirectUrl,
                    'intent_id'    => $intent['id'],
                ]);
            }

            return $this->json(['success' => false, 'message' => 'Payment was not completed. Status: ' . $status], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Payment provider error: ' . $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST /api/payment/webhook
    //  Registered in the PayMongo dashboard. Updates order status when paid.
    //  Verification: HMAC-SHA256 of "timestamp.rawBody" against webhook secret.
    // ──────────────────────────────────────────────────────────────────────
    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(Request $request, OrdersRepository $ordersRepository, EntityManagerInterface $em): Response
    {
        $rawBody       = $request->getContent();
        $sigHeader     = $request->headers->get('paymongo-signature', '');
        $webhookSecret = $_ENV['PAYMONGO_WEBHOOK_SECRET'] ?? '';

        // Verify webhook signature if secret is configured
        if ($webhookSecret && $sigHeader) {
            $parts = [];
            foreach (explode(',', $sigHeader) as $part) {
                $kv = explode('=', $part, 2);
                if (count($kv) === 2) {
                    $parts[$kv[0]] = $kv[1];
                }
            }

            $timestamp = $parts['t'] ?? '';
            // PayMongo sends 'te' for test, 'li' for live
            $receivedSig = $parts['te'] ?? ($parts['li'] ?? '');
            $computed    = hash_hmac('sha256', $timestamp . '.' . $rawBody, $webhookSecret);

            if (!hash_equals($computed, $receivedSig)) {
                return new Response('Invalid signature', Response::HTTP_UNAUTHORIZED);
            }
        }

        $event     = json_decode($rawBody, true);
        $eventType = $event['data']['attributes']['type'] ?? '';

        if ($eventType === 'payment.paid') {
            $paymentData     = $event['data']['attributes']['data']['attributes'] ?? [];
            $paymentIntentId = $paymentData['payment_intent_id'] ?? null;

            if ($paymentIntentId) {
                $orders = $ordersRepository->findBy(['paymentIntentId' => $paymentIntentId]);
                foreach ($orders as $order) {
                    $order->setPaymentStatus('paid');
                    if ($order->getStatus() === 'Pending') {
                        $order->setStatus('Processing');
                    }
                }
                $em->flush();
            }
        }

        return new Response('OK', Response::HTTP_OK);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function authHeader(string $secretKey): string
    {
        return 'Basic ' . base64_encode($secretKey . ':');
    }

    private function tagOrders(
        OrdersRepository $repo,
        EntityManagerInterface $em,
        array $orderIds,
        ?string $intentId,
        string $paymentStatus,
        ?string $orderStatus = null
    ): void {
        if (empty($orderIds)) {
            return;
        }
        foreach ($orderIds as $id) {
            $order = $repo->find($id);
            if ($order) {
                $order->setPaymentIntentId($intentId);
                $order->setPaymentStatus($paymentStatus);
                if ($orderStatus !== null) {
                    $order->setStatus($orderStatus);
                }
            }
        }
        $em->flush();
    }

    private function createSource(
        string $secretKey,
        string $type,
        int $amountCents,
        string $description,
        string $name,
        string $email,
        string $phone,
        string $appUrl
    ): array {
        $billing = array_filter(['name' => $name, 'email' => $email ?: null, 'phone' => $phone ?: null]);

        $response = $this->httpClient->request('POST', self::PAYMONGO_BASE . '/sources', [
            'headers' => [
                'Authorization' => $this->authHeader($secretKey),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'data' => [
                    'attributes' => [
                        'amount'      => $amountCents,
                        'currency'    => 'PHP',
                        'type'        => $type,
                        'description' => $description,
                        'redirect'    => [
                            'success' => $appUrl . '/payment-success',
                            'failed'  => $appUrl . '/payment-failed',
                        ],
                        'billing' => $billing,
                    ],
                ],
            ],
        ]);

        $body = $response->toArray(false);

        if ($response->getStatusCode() >= 400) {
            $errors = $body['errors'][0]['detail'] ?? json_encode($body);
            throw new \RuntimeException('PayMongo source error: ' . $errors);
        }

        return ['id' => $body['data']['id'], ...$body['data']['attributes']];
    }

    private function createCardPaymentMethod(
        string $secretKey,
        string $cardNumber,
        int $expMonth,
        int $expYear,
        string $cvc,
        string $name,
        string $email,
        string $phone
    ): array {
        $billing = array_filter(['name' => $name, 'email' => $email ?: null, 'phone' => $phone ?: null]);

        $response = $this->httpClient->request('POST', self::PAYMONGO_BASE . '/payment_methods', [
            'headers' => [
                'Authorization' => $this->authHeader($secretKey),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'data' => [
                    'attributes' => [
                        'type'    => 'card',
                        'details' => [
                            'card_number' => $cardNumber,
                            'exp_month'   => $expMonth,
                            'exp_year'    => $expYear,
                            'cvc'         => $cvc,
                        ],
                        'billing' => $billing,
                    ],
                ],
            ],
        ]);

        $body = $response->toArray(false);

        if ($response->getStatusCode() >= 400) {
            $errors = $body['errors'][0]['detail'] ?? json_encode($body);
            throw new \RuntimeException('PayMongo payment_method error: ' . $errors);
        }

        return ['id' => $body['data']['id'], ...$body['data']['attributes']];
    }

    private function createPaymentIntent(string $secretKey, int $amountCents, string $description): array
    {
        $response = $this->httpClient->request('POST', self::PAYMONGO_BASE . '/payment_intents', [
            'headers' => [
                'Authorization' => $this->authHeader($secretKey),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'data' => [
                    'attributes' => [
                        'amount'                 => $amountCents,
                        'payment_method_allowed' => ['card'],
                        'payment_method_options' => [
                            'card' => ['request_three_d_secure' => 'any'],
                        ],
                        'currency'    => 'PHP',
                        'description' => $description,
                    ],
                ],
            ],
        ]);

        $body = $response->toArray(false);

        if ($response->getStatusCode() >= 400) {
            $errors = $body['errors'][0]['detail'] ?? json_encode($body);
            throw new \RuntimeException('PayMongo payment_intent error: ' . $errors);
        }

        return ['id' => $body['data']['id'], ...$body['data']['attributes']];
    }

    private function attachPaymentMethod(
        string $secretKey,
        string $intentId,
        string $paymentMethodId,
        string $returnUrl
    ): array {
        $response = $this->httpClient->request(
            'POST',
            self::PAYMONGO_BASE . "/payment_intents/{$intentId}/attach",
            [
                'headers' => [
                    'Authorization' => $this->authHeader($secretKey),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'data' => [
                        'attributes' => [
                            'payment_method' => $paymentMethodId,
                            'return_url'     => $returnUrl,
                        ],
                    ],
                ],
            ]
        );

        $body = $response->toArray(false);

        if ($response->getStatusCode() >= 400) {
            $errors = $body['errors'][0]['detail'] ?? json_encode($body);
            throw new \RuntimeException('PayMongo attach error: ' . $errors);
        }

        return $body['data']['attributes'] ?? [];
    }
}
