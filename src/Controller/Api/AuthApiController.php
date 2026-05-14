<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Productss;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class AuthApiController extends AbstractController
{
    #[Route('/register-legacy', name: 'register_legacy', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation
        $errors = [];
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (!empty($errors)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User with this email already exists',
            ], Response::HTTP_CONFLICT);
        }

        // Create user
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFullName($data['full_name']);
        $user->setRoles(['ROLE_USER']);
        $user->setStatus('active');

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        // Send verification email
        $emailVerificationService->createAndSendVerificationEmail($user);

        return new JsonResponse([
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.',
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'full_name' => $user->getFullName(),
                'verified' => $user->isVerified(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/verify-email-legacy', name: 'verify_email_legacy', methods: ['POST'])]
    public function verifyEmail(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['token'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Verification token is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $emailVerificationService->verifyEmail($data['token']);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Verification token is invalid or has expired',
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Email verified successfully',
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'full_name' => $user->getFullName(),
                'verified' => $user->isVerified(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/users/me', name: 'current_user', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'full_name' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'verified' => $user->isVerified(),
                'last_login' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
                'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/products', name: 'products_list', methods: ['GET'])]
    public function listProducts(EntityManagerInterface $entityManager): JsonResponse
    {
        // Get all products from database
        $products = $entityManager->getRepository(Productss::class)->findAll();

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'quantity' => $product->getQuantity(),
                'category' => $product->getCategory()?->getName(),
                'image' => $product->getImagefilename(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $data,
            'count' => count($data),
        ], Response::HTTP_OK);
    }

    #[Route('/products/{id}', name: 'product_detail', methods: ['GET'])]
    public function getProduct(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $entityManager->getRepository(Productss::class)->find($id);

        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'quantity' => $product->getQuantity(),
                'category' => $product->getCategory()?->getName(),
                'image' => $product->getImagefilename(),
            ],
        ], Response::HTTP_OK);
    }
}
