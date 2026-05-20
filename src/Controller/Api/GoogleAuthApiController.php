<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api')]
class GoogleAuthApiController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly ActivityLogService $activityLogService,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    #[Route('/google/config', name: 'api_google_auth_config', methods: ['GET'])]
    public function config(): JsonResponse
    {
        $clientId = $this->getPrimaryGoogleClientId();

        return $this->json([
            'success' => true,
            'enabled' => $clientId !== null,
            'clientId' => $clientId,
        ]);
    }

    #[Route('/google-login', name: 'api_google_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $configuredClientIds = $this->getConfiguredGoogleClientIds();

        if ($configuredClientIds === []) {
            return $this->json([
                'success' => false,
                'message' => 'Google Sign-In is not configured on the server.',
            ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        $payload = json_decode($request->getContent(), true);
        $idToken = trim((string) ($payload['idToken'] ?? ''));

        if ($idToken === '') {
            return $this->json([
                'success' => false,
                'message' => 'Google ID token is required.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $googleUserData = $this->fetchGoogleUserData($idToken);
        if ($googleUserData === null) {
            return $this->json([
                'success' => false,
                'message' => 'Unable to verify the Google sign-in token.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $audience = trim((string) ($googleUserData['aud'] ?? ''));
        if ($audience === '' || !in_array($audience, $configuredClientIds, true)) {
            return $this->json([
                'success' => false,
                'message' => 'Google sign-in is not allowed for this mobile app configuration.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $email = trim((string) ($googleUserData['email'] ?? ''));
        $emailVerified = filter_var($googleUserData['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || !$emailVerified) {
            return $this->json([
                'success' => false,
                'message' => 'Google account must provide a verified email address.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $displayName = trim((string) ($googleUserData['name'] ?? ''));
        if ($displayName === '') {
            $displayName = strstr($email, '@', true) ?: $email;
        }

        $googleId = trim((string) ($googleUserData['sub'] ?? '')) ?: null;
        $user = $this->userRepository->findOneBy(['email' => $email]) ?? new User();

        if ($user->getId() === null) {
            $user->setEmail($email);
            $user->setFullName($displayName);
            $user->setRoles(['ROLE_STAFF']);
            $user->setStatus('active');
            $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
        } else {
            if (trim((string) $user->getFullName()) === '') {
                $user->setFullName($displayName);
            }

            if (!$user->isAdmin() && !$user->isStaff()) {
                $user->setRoles(['ROLE_STAFF']);
            }

            if (!$user->isActive()) {
                $user->setStatus('active');
            }
        }

        $user->setGoogleId($googleId);

        if (!$user->isVerified()) {
            $user->setVerifiedAt(new \DateTimeImmutable());
        }

        $user->setLastLoginAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->activityLogService->logLogin($user);

        return $this->json([
            'success' => true,
            'token' => $this->jwtTokenManager->create($user),
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUserIdentifier(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'verified' => $user->isVerified(),
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function getConfiguredGoogleClientIds(): array
    {
        $candidateValues = [
            $_ENV['GOOGLE_CLIENT_ID'] ?? $_SERVER['GOOGLE_CLIENT_ID'] ?? null,
            $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? $_SERVER['GOOGLE_OAUTH_CLIENT_ID'] ?? null,
            $_ENV['GOOGLE_ANDROID_CLIENT_ID'] ?? $_SERVER['GOOGLE_ANDROID_CLIENT_ID'] ?? null,
            $_ENV['GOOGLE_IOS_CLIENT_ID'] ?? $_SERVER['GOOGLE_IOS_CLIENT_ID'] ?? null,
        ];

        $clientIds = [];
        foreach ($candidateValues as $value) {
            $normalized = trim((string) $value);
            if ($normalized !== '') {
                $clientIds[] = $normalized;
            }
        }

        return array_values(array_unique($clientIds));
    }

    private function getPrimaryGoogleClientId(): ?string
    {
        // Prefer the mobile-specific client IDs so the /api/google/config endpoint
        // returns the ID that matches google-services.json / GoogleService-Info.plist.
        $preferredKeys = [
            'GOOGLE_ANDROID_CLIENT_ID',
            'GOOGLE_IOS_CLIENT_ID',
        ];

        foreach ($preferredKeys as $key) {
            $value = trim((string) ($_ENV[$key] ?? $_SERVER[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $clientIds = $this->getConfiguredGoogleClientIds();

        return $clientIds[0] ?? null;
    }

    private function fetchGoogleUserData(string $idToken): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
                'query' => [
                    'id_token' => $idToken,
                ],
            ]);
        } catch (TransportExceptionInterface) {
            return null;
        }

        if ($response->getStatusCode() !== JsonResponse::HTTP_OK) {
            return null;
        }

        try {
            $data = $response->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }
}
