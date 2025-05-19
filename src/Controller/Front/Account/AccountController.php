<?php

namespace App\Controller\Front\Account;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mon-compte', name: 'account_')]
class AccountController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('front/account/index.html.twig');
    }

    #[Route('/mes-rendez-vous', name: 'appointments')]
    public function appointments(): Response
    {
        return $this->render('front/account/appointments.html.twig');
    }
}