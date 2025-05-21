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

#[Route('/mon-compte/mes-vehicules', name: 'account_vehicle_')]
class VehicleController extends AbstractController
{
    public function __construct(private readonly VehicleNormalizer $normalizer) {}

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

        return $this->render('front/account/vehicle/index.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }


    #[Route('/nouveau', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em, VehicleNormalizer $normalizer): Response
    {
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