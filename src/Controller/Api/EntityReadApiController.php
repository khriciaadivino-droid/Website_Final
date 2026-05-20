<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Entity\Orders;
use App\Entity\PetOwners;
use App\Entity\PetProfileManagement;
use App\Entity\Productss;
use App\Entity\Stocks;
use App\Repository\ActivityLogRepository;
use App\Repository\CategoryRepository;
use App\Repository\OrdersRepository;
use App\Repository\PetOwnersRepository;
use App\Repository\PetProfileManagementRepository;
use App\Repository\ProductssRepository;
use App\Repository\StocksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api', name: 'api_entities_')]
class EntityReadApiController extends AbstractController
{
    private const ORDER_STATUS_MAP = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    #[Route('/activity_logs', name: 'activity_logs', methods: ['GET'])]
    public function activityLogs(ActivityLogRepository $activityLogRepository): JsonResponse
    {
        $logs = $activityLogRepository->findBy([], ['timestamp' => 'DESC']);

        $data = array_map(static function ($log): array {
            return [
                'id' => $log->getId(),
                'user_id' => $log->getUserId(),
                'username' => $log->getUsername(),
                'role' => $log->getRole(),
                'action' => $log->getAction(),
                'target_data' => $log->getTargetData(),
                'timestamp' => $log->getTimestamp()?->format('Y-m-d H:i:s'),
            ];
        }, $logs);

        return $this->success('Activity logs fetched successfully', $data);
    }

    #[Route('/events', name: 'events', methods: ['GET'])]
    public function events(ActivityLogRepository $activityLogRepository): JsonResponse
    {
        // Alias endpoint for mobile/Postman collections expecting /api/events.
        return $this->activityLogs($activityLogRepository);
    }

    #[Route('/stocks', name: 'stocks', methods: ['GET'])]
    public function stocks(StocksRepository $stocksRepository): JsonResponse
    {
        $stocks = $stocksRepository->findBy([], ['createAt' => 'DESC']);

        $data = array_map(static function ($stock): array {
            return [
                'id' => $stock->getId(),
                'product' => [
                    'id' => $stock->getProductss()?->getId(),
                    'name' => $stock->getProductss()?->getName(),
                ],
                'quantity_change' => $stock->getQuantityChange(),
                'stock_change_log' => $stock->getStockChangeLog(),
                'created_at' => $stock->getCreateAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $stock->getUpdateAt()?->format('Y-m-d H:i:s'),
            ];
        }, $stocks);

        return $this->success('Stocks fetched successfully', $data);
    }

