<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ActivityLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiLogoutController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService,
    ) {}

    /**
     * Logout API endpoint - Records logout in activity log
     */
    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Log the logout action
        try {
            $this->activityLogService->logLogout($user);
        } catch (\Exception $e) {
            // Log the error but still return success to the client
            error_log('Failed to log logout for user ' . $user->getId() . ': ' . $e->getMessage());
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Logged out successfully',
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
        ], Response::HTTP_OK);
    }
}
