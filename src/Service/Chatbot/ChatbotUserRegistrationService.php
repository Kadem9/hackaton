<?php

namespace App\Service\Chatbot;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

readonly class ChatbotUserRegistrationService
{
    public function __construct(
        private ChatbotRegistrationService $registrationService,
        private ChatbotSanitizerService $sanitizer,
    ) {}

    public function handleCivility(mixed $input, Request $request): JsonResponse
    {
        $request->getSession()->set('chatbot_civility', ucfirst(strtolower($input)));
        return new JsonResponse([
            'step' => 'ask_email',
            'message' => "Merci ! Quel est votre email ?",
            'type' => 'text'
        ]);
    }

    public function handleEmail(mixed $input, Request $request): JsonResponse
    {
        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'step' => 'ask_email',
                'message' => "Email invalide, merci de réessayer.",
                'type' => 'text'
            ]);
        }

        $request->getSession()->set('chatbot_email', $input);
        return new JsonResponse([
            'step' => 'ask_user_type',
            'message' => "Êtes-vous un particulier ou un professionnel ?",
            'type' => 'text'
        ]);
    }

    public function handleUserType(mixed $input, Request $request): JsonResponse
    {
        $request->getSession()->set('chatbot_type', strtolower($input));
        return new JsonResponse([
            'step' => 'ask_name',
            'message' => "Parfait ! Merci de me donner votre nom et prénom.",
            'type' => 'text'
        ]);
    }

    public function handleName(mixed $input, Request $request): JsonResponse
    {
        $parts = explode(' ', $input);
        if (count($parts) < 2) {
            return new JsonResponse([
                'step' => 'ask_name',
                'message' => "Merci d’indiquer nom + prénom.",
                'type' => 'text'
            ]);
        }

        $request->getSession()->set('chatbot_lastname', $parts[0]);
        $request->getSession()->set('chatbot_firstname', $parts[1]);

        return new JsonResponse([
            'step' => 'ask_phone',
            'message' => "Super, quel est votre numéro de téléphone ?",
            'type' => 'text'
        ]);
    }

    public function handlePhone(mixed $input, Request $request): JsonResponse
    {
        $phone = $this->sanitizer->extractPhone((string)$input);

        if (!$phone) {
            return new JsonResponse([
                'step' => 'ask_phone',
                'message' => "Numéro invalide. Merci d’indiquer au moins 10 chiffres.",
                'type' => 'text'
            ]);
        }

        $request->getSession()->set('chatbot_phone', $phone);

        return new JsonResponse([
            'step' => 'ask_password',
            'message' => "Top ! Définissez un mot de passe pour terminer l’inscription.",
            'type' => 'text'
        ]);
    }


    public function handlePassword(mixed $input, Request $request): JsonResponse
    {
        if (strlen($input) < 6) {
            return new JsonResponse([
                'step' => 'ask_password',
                'message' => "Le mot de passe doit contenir au moins 6 caractères.",
                'type' => 'text'
            ]);
        }

        $request->getSession()->set('chatbot_password', $input);

        return new JsonResponse([
            'step' => 'finalize_registration',
            'message' => "Merci ! Nous allons maintenant enregistrer votre compte.",
            'type' => 'confirm'
        ]);
    }

    public function handleFinalizeRegistration(Request $request): JsonResponse
    {
        $session = $request->getSession();

        $this->registrationService->register(
            $session->get('chatbot_email'),
            $session->get('chatbot_password'),
            $session->get('chatbot_firstname'),
            $session->get('chatbot_lastname'),
            $session->get('chatbot_phone'),
            $session->get('chatbot_immatriculation'),
            $session->get('chatbot_brand'),
            $session->get('chatbot_model'),
            $session->get('chatbot_date'),
            $session->get('chatbot_mileage'),
            $session->get('chatbot_vin'),
        );

        return new JsonResponse([
            'step' => 'ask_problem',
            'message' => "Super {$session->get('chatbot_firstname')}, votre compte et véhicule sont désormais référencés chez nous. Quel est le problème avec votre véhicule ?",
            'type' => 'text'
        ]);
    }
}
