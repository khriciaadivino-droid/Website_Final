<?php

namespace App\Controller;

use App\Service\LiveRevisionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class LiveRevisionController extends AbstractController
{
    #[Route('/live/revision', name: 'app_live_revision_snapshot', methods: ['GET'])]
    #[Route('/api/live/revision', name: 'api_live_revision_snapshot', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function snapshot(LiveRevisionService $liveRevisionService): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'Live revisions fetched successfully',
            'revisions' => $liveRevisionService->snapshot(),
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/live/revision/{domain}', name: 'app_live_revision_domain', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function domain(string $domain, LiveRevisionService $liveRevisionService): JsonResponse
    {
        if (!in_array($domain, LiveRevisionService::DOMAINS, true)) {
            return $this->json([
                'success' => false,
                'message' => 'Unknown revision domain',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'domain' => $domain,
            'revision' => $liveRevisionService->current($domain),
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }
}
