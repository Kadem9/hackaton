<?php

namespace App\Controller\Api;

use App\Service\Chatbot\ChatbotStepDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/chatbot', name: 'api_chatbot_')]
class ChatBotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotStepDispatcher $chatbotStepDispatcher,
    ) {}

    #[Route('/step', name: 'step', methods: ['POST'])]
    public function step(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $step = $data['step'] ?? 'start';
        $input = $data['input'] ?? null;

        return $this->chatbotStepDispatcher->dispatch($step, $input, $request, $this->getUser());


    }
}
