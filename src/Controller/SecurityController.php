<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly ?string $brevoApiKey = null,
        private readonly ?string $brevoListId = null,
        private readonly string $contactRecipientEmail = 'khriciaazucena@gmail.com',
        private readonly string $contactSenderEmail = 'a45e93001@smtp-brevo.com',
    ) {}

    #[Route(path: '/welcome', name: 'app_welcome')]
    public function landing(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        return $this->render('landing/index.html.twig');
    }

    #[Route(path: '/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('about/index.html.twig');
    }

    #[Route(path: '/our-story', name: 'app_story')]
    public function story(): Response
    {
        return $this->render('story/index.html.twig');
    }

    #[Route(path: '/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('contact/index.html.twig');
    }

    #[Route(path: '/pet-details/{slug}', name: 'app_pet_details')]
    public function petDetails(string $slug): Response
    {
        $pets = [
            'human-you-need-me' => [
                'name' => 'Snowy',
                'type' => 'Cat',
                'title' => 'HUMAN! YOU NEED ME',
                'image' => 'gallery8.jpg',
                'description' => 'Snowy is a calm and affectionate cat who loves gentle company and playtime with soft toys.',
                'petOfMonthDate' => 'April 2026',
            ],
            'adopt-a-friend' => [
                'name' => 'Buddy Squad',
                'type' => 'Puppies',
                'title' => 'ADOPT A FRIEND',
                'image' => 'gallery6.jpg',
                'description' => 'A friendly group of rescue puppies that quickly bond with families and thrive in active homes.',
                'petOfMonthDate' => 'March 2026',
            ],
            'true-best-friend' => [
                'name' => 'Milo',
                'type' => 'Cat',
                'title' => 'TRUE BEST FRIEND',
                'image' => 'gallery7.jpg',
                'description' => 'Milo is playful, curious, and very social, making him a perfect companion for first-time pet parents.',
                'petOfMonthDate' => 'February 2026',
            ],
            'partner-of-life' => [
                'name' => 'Coco',
                'type' => 'Dog',
                'title' => 'PARTNER OF LIFE',
                'image' => 'gallery10.jpg',
                'description' => 'Coco is loyal and energetic, ideal for owners who enjoy outdoor walks and daily routines.',
                'petOfMonthDate' => 'January 2026',
            ],
            'puppy-is-the-answer' => [
                'name' => 'Peanut',
                'type' => 'Dachshund',
                'title' => 'PUPPY IS THE ANSWER',
                'image' => 'gallery11.jpg',
                'description' => 'Peanut is cheerful and brave, bringing positive energy to every home and loving human interaction.',
                'petOfMonthDate' => 'December 2025',
            ],
        ];

        if (!isset($pets[$slug])) {
            throw $this->createNotFoundException('Pet details not found.');
        }

        return $this->render('pet_details/index.html.twig', [
            'pet' => $pets[$slug],
        ]);
    }

    #[Route(path: '/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function contactSubmit(Request $request): Response
    {
        $name = trim((string) $request->request->get('name', ''));
        $email = trim((string) $request->request->get('email', ''));
        $phone = trim((string) $request->request->get('phone', ''));
        $subject = trim((string) $request->request->get('subject', ''));
        $message = trim((string) $request->request->get('message', ''));

        // Validate required fields
        if (!$name || !$email || !$subject || !$message) {
            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email address'], Response::HTTP_BAD_REQUEST);
        }

        $recipientEmail = filter_var($this->contactRecipientEmail, FILTER_VALIDATE_EMAIL)
            ? $this->contactRecipientEmail
            : 'khriciaazucena@gmail.com';

        $senderEmail = filter_var($this->contactSenderEmail, FILTER_VALIDATE_EMAIL)
            ? $this->contactSenderEmail
            : $recipientEmail;

        $emailSent = false;
        $deliveryError = null;

        try {
            $this->sendContactNotification(
                $senderEmail,
                $recipientEmail,
                $name,
                $email,
                $phone,
                $subject,
                $message
            );
            $emailSent = true;
        } catch (\Throwable $exception) {
            $deliveryError = $exception->getMessage();
            $this->logger->error('Contact form email send failed', [
                'error' => $deliveryError,
                'recipient' => $recipientEmail,
                'sender' => $senderEmail,
                'reply_to' => $email,
            ]);
        }

        $contactMessage = (new ContactMessage())
            ->setName($name)
            ->setEmail($email)
            ->setPhone($phone !== '' ? $phone : null)
            ->setSubject($subject)
            ->setMessage($message)
            ->setEmailSent($emailSent)
            ->setDeliveryStatus($emailSent ? 'sent' : 'saved_only')
            ->setDeliveryError($deliveryError);

        try {
            $this->entityManager->persist($contactMessage);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->logger->error('Contact form persistence failed', [
                'error' => $exception->getMessage(),
                'email' => $email,
                'subject' => $subject,
                'email_sent' => $emailSent,
            ]);

            return new JsonResponse([
                'error' => 'Unable to save your message right now. Please try again later.',
            ], Response::HTTP_BAD_GATEWAY);
        }

        if (!$emailSent) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Your message was received and saved successfully. Our team can view it in the admin inbox even if email delivery is delayed.',
            ], Response::HTTP_ACCEPTED);
        }

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse([
                'success' => true,
                'message' => 'Thank you for contacting us! Your message was submitted successfully via Brevo.',
            ]);
        }

        return $this->redirectToRoute('app_contact');
    }

    private function sendContactNotification(
        string $senderEmail,
        string $recipientEmail,
        string $name,
        string $replyToEmail,
        string $phone,
        string $subject,
        string $message
    ): void {
        $textBody =
            "New contact form submission\n\n" .
            "Name: {$name}\n" .
            "Email: {$replyToEmail}\n" .
            "Phone: " . ($phone ?: 'N/A') . "\n" .
            "Subject: {$subject}\n\n" .
            "Message:\n{$message}\n";

        if (is_string($this->brevoApiKey) && trim($this->brevoApiKey) !== '') {
            try {
                $response = $this->httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                    'headers' => [
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                        'api-key' => trim($this->brevoApiKey),
                    ],
                    'json' => [
                        'sender' => [
                            'email' => $senderEmail,
                            'name' => 'PawStuff Contact Form',
                        ],
                        'to' => [
                            [
                                'email' => $recipientEmail,
                            ],
                        ],
                        'replyTo' => [
                            'email' => $replyToEmail,
                            'name' => $name,
                        ],
                        'subject' => '[PawStuff Contact] ' . $subject,
                        'textContent' => $textBody,
                    ],
                    'timeout' => 20,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode < 400) {
                    return;
                }

                $errorBody = $response->getContent(false);
                $this->logger->warning('Brevo API send failed, falling back to SMTP', [
                    'status_code' => $statusCode,
                    'error' => $errorBody,
                ]);
            } catch (\Throwable $exception) {
                $this->logger->warning('Brevo API transport error, falling back to SMTP', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        // Fallback to SMTP mailer only when API key is unavailable.
        $emailMessage = (new Email())
            ->from($senderEmail)
            ->to($recipientEmail)
            ->replyTo($replyToEmail)
            ->subject('[PawStuff Contact] ' . $subject)
            ->text($textBody);

        $this->mailer->send($emailMessage);
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $googleClientId = trim((string) ($_ENV['GOOGLE_CLIENT_ID'] ?? $_SERVER['GOOGLE_CLIENT_ID'] ?? $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? $_SERVER['GOOGLE_OAUTH_CLIENT_ID'] ?? ''));
        $googleClientSecret = trim((string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? $_SERVER['GOOGLE_CLIENT_SECRET'] ?? $_ENV['GOOGLE_OAUTH_CLIENT_SECRET'] ?? $_SERVER['GOOGLE_OAUTH_CLIENT_SECRET'] ?? ''));
        $googleOAuthConfigured = $googleClientId !== '' && $googleClientSecret !== '';

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'google_oauth_configured' => $googleOAuthConfigured,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
