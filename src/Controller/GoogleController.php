<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        if (!$this->isGoogleOauthConfigured()) {
            $this->addFlash('error', 'Google sign-in is not configured yet. Please set the Google OAuth client credentials in .env.local.');

            return $this->redirectToRoute('app_login');
        }

        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(): RedirectResponse
    {
        $message = $this->isGoogleOauthConfigured()
            ? 'Google sign-in could not be completed. Please try again.'
            : 'Google sign-in is not configured yet. Please set the Google OAuth client credentials in .env.local.';

        $this->addFlash('error', $message);

        return $this->redirectToRoute('app_login');
    }

    private function isGoogleOauthConfigured(): bool
    {
        $clientId = trim((string) ($_ENV['GOOGLE_CLIENT_ID'] ?? $_SERVER['GOOGLE_CLIENT_ID'] ?? $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? $_SERVER['GOOGLE_OAUTH_CLIENT_ID'] ?? ''));
        $clientSecret = trim((string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? $_SERVER['GOOGLE_CLIENT_SECRET'] ?? $_ENV['GOOGLE_OAUTH_CLIENT_SECRET'] ?? $_SERVER['GOOGLE_OAUTH_CLIENT_SECRET'] ?? ''));

        return $clientId !== '' && $clientSecret !== '';
    }
}
