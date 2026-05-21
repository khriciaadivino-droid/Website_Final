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

        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $petOfTheMonth = $petProfileRepository->findOneBy(['isPetOfTheMonth' => true]);
        $allPets = $petProfileRepository->findBy([], ['id' => 'DESC']);
        $recentActivityLogs = $activityLogRepository->findRecentLogs(6);
        $recentContactMessages = $contactMessageRepository->findLatest(5);
        $pendingContactMessages = $contactMessageRepository->count(['emailSent' => false]);
        $totalContactMessages = $contactMessageRepository->count([]);

        return $this->render('admin_dashboard/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalStaff' => $totalStaff,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalCompletedIncome' => $totalCompletedIncome,
            'totalStockQuantity' => $totalStockQuantity,
            'recentUsers' => $recentUsers,
            'petOfTheMonth' => $petOfTheMonth,
            'allPets' => $allPets,
            'recentActivityLogs' => $recentActivityLogs,
            'recentContactMessages' => $recentContactMessages,
            'pendingContactMessages' => $pendingContactMessages,
            'totalContactMessages' => $totalContactMessages,
        ]);
    }
}
