<?php

namespace App\Controller;

use App\Entity\Stocks;
use App\Form\StocksType;
use App\Repository\StocksRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stocks')]
final class StocksController extends AbstractController
{
    #[Route(name: 'app_stocks_index', methods: ['GET'])]
    public function index(StocksRepository $stocksRepository): Response
    {
        return $this->render('stocks/index.html.twig', [
            'stocks' => $stocksRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_stocks_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        $stock = new Stocks();
        $form = $this->createForm(StocksType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->setCreateAt(new \DateTimeImmutable());
            $stock->setUpdateAt(new \DateTimeImmutable());
            $quantityChange = $stock->getQuantityChange() ?? 0;
            $logMessage = sprintf('Stock created with quantity change: %s on %s', $quantityChange, $stock->getCreateAt()->format('Y-m-d H:i:s'));
            $stock->setStockChangeLog($logMessage);
            $entityManager->persist($stock);
            $entityManager->flush();

            // Update the product's quantity
            $product = $stock->getProductss();
            $currentQuantity = $product->getQuantity() ?? 0;
            $product->setQuantity($currentQuantity + $quantityChange);
            $entityManager->flush();

            // Log the stock creation
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logCreate($user, 'Stock', $product->getName() . ' Stock', $stock->getId());
            }

            return $this->redirectToRoute('app_stocks_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stocks/new.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stocks_show', methods: ['GET'])]
    public function show(Stocks $stock): Response
    {
        return $this->render('stocks/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stocks_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stocks $stock, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        $originalQuantityChange = $stock->getQuantityChange();
        $form = $this->createForm(StocksType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->setUpdateAt(new \DateTimeImmutable());
            $newQuantityChange = $stock->getQuantityChange();
            $logMessage = sprintf('Stock updated with quantity change: %s on %s', $newQuantityChange, $stock->getUpdateAt()->format('Y-m-d H:i:s'));
            $stock->setStockChangeLog($logMessage);
            $entityManager->flush();

            // Update the product's quantity with the difference
            $product = $stock->getProductss();
            $currentQuantity = $product->getQuantity() ?? 0;
            $product->setQuantity($currentQuantity - $originalQuantityChange + $newQuantityChange);
            $entityManager->flush();

            // Log the stock update
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logUpdate($user, 'Stock', $product->getName() . ' Stock', $stock->getId());
            }

            return $this->redirectToRoute('app_stocks_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stocks/edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stocks_delete', methods: ['POST'])]
    public function delete(Request $request, Stocks $stock, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stock->getId(), $request->getPayload()->getString('_token'))) {
            // Update the product's quantity before deleting the stock
            $product = $stock->getProductss();
            $currentQuantity = $product->getQuantity() ?? 0;
            $quantityChange = $stock->getQuantityChange();
            $product->setQuantity($currentQuantity - $quantityChange);
            $entityManager->flush();

            $stockId = $stock->getId();
            $productName = $product->getName();

            $entityManager->remove($stock);
            $entityManager->flush();

            // Log the stock deletion
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logDelete($user, 'Stock', $productName . ' Stock', $stockId);
            }
        }

        return $this->redirectToRoute('app_stocks_index', [], Response::HTTP_SEE_OTHER);
    }
}
