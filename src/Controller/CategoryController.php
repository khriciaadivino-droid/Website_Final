<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\Category1Type;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category')]
final class CategoryController extends AbstractController
{
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        $category = new Category();
        $form = $this->createForm(Category1Type::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            // Log the category creation
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logCreate($user, 'Category', $category->getName(), $category->getId());
            }

            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        // Get all products belonging to this category
        $products = $category->getProductsses();

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        $form = $this->createForm(Category1Type::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Log the category update
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logUpdate($user, 'Category', $category->getName(), $category->getId());
            }

            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->getPayload()->getString('_token'))) {
            $categoryName = $category->getName();
            $categoryId = $category->getId();

            $entityManager->remove($category);
            $entityManager->flush();

            // Log the category deletion
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logDelete($user, 'Category', $categoryName, $categoryId);
            }
        }

        return $this->redirectToRoute('app_category_index');
    }
}
