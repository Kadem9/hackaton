<?php

namespace App\Controller\Front\Account;

use App\Entity\Conductor;
use App\Form\Conductor\ConductorType;
use App\Repository\ConductorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/mon-compte/mes-conducteurs', name: 'account_conductor_')]
class ConductorController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ConductorRepository $conductorRepository): Response
    {
        $conductors = $conductorRepository->findBy(['user' => $this->getUser()]);
        return $this->render('front/account/conductor/index.html.twig', [
            'conductors' => $conductors,
        ]);
    }

    #[Route('/nouveau', name: 'new')]
    public function newConductor(Request $request, EntityManagerInterface $em): Response
    {
        $conductor = new Conductor();
        $form = $this->createForm(ConductorType::class, $conductor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $conductor->setUser($this->getUser());
            $em->persist($conductor);
            $em->flush();

            $this->addFlash('success', 'Conducteur ajouté avec succès.');
            return $this->redirectToRoute('account_conductor_index');
        }

        return $this->render('front/account/conductor/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit')]
    public function editConductor(Request $request, Conductor $conductor, EntityManagerInterface $em): Response
    {
        if ($conductor->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConductorType::class, $conductor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Conducteur modifié.');
            return $this->redirectToRoute('account_conductor_index');
        }

        return $this->render('front/account/conductor/edit.html.twig', [
            'form' => $form,
            'conductor' => $conductor,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function deleteConductor(Request $request, Conductor $conductor, EntityManagerInterface $em): Response
    {
        if ($conductor->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_conductor_' . $conductor->getId(), $request->request->get('_token'))) {
            $em->remove($conductor);
            $em->flush();
            $this->addFlash('success', 'Conducteur supprimé.');
        }

        return $this->redirectToRoute('account_conductor_index');
    }
}
