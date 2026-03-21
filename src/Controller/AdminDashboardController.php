<?php

namespace App\Controller;

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
        PetProfileManagementRepository $petProfileRepository
    ): Response {
        $totalUsers = $userRepository->count([]);
        $totalAdmins = $userRepository->countAdminUsers();
        $totalStaff = $userRepository->countStaffUsers();
        
        $totalProducts = $productssRepository->count([]);
        $totalOrders = $ordersRepository->count([]);
        
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $petOfTheMonth = $petProfileRepository->findOneBy(['isPetOfTheMonth' => true]);

        return $this->render('admin_dashboard/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalStaff' => $totalStaff,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'recentUsers' => $recentUsers,
            'petOfTheMonth' => $petOfTheMonth,
        ]);
    }
}
