<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $fullName = $request->request->get('fullName');
            $email = $request->request->get('email');

            if ($this->isCsrfTokenValid('profile_edit', $request->request->get('_token'))) {
                $user->setFullName($fullName);
                $user->setEmail($email);

                $entityManager->flush();

                $this->addFlash('success', 'Profile updated successfully.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('currentPassword');
            $newPassword = $request->request->get('newPassword');
            $confirmPassword = $request->request->get('confirmPassword');

            if ($this->isCsrfTokenValid('change_password', $request->request->get('_token'))) {
                // Verify current password
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->redirectToRoute('app_profile_change_password');
                }

                // Validate new password
                if (strlen($newPassword) < 6) {
                    $this->addFlash('error', 'New password must be at least 6 characters long.');
                    return $this->redirectToRoute('app_profile_change_password');
                }

                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New passwords do not match.');
                    return $this->redirectToRoute('app_profile_change_password');
                }

                // Hash and update password
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $entityManager->flush();

                // Re-authenticate the user to prevent logout
                $security->login($user, 'form_login', 'main');

                $this->addFlash('success', 'Password changed successfully.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/change_password.html.twig');
    }
}
