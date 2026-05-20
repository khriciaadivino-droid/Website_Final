<?php

namespace App\Controller;

use App\Entity\Productss;
use App\Entity\Orders;
use App\Entity\Stocks;
use App\Form\OrdersType;
use App\Repository\OrdersRepository;
use App\Service\ActivityLogService;
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
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
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

            $availableStock = $product->getQuantity() ?? 0;
            if ($quantity <= 0) {
                $this->addFlash('error', 'Order quantity must be greater than zero.');

                return $this->render('orders/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            if ($availableStock < $quantity) {
                $this->addFlash('error', sprintf('Insufficient stock. Available: %d, requested: %d.', $availableStock, $quantity));

                return $this->render('orders/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $product->setQuantity($availableStock - $quantity);
            $this->createStockMovement(
                $entityManager,
                $product,
                -$quantity,
                sprintf('Order %s created. Deducted %d item(s).', $orderNumber, $quantity)
            );

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
    public function edit(Request $request, Orders $order, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        // Prevent editing completed orders
        if ($order->getStatus() === 'Completed') {
            $this->addFlash('error', 'Cannot edit completed orders.');
            return $this->redirectToRoute('app_orders_show', ['id' => $order->getId()], Response::HTTP_SEE_OTHER);
        }

        $canEditStatus = $this->canEditOrderStatus();
        $originalProduct = $order->getProduct();
        $originalQuantity = $order->getQuantity() ?? 0;
        $originalStatus = $order->getStatus() ?? 'Pending';

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

            if ($quantity <= 0) {
                $this->addFlash('error', 'Order quantity must be greater than zero.');

                return $this->render('orders/edit.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            if ($originalProduct && $originalProduct->getId() === $product->getId()) {
                $stockAdjustment = $originalQuantity - $quantity;
                $updatedStock = ($product->getQuantity() ?? 0) + $stockAdjustment;

                if ($updatedStock < 0) {
                    $this->addFlash('error', sprintf('Insufficient stock. Available: %d, requested increase: %d.', $product->getQuantity() ?? 0, $quantity - $originalQuantity));

                    return $this->render('orders/edit.html.twig', [
                        'order' => $order,
                        'form' => $form,
                    ]);
                }

                $product->setQuantity($updatedStock);

                if ($stockAdjustment !== 0) {
                    $movementText = $stockAdjustment > 0
                        ? sprintf('Order %s edited. Restored %d item(s).', $order->getOrderNumber(), $stockAdjustment)
                        : sprintf('Order %s edited. Deducted %d item(s).', $order->getOrderNumber(), abs($stockAdjustment));

                    $this->createStockMovement($entityManager, $product, $stockAdjustment, $movementText);
                }
            } else {
                if ($originalProduct) {
                    $restored = $originalQuantity;
                    $originalProduct->setQuantity(($originalProduct->getQuantity() ?? 0) + $restored);
                    $this->createStockMovement(
                        $entityManager,
                        $originalProduct,
                        $restored,
                        sprintf('Order %s moved to another product. Restored %d item(s).', $order->getOrderNumber(), $restored)
                    );
                }

                $newAvailableStock = $product->getQuantity() ?? 0;
                if ($newAvailableStock < $quantity) {
                    $this->addFlash('error', sprintf('Insufficient stock for selected product. Available: %d, requested: %d.', $newAvailableStock, $quantity));

                    return $this->render('orders/edit.html.twig', [
                        'order' => $order,
                        'form' => $form,
                    ]);
                }

                $product->setQuantity($newAvailableStock - $quantity);
                $this->createStockMovement(
                    $entityManager,
                    $product,
                    -$quantity,
                    sprintf('Order %s moved from another product. Deducted %d item(s).', $order->getOrderNumber(), $quantity)
                );
            }

            $entityManager->flush();

            // Log the order update
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logUpdate($user, 'Order', 'Order #' . $order->getId(), $order->getId());
            }

            return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_orders_delete', methods: ['POST'])]
    public function delete(Request $request, Orders $order, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->getPayload()->getString('_token'))) {
            $orderId = $order->getId();
            $product = $order->getProduct();
            $quantity = $order->getQuantity() ?? 0;

            if ($product && $quantity > 0) {
                $product->setQuantity(($product->getQuantity() ?? 0) + $quantity);
                $this->createStockMovement(
                    $entityManager,
                    $product,
                    $quantity,
                    sprintf('Order %s deleted. Restored %d item(s).', $order->getOrderNumber(), $quantity)
                );
            }

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

    private function createStockMovement(EntityManagerInterface $entityManager, Productss $product, int $quantityChange, string $message): void
    {
        $messageWithActor = sprintf('%s By: %s.', rtrim($message, '.'), $this->getStockActorLabel());

        $movement = new Stocks();
        $movement->setProductss($product);
        $movement->setQuantityChange((float) $quantityChange);
        $movement->setStockChangeLog($messageWithActor);
        $movement->setCreateAt(new \DateTimeImmutable());
        $movement->setUpdateAt(new \DateTimeImmutable());

        $entityManager->persist($movement);
    }

    private function getStockActorLabel(): string
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return 'System';
        }

        $roles = $user->getRoles();
        $roleLabel = in_array('ROLE_ADMIN', $roles, true)
            ? 'Admin'
            : (in_array('ROLE_STAFF', $roles, true) ? 'Staff' : 'User');

        return sprintf('%s (%s)', $roleLabel, $user->getEmail() ?? 'unknown');
    }
}
