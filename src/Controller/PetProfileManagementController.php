<?php

namespace App\Controller;

use App\Entity\PetProfileManagement;
use App\Form\PetProfileManagementType;
use App\Repository\PetProfileManagementRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/pet/profile/management')]
final class PetProfileManagementController extends AbstractController
{
    #[Route(name: 'app_pet_profile_management_index', methods: ['GET'])]
    public function index(PetProfileManagementRepository $petProfileManagementRepository): Response
    {
        return $this->render('pet_profile_management/index.html.twig', [
            'pet_profile_managements' => $petProfileManagementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pet_profile_management_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogService $activityLogService): Response
    {
        $petProfileManagement = new PetProfileManagement();
        $form = $this->createForm(PetProfileManagementType::class, $petProfileManagement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('pet_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }

                $petProfileManagement->setImage($newFilename);
            }

            $entityManager->persist($petProfileManagement);
            $entityManager->flush();

            // Log the pet profile creation
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logCreate($user, 'Pet Profile', $petProfileManagement->getName(), $petProfileManagement->getId());
            }

            return $this->redirectToRoute('app_pet_profile_management_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pet_profile_management/new.html.twig', [
            'pet_profile_management' => $petProfileManagement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pet_profile_management_show', methods: ['GET'])]
    public function show(PetProfileManagement $petProfileManagement): Response
    {
        return $this->render('pet_profile_management/show.html.twig', [
            'pet_profile_management' => $petProfileManagement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pet_profile_management_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PetProfileManagement $petProfileManagement, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogService $activityLogService): Response
    {
        $form = $this->createForm(PetProfileManagementType::class, $petProfileManagement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('pet_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }

                $petProfileManagement->setImage($newFilename);
            }

            $entityManager->flush();

            // Log the pet profile update
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logUpdate($user, 'Pet Profile', $petProfileManagement->getName(), $petProfileManagement->getId());
            }

            return $this->redirectToRoute('app_pet_profile_management_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pet_profile_management/edit.html.twig', [
            'pet_profile_management' => $petProfileManagement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pet_profile_management_delete', methods: ['POST'])]
    public function delete(Request $request, PetProfileManagement $petProfileManagement, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$petProfileManagement->getId(), $request->getPayload()->getString('_token'))) {
            // Store pet profile info before deletion
            $petName = $petProfileManagement->getName();
            $petId = $petProfileManagement->getId();
            
            $entityManager->remove($petProfileManagement);
            $entityManager->flush();

            // Log the pet profile deletion
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logDelete($user, 'Pet Profile', $petName, $petId);
            }
        }

        return $this->redirectToRoute('app_pet_profile_management_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-pet-of-month', name: 'app_pet_profile_toggle_pet_of_month', methods: ['POST'])]
    public function togglePetOfTheMonth(Request $request, PetProfileManagement $petProfileManagement, EntityManagerInterface $entityManager, PetProfileManagementRepository $repository, ActivityLogService $activityLogService): Response
    {
        if ($this->isCsrfTokenValid('toggle-pet-of-month'.$petProfileManagement->getId(), $request->request->get('_token'))) {
            // If setting this pet as Pet of the Month, unset all others
            if (!$petProfileManagement->isPetOfTheMonth()) {
                $allPets = $repository->findBy(['isPetOfTheMonth' => true]);
                foreach ($allPets as $pet) {
                    $pet->setIsPetOfTheMonth(false);
                }
                $petProfileManagement->setIsPetOfTheMonth(true);
                $action = 'Set as Pet of the Month';
            } else {
                $petProfileManagement->setIsPetOfTheMonth(false);
                $action = 'Removed from Pet of the Month';
            }

            $entityManager->flush();

            // Log the action
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logUpdate($user, 'Pet of the Month', $petProfileManagement->getName() . ' - ' . $action, $petProfileManagement->getId());
            }

            $this->addFlash('success', $action . ': ' . $petProfileManagement->getName());
        }

        return $this->redirectToRoute('app_pet_profile_management_index', [], Response::HTTP_SEE_OTHER);
    }
}
