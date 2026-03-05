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
final class DashboardController extends AbstractController
{
   #[Route(name: 'app_dashboard_index', methods: ['GET'])]
public function index(
    ProductssRepository $productssRepository,
    OrdersRepository $ordersRepository,
    PetProfileManagementRepository $petProfileRepository,
    UserRepository $userRepository
): Response
{
    // Admin-specific statistics
    $totalUsers = 0;
    $totalAdmins = 0;
    $totalStaff = 0;
    $recentUsers = [];

    if ($this->isGranted('ROLE_ADMIN')) {
        $totalUsers = $userRepository->count([]);
        
        // Count admins and staff by checking roles
        $allUsers = $userRepository->findAll();
        foreach ($allUsers as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $totalAdmins++;
            } elseif (in_array('ROLE_STAFF', $user->getRoles())) {
                $totalStaff++;
            }
        }
        
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);
    }

    // Count total products
    $totalProducts = $productssRepository->count([]);

    // Sum total quantity of all products → total stocks
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

    // Get Best Selling Product (product with highest quantity sold or most popular)
    $bestSellingProduct = $productssRepository->createQueryBuilder('p')
        ->orderBy('p.quantity', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    return $this->render('dashboard/index.html.twig', [
        'totalProducts' => $totalProducts,
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
