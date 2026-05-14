<?php

namespace App\Controller\Api;

use App\Repository\PetOwnersRepository;
use App\Repository\PetProfileManagementRepository;
use App\Repository\ProductssRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mobile', name: 'api_mobile_')]
class MobileApiController extends AbstractController
{
    #[Route('/products', name: 'products', methods: ['GET'])]
    public function products(ProductssRepository $productssRepository): JsonResponse
    {
        $products = $productssRepository->findAll();

        $data = array_map(static function ($product): array {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'quantity' => $product->getQuantity(),
                'image' => $product->getImagefilename(),
                'category' => $product->getCategory()?->getName(),
            ];
        }, $products);

        return $this->successResponse('Products fetched successfully', $data);
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

        return $this->successResponse('Pet profiles fetched successfully', $data);
    }

    #[Route('/pet-owners', name: 'pet_owners', methods: ['GET'])]
    public function petOwners(PetOwnersRepository $petOwnersRepository): JsonResponse
    {
        $owners = $petOwnersRepository->findAll();

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

        return $this->successResponse('Pet owners fetched successfully', $data);
    }

    private function successResponse(string $message, array $data): JsonResponse
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
}
