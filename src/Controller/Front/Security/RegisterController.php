<?php

namespace App\Controller\Front\Security;

use App\Entity\Conductor;
use App\Entity\User;
use App\Form\RegistrationForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inscription', name: 'app_register_')]
class RegisterController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setEnabled(true);
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);

            $conductor = new Conductor();
            $conductor->setUser($user);

            $isConductor = $request->request->get('is_conductor');

            if ($isConductor) {
                $conductor->setFirstname($user->getFirstname());
                $conductor->setLastname($user->getLastname());
                $conductor->setPhone($user->getTel());
            } else {
                $conductor->setFirstname($request->request->get('firstname_conductor'));
                $conductor->setLastname($request->request->get('name_conductor'));
                $conductor->setPhone($request->request->get('tel_conductor'));
            }

            $entityManager->persist($conductor);
            $entityManager->flush();

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('front/security/register/index.html.twig', [
            'registrationForm' => $form,
        ]);
    }

}