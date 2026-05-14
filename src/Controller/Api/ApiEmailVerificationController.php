<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\VerificationToken;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class ApiEmailVerificationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/verify-email', name: 'api_verify_email', methods: ['POST', 'GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query->get('token');

        if (!$token && $request->getContent() !== '') {
            $data = json_decode($request->getContent(), true);
            $token = is_array($data) ? ($data['token'] ?? null) : null;
        }

        if (!$token) {
            return $this->json([
                'success' => false,
                'message' => 'Verification token is required',
            ], 400);
        }

        $user = $this->emailVerificationService->verifyToken((string) $token);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid or expired verification token',
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified(),
            ],
        ], 200);
    }

    #[Route('/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        if ($user->isVerified()) {
            return $this->json([
                'success' => false,
                'message' => 'Email is already verified',
            ], 400);
        }

        $existingTokens = $this->entityManager
            ->getRepository(VerificationToken::class)
            ->findBy(['user' => $user, 'usedAt' => null]);

        foreach ($existingTokens as $existingToken) {
            $this->entityManager->remove($existingToken);
        }

        $token = new VerificationToken();
        $token->setToken($this->emailVerificationService->generateVerificationToken());
        $token->setUser($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $verificationUrl = $this->generateUrl(
            'app_verify_email',
            ['token' => $token->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);

        return $this->json([
            'success' => true,
            'message' => 'Verification email sent successfully',
        ], 200);
    }

    #[Route('/verification-status', name: 'api_verification_status', methods: ['GET'])]
    #[Route('/verfication-status', name: 'api_verfication_status_legacy', methods: ['GET'])]
    public function verificationStatus(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        return $this->json([
            'success' => true,
            'isVerified' => $user->isVerified(),
            'email' => $user->getEmail(),
        ], 200);
    }
}
