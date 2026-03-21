<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $brevoApiKey = null,
        private readonly ?string $brevoListId = null,
    ) {
    }

    #[Route(path: '/', name: 'app_landing')]
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

    #[Route(path: '/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('contact/index.html.twig');
    }

    #[Route(path: '/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function contactSubmit(Request $request): Response
    {
        // Get form data
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone', '');
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');

        // Validate required fields
        if (!$name || !$email || !$subject || !$message) {
            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email address'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->brevoApiKey) {
            return new JsonResponse(
                ['error' => 'Contact integration is not configured yet. Please set BREVO_API_KEY.'],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        try {
            $payload = [
                'email' => $email,
                'attributes' => [
                    'FIRSTNAME' => $name,
                    'SMS' => $phone ?: null,
                    'SUBJECT' => $subject,
                    'MESSAGE' => $message,
                ],
                'updateEnabled' => true,
            ];

            if ($this->brevoListId && ctype_digit($this->brevoListId)) {
                $payload['listIds'] = [(int) $this->brevoListId];
            }

            $response = $this->httpClient->request('POST', 'https://api.brevo.com/v3/contacts', [
                'headers' => [
                    'api-key' => $this->brevoApiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                return new JsonResponse(['error' => 'Unable to submit to Brevo right now.'], Response::HTTP_BAD_GATEWAY);
            }
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'Unable to submit to Brevo right now.'], Response::HTTP_BAD_GATEWAY);
        }
        
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse(['success' => true, 'message' => 'Thank you for contacting us! Your message was submitted via Brevo.']);
        }

        return $this->redirectToRoute('app_contact');
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
