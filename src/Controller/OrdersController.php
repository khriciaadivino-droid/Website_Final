<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Form\OrdersType;
use App\Repository\OrdersRepository;
use App\Service\ActivityLogService;
use App\Service\OrderStockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('ROLE_STAFF')]
final class OrdersController extends AbstractController
{
    #[Route(name: 'app_orders_index', methods: ['GET'])]
    public function index(OrdersRepository $ordersRepository): Response
    {
        return $this->render('orders/index.html.twig', [
            'orders' => $ordersRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_orders_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService,
        OrderStockService $orderStockService
    ): Response {
        $order = new Orders();
        $canEditStatus = $this->canEditOrderStatus();
        $form = $this->createForm(OrdersType::class, $order, [
            'can_edit_status' => $canEditStatus,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setStatus($canEditStatus ? ($order->getStatus() ?? 'Pending') : 'Pending');

            // Set order date to current time
            $order->setOrderDate(new \DateTime());

            // Calculate total amount: price * quantity
            $product = $order->getProduct();
            $quantity = $order->getQuantity();
            if ($product && $quantity) {
                $totalAmount = $product->getPrice() * $quantity;
                $order->setTotalAmount($totalAmount);
            }

            // Auto-generate order number: ORD-YYYYMMDD-XXXXX
            $date = new \DateTime();
            $randomNumber = str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            $orderNumber = 'ORD-' . $date->format('Ymd') . '-' . $randomNumber;
            $order->setOrderNumber($orderNumber);

            if (!$product) {
                $this->addFlash('error', 'Please select a product for this order.');

                return $this->render('orders/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            try {
                $orderStockService->syncForCreate($order);
            } catch (\InvalidArgumentException | \RuntimeException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->render('orders/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            // Log the order creation
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logCreate($user, 'Order', 'Order #' . $order->getId(), $order->getId());
            }

            return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('orders/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_orders_show', methods: ['GET'])]
    public function show(Orders $order): Response
    {
        return $this->render('orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_orders_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Orders $order,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService,
        OrderStockService $orderStockService
    ): Response {
        // Prevent editing completed orders
        if ($order->getStatus() === 'Completed') {
            $this->addFlash('error', 'Cannot edit completed orders.');
            return $this->redirectToRoute('app_orders_show', ['id' => $order->getId()], Response::HTTP_SEE_OTHER);
        }

        $canEditStatus = $this->canEditOrderStatus();
        $originalProduct = $order->getProduct();
        $originalQuantity = $order->getQuantity() ?? 0;
        $originalStatus = $order->getStatus() ?? 'Pending';
        $originalStockDeducted = $order->isStockDeducted();

        $form = $this->createForm(OrdersType::class, $order, [
            'can_edit_status' => $canEditStatus,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setStatus($canEditStatus ? ($order->getStatus() ?? $originalStatus) : $originalStatus);

            // Recalculate total amount: price * quantity
            $product = $order->getProduct();
            $quantity = $order->getQuantity();
            if ($product && $quantity) {
                $totalAmount = $product->getPrice() * $quantity;
                $order->setTotalAmount($totalAmount);
            }

            if (!$product) {
                $this->addFlash('error', 'Please select a product for this order.');

                return $this->render('orders/edit.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            try {
                $orderStockService->syncForUpdate(
                    $order,
                    $originalProduct,
                    $originalQuantity,
                    $originalStockDeducted
                );
            } catch (\InvalidArgumentException | \RuntimeException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->render('orders/edit.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            // Log the order update
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logUpdate($user, 'Order', 'Order #' . $order->getId(), $order->getId());
                $activityLogService->logOrderStatusNotification(
                    $user,
                    $order,
                    $originalStatus,
                    $order->getStatus() ?? $originalStatus
                );
            }

            return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_orders_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Orders $order,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService,
        OrderStockService $orderStockService
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->getPayload()->getString('_token'))) {
            $orderId = $order->getId();
            $orderStockService->restoreForDelete($order);

            $entityManager->remove($order);
            $entityManager->flush();

            // Log the order deletion
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logDelete($user, 'Order', 'Order #' . $orderId, $orderId);
            }
        }

        return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canEditOrderStatus(): bool
    {
        return $this->isGranted('ROLE_ADMIN');
    }
}
