<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\UserRepository;
use App\Repository\ProductssRepository;
use App\Repository\OrdersRepository;
use App\Repository\PetProfileManagementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ProductssRepository $productssRepository,
        OrdersRepository $ordersRepository,
        PetProfileManagementRepository $petProfileRepository,
        ActivityLogRepository $activityLogRepository,
        ContactMessageRepository $contactMessageRepository
    ): Response {
        return $this->render('admin_dashboard/index.html.twig', $this->buildDashboardContext(
            $userRepository,
            $productssRepository,
            $ordersRepository,
            $petProfileRepository,
            $activityLogRepository,
            $contactMessageRepository,
        ));
    }

    #[Route('/admin/dashboard/live-fragment', name: 'app_admin_dashboard_live_fragment', methods: ['GET'])]
    public function liveFragment(
        UserRepository $userRepository,
        ProductssRepository $productssRepository,
        OrdersRepository $ordersRepository,
        PetProfileManagementRepository $petProfileRepository,
        ActivityLogRepository $activityLogRepository,
        ContactMessageRepository $contactMessageRepository
    ): Response {
        return $this->render('admin_dashboard/_content.html.twig', $this->buildDashboardContext(
            $userRepository,
            $productssRepository,
            $ordersRepository,
            $petProfileRepository,
            $activityLogRepository,
            $contactMessageRepository,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardContext(
        UserRepository $userRepository,
        ProductssRepository $productssRepository,
        OrdersRepository $ordersRepository,
        PetProfileManagementRepository $petProfileRepository,
        ActivityLogRepository $activityLogRepository,
        ContactMessageRepository $contactMessageRepository,
    ): array {
        $totalUsers = $userRepository->count([]);
        $totalAdmins = $userRepository->countAdminUsers();
        $totalStaff = $userRepository->countStaffUsers();

        $totalProducts = $productssRepository->count([]);
        $totalOrders = $ordersRepository->count([]);
        $totalCompletedIncome = $ordersRepository->getCompletedIncomeTotal();
        $totalStockQuantity = (int) $productssRepository->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.quantity), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalStaff' => $totalStaff,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalCompletedIncome' => $totalCompletedIncome,
            'totalStockQuantity' => $totalStockQuantity,
            'recentUsers' => $userRepository->findBy([], ['createdAt' => 'DESC'], 5),
            'petOfTheMonth' => $petProfileRepository->findOneBy(['isPetOfTheMonth' => true]),
            'allPets' => $petProfileRepository->findBy([], ['id' => 'DESC']),
            'recentActivityLogs' => $activityLogRepository->findRecentLogs(6),
            'recentContactMessages' => $contactMessageRepository->findLatest(5),
            'pendingContactMessages' => $contactMessageRepository->count(['emailSent' => false]),
            'totalContactMessages' => $contactMessageRepository->count([]),
        ];
    }
}
