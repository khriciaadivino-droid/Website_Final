<?php

namespace App\Service;

use App\Entity\Orders;
use App\Entity\Productss;
use App\Entity\Stocks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrderStockService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly LiveRevisionService $liveRevisionService,
    ) {}

    public function syncForCreate(Orders $order, string $source = 'web'): void
    {
        $product = $this->requireProduct($order);
        $quantity = $this->requireQuantity($order);

        $this->ensureStockAvailable($product->getQuantity() ?? 0, $quantity);

        if (!$this->shouldDeductStock($order)) {
            $order->setStockDeducted(false);

            return;
        }

        $product->setQuantity(($product->getQuantity() ?? 0) - $quantity);
        $order->setStockDeducted(true);

        $this->createStockMovement(
            $product,
            -$quantity,
            sprintf('Order %s created as completed%s. Deducted %d item(s).', $order->getOrderNumber(), $this->getSourceSuffix($source), $quantity)
        );

        $this->bumpInventoryRevisions();
    }

    public function syncForUpdate(
        Orders $order,
        ?Productss $originalProduct,
        int $originalQuantity,
        bool $originalStockDeducted,
        string $source = 'web'
    ): void {
        $currentProduct = $this->requireProduct($order);
        $currentQuantity = $this->requireQuantity($order);
        $shouldDeductStock = $this->shouldDeductStock($order);

        if (!$originalStockDeducted && !$shouldDeductStock) {
            $availableStock = $currentProduct->getQuantity() ?? 0;
            $this->ensureStockAvailable($availableStock, $currentQuantity);
            $order->setStockDeducted(false);

            return;
        }

        if (!$originalStockDeducted && $shouldDeductStock) {
            $availableStock = $currentProduct->getQuantity() ?? 0;
            $this->ensureStockAvailable($availableStock, $currentQuantity);

            $currentProduct->setQuantity($availableStock - $currentQuantity);
            $order->setStockDeducted(true);

            $this->createStockMovement(
                $currentProduct,
                -$currentQuantity,
                sprintf('Order %s marked completed%s. Deducted %d item(s).', $order->getOrderNumber(), $this->getSourceSuffix($source), $currentQuantity)
            );

            $this->bumpInventoryRevisions();

            return;
        }

        if ($originalStockDeducted && !$shouldDeductStock) {
            $availableStock = $this->getAvailableStockAfterRelease(
                $currentProduct,
                $originalProduct,
                $originalQuantity,
                $originalStockDeducted
            );
            $this->ensureStockAvailable($availableStock, $currentQuantity);

            if ($originalProduct && $originalQuantity > 0) {
                $originalProduct->setQuantity(($originalProduct->getQuantity() ?? 0) + $originalQuantity);
                $this->createStockMovement(
                    $originalProduct,
                    $originalQuantity,
                    sprintf('Order %s is no longer completed%s. Restored %d item(s).', $order->getOrderNumber(), $this->getSourceSuffix($source), $originalQuantity)
                );
            }

            $order->setStockDeducted(false);

            $this->bumpInventoryRevisions();

            return;
        }

        $order->setStockDeducted(true);

        if ($originalProduct && $originalProduct->getId() === $currentProduct->getId()) {
            $stockAdjustment = $originalQuantity - $currentQuantity;
            $newStock = ($currentProduct->getQuantity() ?? 0) + $stockAdjustment;

            if ($newStock < 0) {
                throw new \RuntimeException(sprintf('Insufficient stock. Available: %d, requested: %d.', $currentProduct->getQuantity() ?? 0, $currentQuantity));
            }

            $currentProduct->setQuantity($newStock);

            if ($stockAdjustment !== 0) {
                $movementText = $stockAdjustment > 0
                    ? sprintf('Order %s updated%s. Restored %d item(s).', $order->getOrderNumber(), $this->getSourceSuffix($source), $stockAdjustment)
                    : sprintf('Order %s updated%s. Deducted %d item(s).', $order->getOrderNumber(), $this->getSourceSuffix($source), abs($stockAdjustment));

                $this->createStockMovement($currentProduct, $stockAdjustment, $movementText);
            }

            $this->bumpInventoryRevisions();

            return;
        }

        if ($originalProduct && $originalQuantity > 0) {
            $originalProduct->setQuantity(($originalProduct->getQuantity() ?? 0) + $originalQuantity);
            $this->createStockMovement(
                $originalProduct,
                $originalQuantity,
                sprintf('Order %s moved%s. Restored %d item(s).', $order->getOrderNumber(), $this->getSourceSuffix($source), $originalQuantity)
            );
        }

        $availableStock = $currentProduct->getQuantity() ?? 0;
        $this->ensureStockAvailable($availableStock, $currentQuantity);

        $currentProduct->setQuantity($availableStock - $currentQuantity);
        $this->createStockMovement(
            $currentProduct,
            -$currentQuantity,
            sprintf('Order %s moved%s. Deducted %d item(s).', $order->getOrderNumber(), $this->getSourceSuffix($source), $currentQuantity)
        );

        $this->bumpInventoryRevisions();
    }

    public function restoreForDelete(Orders $order, string $source = 'web'): void
    {
        if (!$order->isStockDeducted()) {
            return;
        }

        $product = $order->getProduct();
        $quantity = $order->getQuantity() ?? 0;

        if (!$product || $quantity <= 0) {
            return;
        }

        $product->setQuantity(($product->getQuantity() ?? 0) + $quantity);
        $this->createStockMovement(
            $product,
            $quantity,
            sprintf('Order %s deleted%s. Restored %d item(s).', $order->getOrderNumber(), $this->getSourceSuffix($source), $quantity)
        );

        $this->bumpInventoryRevisions();
    }

    private function shouldDeductStock(Orders $order): bool
    {
        return strcasecmp($order->getStatus() ?? '', 'Completed') === 0;
    }

    private function requireProduct(Orders $order): Productss
    {
        $product = $order->getProduct();
        if (!$product) {
            throw new \InvalidArgumentException('Please select a product for this order.');
        }

        return $product;
    }

    private function requireQuantity(Orders $order): int
    {
        $quantity = $order->getQuantity() ?? 0;
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Order quantity must be greater than zero.');
        }

        return $quantity;
    }

    private function ensureStockAvailable(int $availableStock, int $requestedQuantity): void
    {
        if ($availableStock < $requestedQuantity) {
            throw new \RuntimeException(sprintf('Insufficient stock. Available: %d, requested: %d.', $availableStock, $requestedQuantity));
        }
    }

    private function getAvailableStockAfterRelease(
        Productss $currentProduct,
        ?Productss $originalProduct,
        int $originalQuantity,
        bool $originalStockDeducted
    ): int {
        $availableStock = $currentProduct->getQuantity() ?? 0;

        if (
            $originalStockDeducted
            && $originalProduct
            && $originalProduct->getId() === $currentProduct->getId()
        ) {
            $availableStock += $originalQuantity;
        }

        return $availableStock;
    }

    private function createStockMovement(Productss $product, int $quantityChange, string $message): void
    {
        $messageWithActor = sprintf('%s By: %s.', rtrim($message, '.'), $this->getStockActorLabel());

        $movement = new Stocks();
        $movement->setProductss($product);
        $movement->setQuantityChange((float) $quantityChange);
        $movement->setStockChangeLog($messageWithActor);
        $movement->setCreateAt(new \DateTimeImmutable());
        $movement->setUpdateAt(new \DateTimeImmutable());

        $this->entityManager->persist($movement);
    }

    public function bumpInventoryRevisions(): void
    {
        $this->liveRevisionService->bump(LiveRevisionService::STOCKS);
        $this->liveRevisionService->bump(LiveRevisionService::PRODUCTS);
    }

    private function getSourceSuffix(string $source): string
    {
        return $source === 'api' ? ' via API' : '';
    }

    private function getStockActorLabel(): string
    {
        $user = $this->security->getUser();
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
