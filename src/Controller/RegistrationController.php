<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService
    ): Response {
        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $fullName = $request->request->get('full_name');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validation
            if (empty($email) || empty($fullName) || empty($password) || empty($confirmPassword)) {
                $error = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please provide a valid email address.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                // Check if user already exists
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($existingUser) {
                    $error = 'An account with this email already exists.';
                } else {
                    // Create new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setFullName($fullName);
                    $user->setRoles(['ROLE_USER']);
                    $user->setStatus('active');
                    // Do NOT set verified - user must verify via email

                    // Hash the password
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);

                    // Save to database
                    $entityManager->persist($user);
                    $entityManager->flush();

                    // Send verification email
                    $emailVerificationService->createAndSendVerificationEmail($user);

                    $success = true;
                    $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyEmail(Request $request, EmailVerificationService $emailVerificationService): Response
    {
        $token = $request->query->get('token');

        if (!$token) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_login');
        }

        $user = $emailVerificationService->verifyEmail($token);

        if (!$user) {
            $this->addFlash('error', 'Verification link is invalid or has expired.');
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Email verified successfully! You can now login.');
        return $this->redirectToRoute('app_login');
    }
}
