<?php

namespace App\Controller\Front\Chatbot;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
    #[Route('/chatbot', name: 'fo_chatbot')]
    public function index(): Response
    {
        return $this->render('front/chatbot/index.html.twig');
    }
}