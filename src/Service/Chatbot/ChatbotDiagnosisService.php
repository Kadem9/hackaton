<?php

namespace App\Service\Chatbot;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

readonly class ChatbotDiagnosisService
{
    public function __construct(private GeminiService $geminiService) {}

    public function handleProblem(mixed $input, Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$input) {
            return new JsonResponse([
                'step' => 'ask_problem',
                'message' => 'Merci de décrire brièvement le problème.',
                'type' => 'text'
            ]);
        }

        $session->set('chatbot_problem', $input); 

        $operations = $this->geminiService->analyzeProblem($input);
        $operations = array_filter(array_map('trim', $operations));

        if (empty($operations)) {
            return new JsonResponse([
                'step' => 'confirm_diagnostic',
                'message' => "Je vous recommande un diagnostic complet. Souhaitez-vous en programmer un ?",
                'type' => 'confirm'
            ]);
        }

        return new JsonResponse([
            'step' => 'choose_operations',
            'message' => "Voici les opérations suggérées :",
            'type' => 'checkbox',
            'options' => $operations,
            'data' => ['problem' => $input]
        ]);
    }
    public function handleChooseOperations(mixed $input, Request $request): JsonResponse
    {
        if (empty($input)) {
            return new JsonResponse([
                'step' => 'ask_reminder',
                'message' => "Souhaitez-vous qu'on vous rappelle pour clarifier votre besoin ?",
                'type' => 'confirm'
            ]);
        }

        $session = $request->getSession();
        $session->set('chatbot_selected_operations', $input);


        return new JsonResponse([
            'step' => 'ask_location',
            'message' => "Merci ! Dans quelle ville se trouve votre véhicule ?",
            'type' => 'text'
        ]);
    }


}
