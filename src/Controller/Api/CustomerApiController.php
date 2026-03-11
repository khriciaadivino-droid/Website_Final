<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customers', name: 'api_customer_')]
class CustomerApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(CustomerRepository $customerRepository): JsonResponse
    {
        $customers = $customerRepository->findAll();
        
        $data = array_map(function (Customer $customer) {
            return [
                'id' => $customer->getId(),
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
                'fullName' => $customer->getFullName(),
                'email' => $customer->getEmail(),
                'phoneNumber' => $customer->getPhoneNumber(),
                'address' => $customer->getAddress(),
                'city' => $customer->getCity(),
                'postalCode' => $customer->getPostalCode(),
                'country' => $customer->getCountry(),
                'registrationDate' => $customer->getRegistrationDate()?->format('Y-m-d H:i:s'),
                'lastPurchaseDate' => $customer->getLastPurchaseDate()?->format('Y-m-d H:i:s'),
            ];
        }, $customers);

        return $this->json([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Customer $customer): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $customer->getId(),
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
                'fullName' => $customer->getFullName(),
                'email' => $customer->getEmail(),
                'phoneNumber' => $customer->getPhoneNumber(),
                'address' => $customer->getAddress(),
                'city' => $customer->getCity(),
                'postalCode' => $customer->getPostalCode(),
                'country' => $customer->getCountry(),
                'registrationDate' => $customer->getRegistrationDate()?->format('Y-m-d H:i:s'),
                'lastPurchaseDate' => $customer->getLastPurchaseDate()?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON data'
            ], Response::HTTP_BAD_REQUEST);
        }

        $customer = new Customer();
        
        try {
            if (isset($data['firstName'])) $customer->setFirstName($data['firstName']);
            if (isset($data['lastName'])) $customer->setLastName($data['lastName']);
            if (isset($data['email'])) $customer->setEmail($data['email']);
            if (isset($data['phoneNumber'])) $customer->setPhoneNumber($data['phoneNumber']);
            if (isset($data['address'])) $customer->setAddress($data['address']);
            if (isset($data['city'])) $customer->setCity($data['city']);
            if (isset($data['postalCode'])) $customer->setPostalCode($data['postalCode']);
            if (isset($data['country'])) $customer->setCountry($data['country']);
            
            // Set registration date
            if (isset($data['registrationDate'])) {
                $customer->setRegistrationDate(new \DateTime($data['registrationDate']));
            } else {
                $customer->setRegistrationDate(new \DateTime());
            }
            
            if (isset($data['lastPurchaseDate'])) {
                $customer->setLastPurchaseDate(new \DateTime($data['lastPurchaseDate']));
            }

            // Validate
            $errors = $this->validator->validate($customer);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($customer);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => [
                    'id' => $customer->getId(),
                    'firstName' => $customer->getFirstName(),
                    'lastName' => $customer->getLastName(),
                    'fullName' => $customer->getFullName(),
                    'email' => $customer->getEmail(),
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error creating customer: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON data'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            if (isset($data['firstName'])) $customer->setFirstName($data['firstName']);
            if (isset($data['lastName'])) $customer->setLastName($data['lastName']);
            if (isset($data['email'])) $customer->setEmail($data['email']);
            if (isset($data['phoneNumber'])) $customer->setPhoneNumber($data['phoneNumber']);
            if (isset($data['address'])) $customer->setAddress($data['address']);
            if (isset($data['city'])) $customer->setCity($data['city']);
            if (isset($data['postalCode'])) $customer->setPostalCode($data['postalCode']);
            if (isset($data['country'])) $customer->setCountry($data['country']);
            
            if (isset($data['registrationDate'])) {
                $customer->setRegistrationDate(new \DateTime($data['registrationDate']));
            }
            
            if (isset($data['lastPurchaseDate'])) {
                $customer->setLastPurchaseDate(new \DateTime($data['lastPurchaseDate']));
            }

            // Validate
            $errors = $this->validator->validate($customer);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => [
                    'id' => $customer->getId(),
                    'firstName' => $customer->getFirstName(),
                    'lastName' => $customer->getLastName(),
                    'fullName' => $customer->getFullName(),
                    'email' => $customer->getEmail(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating customer: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Customer $customer): JsonResponse
    {
        try {
            $this->entityManager->remove($customer);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting customer: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
