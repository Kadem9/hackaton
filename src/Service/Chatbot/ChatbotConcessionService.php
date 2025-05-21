<?php

namespace App\Service\Chatbot;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

readonly class ChatbotConcessionService
{
    public function __construct(private ConcessionLocatorService $locator) {}

    public function handleLocation(mixed $input, Request $request): JsonResponse
    {
        if (!$input) {
            return new JsonResponse([
                'step' => 'ask_location',
                'message' => "Merci de saisir une ville valide.",
                'type' => 'text'
            ]);
        }

        $results = $this->locator->findClosest($input);

        if (empty($results)) {
            return new JsonResponse([
                'step' => 'ask_location',
                'message' => "Je n’ai pas trouvé de garage à proximité. Pouvez-vous réessayer avec une autre adresse ?",
                'type' => 'text'
            ]);
        }

        return new JsonResponse([
            'step' => 'choose_garage',
            'message' => "Voici les garages les plus proches de vous :",
            'type' => 'checkbox',
            'options' => array_map(
                fn($g) => "{$g['name']} – {$g['address']} ({$g['zipcode']} {$g['city']})",
                $results
            ),
            'data' => ['garages' => $results]
        ]);
    }

    public function handleChooseGarage(mixed $input, Request $request): JsonResponse
    {
        $session = $request->getSession();

        if (empty($input)) {
            return new JsonResponse([
                'step' => 'ask_reminder',
                'message' => "Souhaitez-vous qu'on vous appelle pour fixer le rendez-vous ?",
                'type' => 'confirm'
            ]);
        }

        $selected = is_array($input) ? $input[0] : $input;
        $session->set('chatbot_selected_garage', $selected);

        return new JsonResponse([
            'step' => 'ask_date_type',
            'message' => "Souhaitez-vous choisir une date précise ou le premier créneau disponible ?",
            'type' => 'confirm'
        ]);
    }

}
