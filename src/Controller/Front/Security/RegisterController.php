<?php

namespace App\Controller\Front\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inscription', name: 'app_register_')]
class RegisterController extends AbstractController
{
    #[Route('/', name: 'index')]
    final public function index(): Response
    {

        return $this->render('front/security/register/index.html.twig', [
        ]);
    }
}