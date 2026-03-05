<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/activity-logs')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'app_activity_log', methods: ['GET'])]
    public function index(
        Request $request,
        ActivityLogRepository $activityLogRepository,
        UserRepository $userRepository
    ): Response {
        $filters = [];
        
        // Get filter parameters
        $username = $request->query->get('username');
        $action = $request->query->get('action');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        
        // Build filters array
        if ($username) {
            $filters['username'] = $username;
        }
        
        if ($action) {
            $filters['action'] = $action;
        }
        
        if ($dateFrom) {
            try {
                $filters['dateFrom'] = new \DateTime($dateFrom);
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        }
        
        if ($dateTo) {
            try {
                $filters['dateTo'] = new \DateTime($dateTo);
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        }
        
        // Get filtered logs
        $logs = $activityLogRepository->findWithFilters($filters);
        
        // Get filter options
        $distinctUsernames = $activityLogRepository->findDistinctUsernames();
        $distinctActions = $activityLogRepository->findDistinctActions();
        
        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'usernames' => $distinctUsernames,
            'actions' => $distinctActions,
            'filters' => [
                'username' => $username,
                'action' => $action,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_activity_log_show', methods: ['GET'])]
    public function show(int $id, ActivityLogRepository $activityLogRepository): Response
    {
        $log = $activityLogRepository->find($id);
        
        if (!$log) {
            throw $this->createNotFoundException('Activity log not found');
        }
        
        return $this->render('activity_log/show.html.twig', [
            'log' => $log,
        ]);
    }
}
