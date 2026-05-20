<?php

namespace App\Controller;

use App\Repository\ProductssRepository;
use App\Repository\OrdersRepository;
use App\Repository\PetProfileManagementRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    #[Route(name: 'app_dashboard_index', methods: ['GET'])]
    public function index(
        ProductssRepository $productssRepository,
        OrdersRepository $ordersRepository,
        PetProfileManagementRepository $petProfileRepository,
        UserRepository $userRepository
    ): Response {
        // Admin-specific statistics
        $totalUsers = 0;
        $totalAdmins = 0;
        $totalStaff = 0;
        $recentUsers = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            $totalUsers = $userRepository->count([]);
            $totalAdmins = $userRepository->countAdminUsers();
            $totalStaff = $userRepository->countStaffUsers();
            $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        }

        // Count total products
        $totalProducts = $productssRepository->count([]);
        $products = $productssRepository->findBy([], ['id' => 'DESC'], 8);

        // Sum total quantity of all products -> total stocks
        $totalStocks = $productssRepository->createQueryBuilder('p')
            ->select('SUM(p.quantity)')
            ->getQuery()
            ->getSingleScalarResult();

        // Count total orders
        $totalOrders = $ordersRepository->count([]);

        // Count total pet profiles
        $totalPetProfiles = $petProfileRepository->count([]);

        // Get Pet of the Month
        $petOfTheMonth = $petProfileRepository->findOneBy(['isPetOfTheMonth' => true]);

        // Get Best Selling Product based on actual ordered quantity
        $bestSellingProduct = $ordersRepository->findBestSellingProduct();

        return $this->render('dashboard/index.html.twig', [
            'totalProducts' => $totalProducts,
            'products' => $products,
            'totalStocks' => $totalStocks ?? 0,
            'totalOrders' => $totalOrders,
            'totalPetProfiles' => $totalPetProfiles,
            'petOfTheMonth' => $petOfTheMonth,
            'bestSellingProduct' => $bestSellingProduct,
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalStaff' => $totalStaff,
            'recentUsers' => $recentUsers,
        ]);
    }
}
