<?php

namespace App\Controller;

use App\Repository\ProductssRepository;
use App\Repository\OrdersRepository;
use App\Repository\StocksRepository;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\PaymentsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(
        Request $request,
        ProductssRepository $productsRepository,
        OrdersRepository $ordersRepository,
        StocksRepository $stocksRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        PaymentsRepository $paymentsRepository
    ): Response {
        $query = $request->query->get('q', '');
        
        $results = [
            'query' => $query,
            'products' => [],
            'orders' => [],
            'stocks' => [],
            'users' => [],
            'categories' => [],
            'payments' => [],
        ];

        if (strlen($query) >= 2) {
            // Search products by name, description
            $results['products'] = $productsRepository->createQueryBuilder('p')
                ->leftJoin('p.category', 'c')
                ->where('p.productName LIKE :query OR p.description LIKE :query OR c.name LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(15)
                ->getQuery()
                ->getResult();

            // Search orders by order code, customer name, or status
            $results['orders'] = $ordersRepository->createQueryBuilder('o')
                ->leftJoin('o.user', 'u')
                ->where('o.orderCode LIKE :query OR u.fullName LIKE :query OR u.email LIKE :query OR o.status LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('o.createdAt', 'DESC')
                ->setMaxResults(15)
                ->getQuery()
                ->getResult();

            // Search stocks by product name, batch number, or supplier
            $results['stocks'] = $stocksRepository->createQueryBuilder('s')
                ->leftJoin('s.product', 'p')
                ->where('p.productName LIKE :query OR s.batchNumber LIKE :query OR s.supplier LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(15)
                ->getQuery()
                ->getResult();

            // Search categories
            $results['categories'] = $categoryRepository->createQueryBuilder('c')
                ->where('c.name LIKE :query OR c.description LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            // Search payments by reference number or customer
            $results['payments'] = $paymentsRepository->createQueryBuilder('p')
                ->leftJoin('p.order', 'o')
                ->leftJoin('o.user', 'u')
                ->where('p.referenceNumber LIKE :query OR p.paymentMethod LIKE :query OR u.fullName LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('p.paymentDate', 'DESC')
                ->setMaxResults(15)
                ->getQuery()
                ->getResult();

            // Search users (admin only)
            if ($this->isGranted('ROLE_ADMIN')) {
                $results['users'] = $userRepository->createQueryBuilder('u')
                    ->where('u.fullName LIKE :query OR u.email LIKE :query OR u.address LIKE :query OR u.phoneNumber LIKE :query')
                    ->setParameter('query', '%' . $query . '%')
                    ->setMaxResults(15)
                    ->getQuery()
                    ->getResult();
            }
        }

        return $this->render('search/index.html.twig', [
            'results' => $results,
        ]);
    }
}
