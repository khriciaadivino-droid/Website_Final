<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class OAuthController extends AbstractController
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/connect/check-google', name: 'app_oauth2_google_check')]
    public function connectCheckGoogle(): Response
    {
        return $this->clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    #[Route('/oauth2/callback/google', name: 'app_oauth2_callback')]
    public function connectGoogleCheck(Request $request): Response
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $client->getAccessToken();

        // Normalize provider data to a plain array for compatibility.
        $googleUserData = $client->fetchUserFromToken($accessToken)->toArray();
        $email = $googleUserData['email'] ?? null;
        $name = $googleUserData['name'] ?? 'Google User';
        $googleId = $googleUserData['sub'] ?? null;

        if (!$email) {
            $this->addFlash('error', 'Google account does not expose an email address.');
            return $this->redirectToRoute('app_login');
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // Create new user with ROLE_STAFF (as per requirement) and auto-verified
            $user = new User();
            $user->setEmail($email);
            $user->setFullName($name);
            $user->setRoles(['ROLE_STAFF']);
            $user->setGoogleId($googleId);
            // Auto-verify Google users
            $user->setVerifiedAt(new \DateTime());
            // Generate random password since Google auth is passwordless
            $user->setPassword(hash('sha256', random_bytes(16)));
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } else {
            // Update existing user with Google ID and auto-verify if not already verified
            $user->setGoogleId($googleId);
            if (!$user->isVerified()) {
                $user->setVerifiedAt(new \DateTime());
            }
            $this->entityManager->flush();
        }

        // Update last login
        $user->setLastLoginAt(new \DateTime());
        $this->entityManager->flush();

        // Log the user in
        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        $this->container->get('security.token_storage')->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        $this->container->get('event_dispatcher')->dispatch($event);

        // Redirect to dashboard
        return $this->redirectToRoute('app_dashboard_index');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This will be handled by Symfony security
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
