<?php

namespace App\Controller;

use App\Entity\Productss;
use App\Form\ProductssType;
use App\Repository\ProductssRepository;
use App\Service\ActivityLogService;
use App\Service\LiveRevisionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/productss')]
final class ProductssController extends AbstractController
{
    #[Route(name: 'app_productss_index', methods: ['GET'])]
    public function index(ProductssRepository $productssRepository): Response
    {
        return $this->render('productss/index.html.twig', [
            'productsses' => $productssRepository->findAll(),
        ]);
    }


    #[Route('/new', name: 'app_productss_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogService $activityLogService, LiveRevisionService $liveRevisionService): Response
    {
        $productss = new Productss();
        $form = $this->createForm(ProductssType::class, $productss);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imagefilename')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $uploadDirectory = (string) $this->getParameter('images_directory');
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                    $this->addFlash('error', 'Product image upload directory is not available yet. Please try again in a moment.');

                    return $this->render('productss/new.html.twig', [
                        'productss' => $productss,
                        'form' => $form,
                    ]);
                }

                try {
                    $imageFile->move($uploadDirectory, $newFilename);
                } catch (FileException) {
                    $this->addFlash('error', 'Product image upload failed. Please try again with the same image or save the product without an image first.');

                    return $this->render('productss/new.html.twig', [
                        'productss' => $productss,
                        'form' => $form,
                    ]);
                }

                $productss->setImageFilename($newFilename);
            }
        
        // Set the creator (for staff ownership tracking)
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $productss->setCreatedBy($user);

            $entityManager->persist($productss);
            $entityManager->flush();

        // Log the product creation
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logCreate($user, 'Product', $productss->getName(), $productss->getId());
            }

            $liveRevisionService->bump(LiveRevisionService::PRODUCTS);

            return $this->redirectToRoute('app_productss_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('productss/new.html.twig', [
            'productss' => $productss,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_productss_show', methods: ['GET'])]
    public function show(Productss $productss): Response
    {
        return $this->render('productss/show.html.twig', [
            'productss' => $productss,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_productss_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function edit(Request $request, Productss $productss, EntityManagerInterface $entityManager, ActivityLogService $activityLogService, SluggerInterface $slugger, LiveRevisionService $liveRevisionService): Response
    {
        $form = $this->createForm(ProductssType::class, $productss);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imagefilename')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $uploadDirectory = (string) $this->getParameter('images_directory');
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                    $this->addFlash('error', 'Product image upload directory is not available yet. Please try again in a moment.');

                    return $this->render('productss/edit.html.twig', [
                        'productss' => $productss,
                        'form' => $form,
                    ]);
                }

                try {
                    $imageFile->move($uploadDirectory, $newFilename);
                } catch (FileException) {
                    $this->addFlash('error', 'Product image upload failed. Please try again with the same image or save the product without changing the image.');

                    return $this->render('productss/edit.html.twig', [
                        'productss' => $productss,
                        'form' => $form,
                    ]);
                }

                $productss->setImageFilename($newFilename);
            }

            $entityManager->flush();

            // Log the product update
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logUpdate($user, 'Product', $productss->getName(), $productss->getId());
            }

            $liveRevisionService->bump(LiveRevisionService::PRODUCTS);

            return $this->redirectToRoute('app_productss_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('productss/edit.html.twig', [
            'productss' => $productss,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_productss_delete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function delete(Request $request, Productss $productss, EntityManagerInterface $entityManager, ActivityLogService $activityLogService, LiveRevisionService $liveRevisionService): Response
    {
        if ($this->isCsrfTokenValid('delete' . $productss->getId(), $request->getPayload()->getString('_token'))) {
            // Check if product has related stocks
            if ($productss->getStocks()->count() > 0) {
                $this->addFlash('error', 'Cannot delete this product because it has related stock records. Please delete the stock records first.');
                return $this->redirectToRoute('app_productss_show', ['id' => $productss->getId()], Response::HTTP_SEE_OTHER);
            }

            // Store product info before deletion
            $productName = $productss->getName();
            $productId = $productss->getId();

            $entityManager->remove($productss);
            $entityManager->flush();

            // Log the product deletion
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $activityLogService->logDelete($user, 'Product', $productName, $productId);
            }

            $liveRevisionService->bump(LiveRevisionService::PRODUCTS);

            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute('app_productss_index', [], Response::HTTP_SEE_OTHER);
    }
}
