<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    #[Route('', name: 'app_user_management_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user_management/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_management_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ActivityLogService $activityLogService): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $fullName = $request->request->get('fullName');
            $password = $request->request->get('password');
            $role = $request->request->get('role');

            if ($this->isCsrfTokenValid('user_new', $request->request->get('_token'))) {
                // Check if email already exists
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $this->addFlash('error', 'Email already exists.');
                    return $this->redirectToRoute('app_user_management_new');
                }

                $user = new User();
                $user->setEmail($email);
                $user->setFullName($fullName);
                /** @var \App\Entity\User $currentUser */
                $currentUser = $this->getUser();
                $user->setCreatedBy($currentUser->getEmail());
                
                if ($role === 'admin') {
                    $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
                } elseif ($role === 'staff') {
                    $user->setRoles(['ROLE_STAFF', 'ROLE_USER']);
                } else {
                    $user->setRoles(['ROLE_USER']);
                }

                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                $entityManager->persist($user);
                $entityManager->flush();

                // Log the user creation
                $activityLogService->logUserCreate($currentUser, $user);

                $this->addFlash('success', 'User created successfully.');
                return $this->redirectToRoute('app_user_management_index');
            }
        }

        return $this->render('user_management/new.html.twig');
    }

    #[Route('/{id}', name: 'app_user_management_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user_management/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_management_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ActivityLogService $activityLogService): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $fullName = $request->request->get('fullName');
            $role = $request->request->get('role');
            $status = $request->request->get('status');
            $newPassword = $request->request->get('password');

            if ($this->isCsrfTokenValid('user_edit'.$user->getId(), $request->request->get('_token'))) {
                $user->setEmail($email);
                $user->setFullName($fullName);
                $user->setStatus($status);

                if ($role === 'admin') {
                    $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
                } elseif ($role === 'staff') {
                    $user->setRoles(['ROLE_STAFF', 'ROLE_USER']);
                } else {
                    $user->setRoles(['ROLE_USER']);
                }

                // Update password if provided
                if (!empty($newPassword)) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                }

                $entityManager->flush();

                // Log the user update
                /** @var \App\Entity\User $currentUser */
                $currentUser = $this->getUser();
                $activityLogService->logUserUpdate($currentUser, $user);

                $this->addFlash('success', 'User updated successfully.');
                return $this->redirectToRoute('app_user_management_index');
            }
        }

        return $this->render('user_management/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'app_user_management_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            // Prevent deleting yourself
            /** @var \App\Entity\User $currentUser */
            $currentUser = $this->getUser();
            if ($user->getId() === $currentUser->getId()) {
                $this->addFlash('error', 'You cannot delete your own account.');
                return $this->redirectToRoute('app_user_management_index');
            }

            // Store user info before deletion
            $deletedUserEmail = $user->getEmail();
            $deletedUserId = $user->getId();

            $entityManager->remove($user);
            $entityManager->flush();

            // Log the user deletion
            $activityLogService->logUserDelete($currentUser, $deletedUserEmail, $deletedUserId);

            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('app_user_management_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'app_user_management_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_status'.$user->getId(), $request->request->get('_token'))) {
            $newStatus = $user->getStatus() === 'active' ? 'disabled' : 'active';
            $user->setStatus($newStatus);
            $entityManager->flush();

            $this->addFlash('success', 'User status updated to ' . $newStatus . '.');
        }

        return $this->redirectToRoute('app_user_management_index');
    }
}
