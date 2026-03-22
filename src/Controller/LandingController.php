<?php

namespace App\Controller;

use App\Repository\ProductssRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LandingController extends AbstractController
{
    #[Route('/', name: 'app_landing')]
    public function index(ProductssRepository $productssRepository, CategoryRepository $categoryRepository): Response
    {
        $products = $productssRepository->findAll();
        $categories = $categoryRepository->findAll();
        return $this->render('landing/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
