<?php

namespace App\Controller\Front\Account;

use App\Entity\Vehicle;
use App\Form\Vehicle\VehicleType;
use App\Normalizer\VehicleNormalizer;
use App\Repository\VehicleRepository;
use App\Service\User\CurrentUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Chatbot\PexelsService;

#[Route('/mon-compte/mes-vehicules', name: 'account_vehicle_')]
class VehicleController extends AbstractController
{
    public function __construct(
        private readonly VehicleNormalizer $normalizer,
        private readonly PexelsService $pexelsService
    ){}

    #[Route('/', name: 'index')]
    public function index(VehicleRepository $repo): Response
    {
        $user = $this->getUser();
    
        $vehicles = $repo->createQueryBuilder('v')
            ->join('v.conductor', 'c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    
        // Ajoute une image Pexels pour chaque véhicule
        $vehiclesWithImages = [];
        foreach ($vehicles as $vehicle) {
            $query = sprintf('%s %s car side view outdoor', $vehicle->getBrand(), $vehicle->getModel());
            $images = $this->pexelsService->searchImages($query, 1);
            $image = $images[0] ?? null;
    
            $vehiclesWithImages[] = [
                'vehicle' => $vehicle,
                'image' => $image,
            ];
        }
    
        return $this->render('front/account/vehicle/index.html.twig', [
            'vehiclesWithImages' => $vehiclesWithImages,
        ]);
    }

    #[Route('/nouveau', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em, VehicleNormalizer $normalizer, CurrentUserService $currentUserService): Response
    {
        $user = $currentUserService->getCurrentUser();
        if (!$user || count($user->getConductors()) === 0) {
            $this->addFlash('error', 'Vous devez d\'abord créer un conducteur.');
            return $this->redirectToRoute('account_conductor_new');
        }

        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $normalizer->normalize($vehicle);
            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule ajouté avec succès.');
            return $this->redirectToRoute('account_vehicle_index');
        }

        return $this->render('front/account/vehicle/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit')]
    public function edit(Request $request, Vehicle $vehicle, EntityManagerInterface $em, VehicleNormalizer $normalizer, CurrentUserService $currentUserService): Response
    {
        $user = $currentUserService->getCurrentUser();
        foreach ($user->getConductors() as $conductor) {
            if ($conductor->getUser() !== $user) {
                throw $this->createAccessDeniedException();
            }
        }

        $form = $this->createForm(VehicleType::class, $vehicle, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $normalizer->normalize($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule mis à jour.');
            return $this->redirectToRoute('account_vehicle_index');
        }

        return $this->render('front/account/vehicle/edit.html.twig', [
            'form' => $form,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $em, CurrentUserService $currentUserService): Response
    {
        $user = $currentUserService->getCurrentUser();
        foreach ($user->getConductors() as $conductor) {
            if ($conductor->getUser() !== $user) {
                throw $this->createAccessDeniedException();
            }
        }

        if ($this->isCsrfTokenValid('delete_vehicle_' . $vehicle->getId(), $request->request->get('_token'))) {
            $em->remove($vehicle);
            $em->flush();
            $this->addFlash('success', 'Véhicule supprimé.');
        }

        return $this->redirectToRoute('account_vehicle_index');
    }
}