<?php

namespace App\Controller\Admin\User;

use App\Entity\User;
use App\Form\User\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur créé avec succès.');
                return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
            }
            catch (\Exception $e) {
                if($e->getPrevious()->getCode() === 1062) {
                    $this->addFlash('error', 'Erreur lors de la création de l\'utilisateur. Email déjà utilisé.');
                    $form->get('email')->addError(new FormError('Email déjà utilisé.'));
                }
                else
                    $this->addFlash('error', 'Erreur lors de la création de l\'utilisateur.');
            }

        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['isEdit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if(!empty($plainPassword)) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur modifié avec succès.');
                return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
            }
            catch (\Exception $e) {
                if($e->getPrevious()->getCode() === 1062) {
                    $this->addFlash('error', 'Erreur lors de la création de l\'utilisateur. Email déjà utilisé.');
                    $form->get('email')->addError(new FormError('Email déjà utilisé.'));
                }
                else
                    $this->addFlash('error', 'Erreur lors de la création de l\'utilisateur.');
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
