<?php

namespace App\Controller;

use App\Entity\PetOwners;
use App\Form\PetOwnersType;
use App\Repository\PetOwnersRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pet-owners')]
final class PetOwnersController extends AbstractController
{
    #[Route(name: 'app_pet_owners_index', methods: ['GET'])]
    public function index(PetOwnersRepository $petOwnersRepository): Response
    {
        return $this->render('pet_owners/index.html.twig', [
            'pet_owners' => $petOwnersRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pet_owners_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        $petOwner = new PetOwners();
        $form = $this->createForm(PetOwnersType::class, $petOwner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($petOwner);
            $entityManager->flush();

            // Log the pet owner creation
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logCreate($user, 'Pet Owner', $petOwner->getFirstName() . ' ' . $petOwner->getLastName(), $petOwner->getId());
            }

            return $this->redirectToRoute('app_pet_owners_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pet_owners/new.html.twig', [
            'pet_owner' => $petOwner,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pet_owners_show', methods: ['GET'])]
    public function show(PetOwners $petOwner): Response
    {
        return $this->render('pet_owners/show.html.twig', [
            'pet_owner' => $petOwner,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pet_owners_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PetOwners $petOwner, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        $form = $this->createForm(PetOwnersType::class, $petOwner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Log the pet owner update
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logUpdate($user, 'Pet Owner', $petOwner->getFirstName() . ' ' . $petOwner->getLastName(), $petOwner->getId());
            }

            return $this->redirectToRoute('app_pet_owners_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pet_owners/edit.html.twig', [
            'pet_owner' => $petOwner,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pet_owners_delete', methods: ['POST'])]
    public function delete(Request $request, PetOwners $petOwner, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$petOwner->getId(), $request->getPayload()->getString('_token'))) {
            // Store pet owner info before deletion
            $petOwnerName = $petOwner->getFirstName() . ' ' . $petOwner->getLastName();
            $petOwnerId = $petOwner->getId();
            
            $entityManager->remove($petOwner);
            $entityManager->flush();

            // Log the pet owner deletion
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logDelete($user, 'Pet Owner', $petOwnerName, $petOwnerId);
            }
        }

        return $this->redirectToRoute('app_pet_owners_index', [], Response::HTTP_SEE_OTHER);
    }
}