    #[Route('/stocks', name: 'stocks_create', methods: ['POST'])]
    public function createStock(Request $request, EntityManagerInterface $entityManager, ProductssRepository $productssRepository): JsonResponse
    {
        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        $productId = isset($data['product_id']) ? (int) $data['product_id'] : 0;
        if ($productId <= 0) {
            return $this->error('product_id is required', Response::HTTP_BAD_REQUEST);
        }

        $product = $productssRepository->find($productId);
        if (!$product) {
            return $this->error('Product not found', Response::HTTP_NOT_FOUND);
        }

        $stock = new Stocks();
        $stock->setProductss($product);
        $stock->setStockChangeLog((string) ($data['stock_change_log'] ?? 'Stock entry created via API'));
        $stock->setQuantityChange(isset($data['quantity_change']) ? (float) $data['quantity_change'] : null);
        $stock->setCreateAt(new \DateTimeImmutable());
        $stock->setUpdateAt(new \DateTimeImmutable());

        $entityManager->persist($stock);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Stock created successfully',
            'data' => ['id' => $stock->getId()],
        ], Response::HTTP_CREATED);
    }

    #[Route('/stocks/{id}', name: 'stocks_update', methods: ['PUT', 'PATCH'])]
    public function updateStock(int $id, Request $request, StocksRepository $stocksRepository, ProductssRepository $productssRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $stock = $stocksRepository->find($id);
        if (!$stock) {
            return $this->error('Stock not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['product_id'])) {
            $product = $productssRepository->find((int) $data['product_id']);
            if (!$product) {
                return $this->error('Product not found', Response::HTTP_NOT_FOUND);
            }
            $stock->setProductss($product);
        }

        if (isset($data['stock_change_log'])) {
            $stock->setStockChangeLog((string) $data['stock_change_log']);
        }

        if (array_key_exists('quantity_change', $data)) {
            $stock->setQuantityChange($data['quantity_change'] !== null ? (float) $data['quantity_change'] : null);
        }

        $stock->setUpdateAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->success('Stock updated successfully', [['id' => $stock->getId()]]);
    }

    #[Route('/stocks/{id}', name: 'stocks_delete', methods: ['DELETE'])]
    public function deleteStock(int $id, StocksRepository $stocksRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $stock = $stocksRepository->find($id);
        if (!$stock) {
            return $this->error('Stock not found', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($stock);
        $entityManager->flush();

        return $this->success('Stock deleted successfully', [['id' => $id]]);
    }

    #[Route('/orders', name: 'orders', methods: ['GET'])]
    public function orders(OrdersRepository $ordersRepository): JsonResponse
    {
        $orders = $ordersRepository->findBy([], ['orderDate' => 'DESC']);

        $data = array_map(static function ($order): array {
            return [
                'id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'product' => [
                    'id' => $order->getProduct()?->getId(),
                    'name' => $order->getProduct()?->getName(),
                ],
                'customer_name' => $order->getCustomerName(),
                'customer_email' => $order->getCustomerEmail(),
                'order_date' => $order->getOrderDate()?->format('Y-m-d H:i:s'),
                'quantity' => $order->getQuantity(),
                'total_amount' => $order->getTotalAmount(),
                'status' => $order->getStatus(),
                'fulfillment_type' => $order->getFulfillmentType(),
                'delivery_address' => $order->getDeliveryAddress(),
                'payment_method'   => $order->getPaymentMethod(),
                'payment_status'   => $order->getPaymentStatus(),
            ];
        }, $orders);

        return $this->success('Orders fetched successfully', $data);
    }

    #[Route('/orders', name: 'orders_create', methods: ['POST'])]
    public function createOrder(Request $request, EntityManagerInterface $entityManager, ProductssRepository $productssRepository): JsonResponse
    {
        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        foreach (['order_number', 'customer_name', 'quantity', 'total_amount', 'product_id'] as $requiredField) {
            if (!isset($data[$requiredField]) || $data[$requiredField] === '') {
                return $this->error($requiredField . ' is required', Response::HTTP_BAD_REQUEST);
            }
        }

        $quantity = (int) $data['quantity'];
        if ($quantity <= 0) {
            return $this->error('quantity must be greater than zero', Response::HTTP_BAD_REQUEST);
        }

        $product = $productssRepository->find((int) $data['product_id']);
        if (!$product) {
            return $this->error('Product not found', Response::HTTP_NOT_FOUND);
        }

        $availableStock = $product->getQuantity() ?? 0;
        if ($availableStock < $quantity) {
            return $this->error(sprintf('Insufficient stock. Available: %d, requested: %d.', $availableStock, $quantity), Response::HTTP_CONFLICT);
        }

        $order = new Orders();
        $order->setOrderNumber((string) $data['order_number']);
        $order->setCustomerName((string) $data['customer_name']);
        $order->setCustomerEmail(isset($data['customer_email']) ? (string) $data['customer_email'] : null);
        $order->setQuantity($quantity);
        $order->setTotalAmount((float) $data['total_amount']);

        $fulfillmentType = isset($data['fulfillment_type']) ? strtolower(trim((string) $data['fulfillment_type'])) : null;
        if ($fulfillmentType !== null && !in_array($fulfillmentType, ['pickup', 'delivery'], true)) {
            return $this->error('fulfillment_type must be "pickup" or "delivery"', Response::HTTP_BAD_REQUEST);
        }
        $order->setFulfillmentType($fulfillmentType);

        $deliveryAddress = isset($data['delivery_address']) ? trim((string) $data['delivery_address']) : null;
        if ($fulfillmentType === 'delivery' && ($deliveryAddress === null || $deliveryAddress === '')) {
            return $this->error('delivery_address is required when fulfillment_type is "delivery"', Response::HTTP_BAD_REQUEST);
        }
        $order->setDeliveryAddress($deliveryAddress ?: null);

        $paymentMethod = isset($data['payment_method']) ? strtolower(trim((string) $data['payment_method'])) : null;
        $validPaymentMethods = ['cash', 'gcash', 'maya', 'card'];
        if ($paymentMethod !== null && !in_array($paymentMethod, $validPaymentMethods, true)) {
            return $this->error('payment_method must be one of: cash, gcash, maya, card', Response::HTTP_BAD_REQUEST);
        }
        $order->setPaymentMethod($paymentMethod);

        $status = $this->resolveOrderStatus($data, 'Pending');
        if ($status === null) {
            return $this->error('Invalid order status', Response::HTTP_BAD_REQUEST);
        }

        $order->setStatus($status);
        $order->setOrderDate(isset($data['order_date']) ? new \DateTime((string) $data['order_date']) : new \DateTime());

        $order->setProduct($product);
        $product->setQuantity($availableStock - $quantity);

        $this->createStockMovement(
            $entityManager,
            $product,
            -$quantity,
            sprintf('Order %s created via API. Deducted %d item(s).', $order->getOrderNumber(), $quantity)
        );

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => [
                'id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'fulfillment_type' => $order->getFulfillmentType(),
                'delivery_address' => $order->getDeliveryAddress(),
                'payment_method' => $order->getPaymentMethod(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/orders/{id}', name: 'orders_update', methods: ['PUT', 'PATCH'])]
    public function updateOrder(int $id, Request $request, OrdersRepository $ordersRepository, ProductssRepository $productssRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $order = $ordersRepository->find($id);
        if (!$order) {
            return $this->error('Order not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        $originalProduct = $order->getProduct();
        $originalQuantity = $order->getQuantity() ?? 0;

        if (isset($data['order_number'])) {
            $order->setOrderNumber((string) $data['order_number']);
        }
        if (isset($data['customer_name'])) {
            $order->setCustomerName((string) $data['customer_name']);
        }
        if (array_key_exists('customer_email', $data)) {
            $order->setCustomerEmail($data['customer_email'] !== null ? (string) $data['customer_email'] : null);
        }
        if (isset($data['quantity'])) {
            $order->setQuantity((int) $data['quantity']);
        }
        if (isset($data['total_amount'])) {
            $order->setTotalAmount((float) $data['total_amount']);
        }
        if (isset($data['status'])) {
            $status = $this->resolveOrderStatus($data, $order->getStatus() ?? 'Pending');
            if ($status === null) {
                return $this->error('Invalid order status', Response::HTTP_BAD_REQUEST);
            }

            $order->setStatus($status);
        }
        if (isset($data['order_date'])) {
            $order->setOrderDate(new \DateTime((string) $data['order_date']));
        }
        if (array_key_exists('product_id', $data)) {
            if ($data['product_id'] === null) {
                $order->setProduct(null);
            } else {
                $product = $productssRepository->find((int) $data['product_id']);
                if (!$product) {
                    return $this->error('Product not found', Response::HTTP_NOT_FOUND);
                }
                $order->setProduct($product);
            }
        }

        $currentProduct = $order->getProduct();
        $currentQuantity = $order->getQuantity() ?? 0;

        if ($currentQuantity <= 0) {
            return $this->error('quantity must be greater than zero', Response::HTTP_BAD_REQUEST);
        }

        if ($originalProduct && $currentProduct && $originalProduct->getId() === $currentProduct->getId()) {
            $stockAdjustment = $originalQuantity - $currentQuantity;
            $newStock = ($currentProduct->getQuantity() ?? 0) + $stockAdjustment;

            if ($newStock < 0) {
                return $this->error(sprintf('Insufficient stock. Available: %d, requested increase: %d.', $currentProduct->getQuantity() ?? 0, $currentQuantity - $originalQuantity), Response::HTTP_CONFLICT);
            }

            $currentProduct->setQuantity($newStock);

            if ($stockAdjustment !== 0) {
                $movementText = $stockAdjustment > 0
                    ? sprintf('Order %s updated via API. Restored %d item(s).', $order->getOrderNumber(), $stockAdjustment)
                    : sprintf('Order %s updated via API. Deducted %d item(s).', $order->getOrderNumber(), abs($stockAdjustment));

                $this->createStockMovement($entityManager, $currentProduct, $stockAdjustment, $movementText);
            }
        } else {
            if ($originalProduct) {
                $originalProduct->setQuantity(($originalProduct->getQuantity() ?? 0) + $originalQuantity);
                $this->createStockMovement(
                    $entityManager,
                    $originalProduct,
                    $originalQuantity,
                    sprintf('Order %s moved/de-linked via API. Restored %d item(s).', $order->getOrderNumber(), $originalQuantity)
                );
            }

            if ($currentProduct) {
                $availableStock = $currentProduct->getQuantity() ?? 0;
                if ($availableStock < $currentQuantity) {
                    return $this->error(sprintf('Insufficient stock for selected product. Available: %d, requested: %d.', $availableStock, $currentQuantity), Response::HTTP_CONFLICT);
                }

                $currentProduct->setQuantity($availableStock - $currentQuantity);
                $this->createStockMovement(
                    $entityManager,
                    $currentProduct,
                    -$currentQuantity,
                    sprintf('Order %s moved/linked via API. Deducted %d item(s).', $order->getOrderNumber(), $currentQuantity)
                );
            }
        }

        $entityManager->flush();

        return $this->success('Order updated successfully', [['id' => $order->getId()]]);
    }

    #[Route('/orders/{id}', name: 'orders_delete', methods: ['DELETE'])]
    public function deleteOrder(int $id, OrdersRepository $ordersRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $order = $ordersRepository->find($id);
        if (!$order) {
            return $this->error('Order not found', Response::HTTP_NOT_FOUND);
        }

        $product = $order->getProduct();
        $quantity = $order->getQuantity() ?? 0;

        if ($product && $quantity > 0) {
            $product->setQuantity(($product->getQuantity() ?? 0) + $quantity);
            $this->createStockMovement(
                $entityManager,
                $product,
                $quantity,
                sprintf('Order %s deleted via API. Restored %d item(s).', $order->getOrderNumber(), $quantity)
            );
        }

        $entityManager->remove($order);
        $entityManager->flush();

        return $this->success('Order deleted successfully', [['id' => $id]]);
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function categories(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findBy([], ['name' => 'ASC']);

        $data = array_map(static function ($category): array {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'product_count' => $category->getProductsses()->count(),
            ];
        }, $categories);

        return $this->success('Categories fetched successfully', $data);
    }

    #[Route('/categories', name: 'categories_create', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            return $this->error('name is required', Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setName(trim((string) $data['name']));

        $entityManager->persist($category);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => ['id' => $category->getId()],
        ], Response::HTTP_CREATED);
    }

    #[Route('/categories/{id}', name: 'categories_update', methods: ['PUT', 'PATCH'])]
    public function updateCategory(int $id, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            return $this->error('Category not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            return $this->error('name is required', Response::HTTP_BAD_REQUEST);
        }

        $category->setName(trim((string) $data['name']));
        $entityManager->flush();

        return $this->success('Category updated successfully', [['id' => $category->getId()]]);
    }

    #[Route('/categories/{id}', name: 'categories_delete', methods: ['DELETE'])]
    public function deleteCategory(int $id, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            return $this->error('Category not found', Response::HTTP_NOT_FOUND);
        }

        try {
            $entityManager->remove($category);
            $entityManager->flush();
        } catch (\Throwable $exception) {
            return $this->error('Unable to delete category: ' . $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->success('Category deleted successfully', [['id' => $id]]);
    }

    #[Route('/pet-owners', name: 'pet_owners', methods: ['GET'])]
    public function petOwners(PetOwnersRepository $petOwnersRepository): JsonResponse
    {
        $owners = $petOwnersRepository->findBy([], ['lastName' => 'ASC']);

        $data = array_map(static function ($owner): array {
            return [
                'id' => $owner->getId(),
                'full_name' => $owner->getFullName(),
                'email' => $owner->getEmail(),
                'phone_number' => $owner->getPhoneNumber(),
                'address' => $owner->getAddress(),
                'registration_date' => $owner->getRegistrationDate()?->format('Y-m-d H:i:s'),
                'pet_count' => $owner->getPetProfiles()->count(),
            ];
        }, $owners);

        return $this->success('Pet owners fetched successfully', $data);
    }

    #[Route('/pet-owners', name: 'pet_owners_create', methods: ['POST'])]
    public function createPetOwner(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        foreach (['first_name', 'last_name', 'email'] as $requiredField) {
            if (!isset($data[$requiredField]) || trim((string) $data[$requiredField]) === '') {
                return $this->error($requiredField . ' is required', Response::HTTP_BAD_REQUEST);
            }
        }

        $owner = new PetOwners();
        $owner->setFirstName(trim((string) $data['first_name']));
        $owner->setLastName(trim((string) $data['last_name']));
        $owner->setEmail(trim((string) $data['email']));
        $owner->setPhoneNumber(isset($data['phone_number']) ? (string) $data['phone_number'] : null);
        $owner->setAddress(isset($data['address']) ? (string) $data['address'] : null);
        $owner->setRegistrationDate(isset($data['registration_date']) ? new \DateTime((string) $data['registration_date']) : new \DateTime());

        $entityManager->persist($owner);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Pet owner created successfully',
            'data' => ['id' => $owner->getId()],
        ], Response::HTTP_CREATED);
    }

    #[Route('/pet-owners/{id}', name: 'pet_owners_update', methods: ['PUT', 'PATCH'])]
    public function updatePetOwner(int $id, Request $request, PetOwnersRepository $petOwnersRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $owner = $petOwnersRepository->find($id);
        if (!$owner) {
            return $this->error('Pet owner not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['first_name'])) {
            $owner->setFirstName(trim((string) $data['first_name']));
        }
        if (isset($data['last_name'])) {
            $owner->setLastName(trim((string) $data['last_name']));
        }
        if (isset($data['email'])) {
            $owner->setEmail(trim((string) $data['email']));
        }
        if (array_key_exists('phone_number', $data)) {
            $owner->setPhoneNumber($data['phone_number'] !== null ? (string) $data['phone_number'] : null);
        }
        if (array_key_exists('address', $data)) {
            $owner->setAddress($data['address'] !== null ? (string) $data['address'] : null);
        }
        if (isset($data['registration_date'])) {
            $owner->setRegistrationDate(new \DateTime((string) $data['registration_date']));
        }

        $entityManager->flush();

        return $this->success('Pet owner updated successfully', [['id' => $owner->getId()]]);
    }

    #[Route('/pet-owners/{id}', name: 'pet_owners_delete', methods: ['DELETE'])]
    public function deletePetOwner(int $id, PetOwnersRepository $petOwnersRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $owner = $petOwnersRepository->find($id);
        if (!$owner) {
            return $this->error('Pet owner not found', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($owner);
        $entityManager->flush();

        return $this->success('Pet owner deleted successfully', [['id' => $id]]);
    }

    #[Route('/pet-profiles', name: 'pet_profiles', methods: ['GET'])]
    public function petProfiles(PetProfileManagementRepository $petProfileRepository): JsonResponse
    {
        $petProfiles = $petProfileRepository->findAll();

        $data = array_map(static function ($pet): array {
            return [
                'id' => $pet->getId(),
                'name' => $pet->getName(),
                'species' => $pet->getSpecies(),
                'breed' => $pet->getBreed(),
                'age' => $pet->getAge(),
                'date_of_birth' => $pet->getDateofbirth()?->format('Y-m-d'),
                'image' => $pet->getImage(),
                'is_pet_of_the_month' => $pet->isPetOfTheMonth(),
                'owner' => $pet->getOwner() ? [
                    'id' => $pet->getOwner()->getId(),
                    'full_name' => $pet->getOwner()->getFullName(),
                    'email' => $pet->getOwner()->getEmail(),
                ] : null,
            ];
        }, $petProfiles);

        return $this->success('Pet profiles fetched successfully', $data);
    }

    #[Route('/pet-profiles', name: 'pet_profiles_create', methods: ['POST'])]
    public function createPetProfile(Request $request, EntityManagerInterface $entityManager, PetOwnersRepository $petOwnersRepository): JsonResponse
    {
        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        foreach (['name', 'species', 'breed', 'age'] as $requiredField) {
            if (!isset($data[$requiredField]) || $data[$requiredField] === '') {
                return $this->error($requiredField . ' is required', Response::HTTP_BAD_REQUEST);
            }
        }

        $pet = new PetProfileManagement();
        $pet->setName((string) $data['name']);
        $pet->setSpecies((string) $data['species']);
        $pet->setBreed((string) $data['breed']);
        $pet->setAge((float) $data['age']);
        if (isset($data['date_of_birth']) && $data['date_of_birth'] !== null) {
            $pet->setDateofbirth(new \DateTime((string) $data['date_of_birth']));
        }
        if (isset($data['image']) && $data['image'] !== null) {
            $pet->setImage((string) $data['image']);
        }
        if (isset($data['is_pet_of_the_month'])) {
            $pet->setIsPetOfTheMonth((bool) $data['is_pet_of_the_month']);
        }
        if (isset($data['owner_id']) && $data['owner_id'] !== null) {
            $owner = $petOwnersRepository->find((int) $data['owner_id']);
            if (!$owner) {
                return $this->error('Pet owner not found', Response::HTTP_NOT_FOUND);
            }
            $pet->setOwner($owner);
        }

        $entityManager->persist($pet);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Pet profile created successfully',
            'data' => ['id' => $pet->getId()],
        ], Response::HTTP_CREATED);
    }

    #[Route('/pet-profiles/{id}', name: 'pet_profiles_update', methods: ['PUT', 'PATCH'])]
    public function updatePetProfile(int $id, Request $request, PetProfileManagementRepository $petProfileRepository, PetOwnersRepository $petOwnersRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $pet = $petProfileRepository->find($id);
        if (!$pet) {
            return $this->error('Pet profile not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $pet->setName((string) $data['name']);
        }
        if (isset($data['species'])) {
            $pet->setSpecies((string) $data['species']);
        }
        if (isset($data['breed'])) {
            $pet->setBreed((string) $data['breed']);
        }
        if (isset($data['age'])) {
            $pet->setAge((float) $data['age']);
        }
        if (array_key_exists('date_of_birth', $data)) {
            $pet->setDateofbirth($data['date_of_birth'] !== null ? new \DateTime((string) $data['date_of_birth']) : null);
        }
        if (array_key_exists('image', $data) && $data['image'] !== null) {
            $pet->setImage((string) $data['image']);
        }
        if (isset($data['is_pet_of_the_month'])) {
            $pet->setIsPetOfTheMonth((bool) $data['is_pet_of_the_month']);
        }
        if (array_key_exists('owner_id', $data)) {
            if ($data['owner_id'] === null) {
                $pet->setOwner(null);
            } else {
                $owner = $petOwnersRepository->find((int) $data['owner_id']);
                if (!$owner) {
                    return $this->error('Pet owner not found', Response::HTTP_NOT_FOUND);
                }
                $pet->setOwner($owner);
            }
        }

        $entityManager->flush();

        return $this->success('Pet profile updated successfully', [['id' => $pet->getId()]]);
    }

    #[Route('/pet-profiles/{id}', name: 'pet_profiles_delete', methods: ['DELETE'])]
    public function deletePetProfile(int $id, PetProfileManagementRepository $petProfileRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $pet = $petProfileRepository->find($id);
        if (!$pet) {
            return $this->error('Pet profile not found', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($pet);
        $entityManager->flush();

        return $this->success('Pet profile deleted successfully', [['id' => $id]]);
    }

    #[Route('/products', name: 'products_create', methods: ['POST'])]
    public function createProduct(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): JsonResponse
    {
        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        foreach (['name', 'description', 'price', 'quantity'] as $requiredField) {
            if (!isset($data[$requiredField]) || $data[$requiredField] === '') {
                return $this->error($requiredField . ' is required', Response::HTTP_BAD_REQUEST);
            }
        }

        $product = new Productss();
        $product->setName((string) $data['name']);
        $product->setDescription((string) $data['description']);
        $product->setPrice((float) $data['price']);
        $product->setQuantity((int) $data['quantity']);
        if (isset($data['image']) && $data['image'] !== null) {
            $product->setImagefilename((string) $data['image']);
        }
        if (isset($data['category_id']) && $data['category_id'] !== null) {
            $category = $categoryRepository->find((int) $data['category_id']);
            if (!$category) {
                return $this->error('Category not found', Response::HTTP_NOT_FOUND);
            }
            $product->setCategory($category);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => ['id' => $product->getId()],
        ], Response::HTTP_CREATED);
    }

    #[Route('/products/{id}', name: 'products_update', methods: ['PUT', 'PATCH'])]
    public function updateProduct(int $id, Request $request, ProductssRepository $productssRepository, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $productssRepository->find($id);
        if (!$product) {
            return $this->error('Product not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $product->setName((string) $data['name']);
        }
        if (isset($data['description'])) {
            $product->setDescription((string) $data['description']);
        }
        if (isset($data['price'])) {
            $product->setPrice((float) $data['price']);
        }
        if (isset($data['quantity'])) {
            $product->setQuantity((int) $data['quantity']);
        }
        if (array_key_exists('image', $data)) {
            $product->setImagefilename($data['image'] !== null ? (string) $data['image'] : null);
        }
        if (array_key_exists('category_id', $data)) {
            if ($data['category_id'] === null) {
                $product->setCategory(null);
            } else {
                $category = $categoryRepository->find((int) $data['category_id']);
                if (!$category) {
                    return $this->error('Category not found', Response::HTTP_NOT_FOUND);
                }
                $product->setCategory($category);
            }
        }

        $entityManager->flush();

        return $this->success('Product updated successfully', [['id' => $product->getId()]]);
    }

    #[Route('/products/{id}', name: 'products_delete', methods: ['DELETE'])]
    public function deleteProduct(int $id, ProductssRepository $productssRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $productssRepository->find($id);
        if (!$product) {
            return $this->error('Product not found', Response::HTTP_NOT_FOUND);
        }

        try {
            $entityManager->remove($product);
            $entityManager->flush();
        } catch (\Throwable $exception) {
            return $this->error('Unable to delete product: ' . $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->success('Product deleted successfully', [['id' => $id]]);
    }

    #[Route('/products/upload/image', name: 'products_upload_image', methods: ['POST'])]
    public function uploadProductImage(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $file = $request->files->get('image');

        if (!$file) {
            return $this->error('No image file provided', Response::HTTP_BAD_REQUEST);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('images_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            return $this->error('Failed to upload image: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'data' => [
                'filename' => $newFilename,
                'url' => '/uploads/products/' . $newFilename
            ]
        ], Response::HTTP_CREATED);
    }

    private function success(string $message, array $data): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ],
        ]);
    }

    private function error(string $message, int $statusCode): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $statusCode);
    }

    private function parseJson(Request $request): ?array
    {
        $content = trim($request->getContent());
        if ($content === '') {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : null;
        } catch (\JsonException) {
            return null;
        }
    }

    private function resolveOrderStatus(array $data, string $fallback): ?string
    {
        if (!$this->canEditOrderStatus()) {
            return $fallback;
        }

        if (!array_key_exists('status', $data) || $data['status'] === null || $data['status'] === '') {
            return $fallback;
        }

        return $this->normalizeOrderStatus((string) $data['status']);
    }

    private function normalizeOrderStatus(string $status): ?string
    {
        $normalized = strtolower(trim($status));

        return self::ORDER_STATUS_MAP[$normalized] ?? null;
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
