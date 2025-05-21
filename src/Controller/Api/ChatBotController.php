<?php

namespace App\Controller\Api;

use App\Repository\VehicleRepository;
use App\Service\Chatbot\ConcessionLocatorService;
use App\Service\Chatbot\GeminiService;
use App\Service\Chatbot\ChatbotRegistrationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/chatbot', name: 'api_chatbot_')]
class ChatBotController extends AbstractController
{
    public function __construct(
        private readonly VehicleRepository $vehicleRepository,
        private readonly GeminiService $geminiService,
        private readonly ConcessionLocatorService $concessionLocator,
        private readonly ChatbotRegistrationService $chatbotRegistration,
        private readonly string $fakeVehiclePath
    ) {}

    #[Route('/step', name: 'step', methods: ['POST'])]
    public function step(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $data = json_decode($request->getContent(), true);
        $step = $data['step'] ?? 'start';
        $input = $data['input'] ?? null;

        switch ($step) {
            case 'start':
                return $this->json([
                    'step' => 'ask_immatriculation',
                    'message' => "Bonjour 👋 ! Quelle est la plaque d'immatriculation de votre véhicule ?",
                    'type' => 'text'
                ]);

            case 'ask_immatriculation':
                $plate = strtoupper(str_replace(['-', ' '], '', $input));
                $vehicle = $this->vehicleRepository->findOneBy(['immatriculation' => $plate]);

                if ($vehicle) {
                    $message = sprintf(
                        "Est-ce bien une %s %s (%s) ?",
                        $vehicle->getBrand(),
                        $vehicle->getModel(),
                        $vehicle->getDateOfCirculation()?->format('Y')
                    );

                    return $this->json([
                        'step' => 'confirm_vehicle',
                        'message' => $message,
                        'type' => 'confirm',
                        'data' => ['source' => 'bdd', 'vehicle_id' => $vehicle->getId()]
                    ]);
                }

                $jsonPath = $this->fakeVehiclePath;
                $content = json_decode(file_get_contents($jsonPath), true);
                $vehicles = $content['vehicules'] ?? [];

                $matched = null;
                foreach ($vehicles as $item) {
                    $itemPlate = strtoupper(str_replace(['-', ' '], '', $item['immatriculation']));
                    if ($itemPlate === $plate) {
                        $matched = $item;
                        break;
                    }
                }

                if ($matched) {
                    $message = sprintf(
                        "Est-ce bien une %s %s (%s) ?",
                        strtoupper($matched['marque']),
                        strtoupper($matched['modele']),
                        (new \DateTime($matched['date_mise_en_circulation']))->format('Y')
                    );

                    return $this->json([
                        'step' => 'confirm_vehicle',
                        'message' => $message,
                        'type' => 'confirm',
                        'data' => ['source' => 'json', 'vehicle' => $matched]
                    ]);
                }

                $session->set('chatbot_immatriculation', $plate);
                return $this->json([
                    'step' => 'ask_brand',
                    'message' => "Nous ne trouvons pas cette plaque dans nos données. Quelle est la marque du véhicule ?",
                    'type' => 'text'
                ]);

            case 'ask_brand':
                $session->set('chatbot_brand', $input);
                return $this->json([
                    'step' => 'ask_model',
                    'message' => "Très bien. Quel est le modèle du véhicule ?",
                    'type' => 'text'
                ]);

            case 'ask_model':
                $session->set('chatbot_model', $input);
                return $this->json([
                    'step' => 'ask_mileage',
                    'message' => "Combien de kilomètres a-t-elle ?",
                    'type' => 'text'
                ]);

            case 'ask_mileage':
                $session->set('chatbot_mileage', preg_replace('/\D/', '', $input));
                return $this->json([
                    'step' => 'ask_circulation_date',
                    'message' => "Quelle est sa date de mise en circulation ? (ex: 20/10/2009)",
                    'type' => 'text'
                ]);

            case 'ask_circulation_date':
                try {
                    $date = \DateTime::createFromFormat('d/m/Y', $input);
                    if (!$date) throw new \Exception();
                    $session->set('chatbot_date', $date);
                } catch (\Exception) {
                    return $this->json([
                        'step' => 'ask_circulation_date',
                        'message' => "Format invalide. Merci d’indiquer une date au format JJ/MM/AAAA.",
                        'type' => 'text'
                    ]);
                }

                return $this->json([
                    'step' => 'ask_vin',
                    'message' => "Parfait. Quel est le numéro VIN du véhicule ?",
                    'type' => 'text'
                ]);

            case 'ask_vin':
                $session->set('chatbot_vin', $input);
                return $this->json([
                    'step' => 'ask_is_driver',
                    'message' => "Êtes-vous le conducteur du véhicule ?",
                    'type' => 'confirm'
                ]);

            case 'ask_is_driver':
                $session->set('chatbot_is_driver', strtolower($input));
                return $this->json([
                    'step' => 'ask_civility',
                    'message' => "Parfait. Vous êtes madame ou monsieur ?",
                    'type' => 'text'
                ]);

            case 'ask_civility':
                $session->set('chatbot_civility', ucfirst(strtolower($input)));
                return $this->json([
                    'step' => 'ask_email',
                    'message' => "Merci ! Quel est votre email ?",
                    'type' => 'text'
                ]);

            case 'ask_email':
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    return $this->json([
                        'step' => 'ask_email',
                        'message' => "Email invalide, merci de réessayer.",
                        'type' => 'text'
                    ]);
                }
                $session->set('chatbot_email', $input);
                return $this->json([
                    'step' => 'ask_user_type',
                    'message' => "Êtes-vous un particulier ou un professionnel ?",
                    'type' => 'text'
                ]);

            case 'ask_user_type':
                $session->set('chatbot_type', strtolower($input));
                return $this->json([
                    'step' => 'ask_name',
                    'message' => "Parfait ! Merci de me donner votre nom et prénom.",
                    'type' => 'text'
                ]);

            case 'ask_name':
                $parts = explode(' ', $input);
                if (count($parts) < 2) {
                    return $this->json([
                        'step' => 'ask_name',
                        'message' => "Merci d’indiquer nom + prénom.",
                        'type' => 'text'
                    ]);
                }

                $session->set('chatbot_lastname', $parts[0]);
                $session->set('chatbot_firstname', $parts[1]);

                return $this->json([
                    'step' => 'ask_phone',
                    'message' => "Super, quel est votre numéro de téléphone ?",
                    'type' => 'text'
                ]);

            case 'ask_phone':
                $session->set('chatbot_phone', $input);
                return $this->json([
                    'step' => 'ask_password',
                    'message' => "Top {$session->get('chatbot_firstname')} ! Définissez un mot de passe.",
                    'type' => 'text'
                ]);

            case 'ask_password':
                $session->set('chatbot_password', $input);

                $this->chatbotRegistration->register(
                    $session->get('chatbot_email'),
                    $session->get('chatbot_password'),
                    $session->get('chatbot_firstname'),
                    $session->get('chatbot_lastname'),
                    $session->get('chatbot_phone'),
                    $session->get('chatbot_immatriculation'),
                    $session->get('chatbot_brand'),
                    $session->get('chatbot_model'),
                    $session->get('chatbot_date')
                );

                return $this->json([
                    'step' => 'ask_problem',
                    'message' => "Super {$session->get('chatbot_firstname')}, votre compte et véhicule sont désormais référencés chez nous. Quel est le problème avec votre véhicule ?",
                    'type' => 'text'
                ]);


            case 'confirm_vehicle':
                if (strtolower($input) === 'oui') {
                    if (($data['data']['source'] ?? null) === 'none' && !$this->getUser()) {
                        return $this->json([
                            'step' => 'create_account',
                            'message' => "Nous allons créer votre compte. Quel est votre email ?",
                            'type' => 'text'
                        ]);
                    }

                    return $this->json([
                        'step' => 'ask_problem',
                        'message' => "Pouvez-vous me décrire le problème rencontré ?",
                        'type' => 'text'
                    ]);
                }

                return $this->json([
                    'step' => 'ask_vehicle_name',
                    'message' => "Merci d’indiquer la marque et le modèle de votre véhicule.",
                    'type' => 'text'
                ]);

            case 'create_account':
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    return $this->json([
                        'step' => 'create_account',
                        'message' => "Merci d'indiquer un email valide.",
                        'type' => 'text'
                    ]);
                }

                $session->set('chatbot_email', $input);

                return $this->json([
                    'step' => 'create_password',
                    'message' => "Quel mot de passe souhaitez-vous utiliser ?",
                    'type' => 'text'
                ]);

            case 'create_password':
                if (strlen($input) < 6) {
                    return $this->json([
                        'step' => 'create_password',
                        'message' => "Le mot de passe doit contenir au moins 6 caractères.",
                        'type' => 'text'
                    ]);
                }

                $session->set('chatbot_password', $input);

                return $this->json([
                    'step' => 'create_name',
                    'message' => "Merci d'indiquer votre prénom, nom et téléphone (ex: Sarah Dupont 0600000000)",
                    'type' => 'text'
                ]);

            case 'create_name':
                $parts = explode(' ', $input);
                if (count($parts) < 3) {
                    return $this->json([
                        'step' => 'create_name',
                        'message' => "Format attendu : prénom nom téléphone",
                        'type' => 'text'
                    ]);
                }

                $firstname = $parts[0];
                $lastname = $parts[1];
                $phone = $parts[2];

                // Création
                $user = $this->chatbotRegistration->register(
                    $session->get('chatbot_email'),
                    $session->get('chatbot_password'),
                    $firstname,
                    $lastname,
                    $phone,
                    'XX-XXX-XX', // exemple, ou à stocker dans session
                    'INCONNU',
                    'INCONNU'
                );

                return $this->json([
                    'step' => 'ask_problem',
                    'message' => "Votre compte a bien été créé. Pouvez-vous me décrire le problème rencontré ?",
                    'type' => 'text'
                ]);

            case 'ask_problem':
                if (!$input) {
                    return $this->json([
                        'step' => 'ask_problem',
                        'message' => 'Merci de décrire brièvement le problème.',
                        'type' => 'text'
                    ]);
                }

                $operations = $this->geminiService->analyzeProblem($input);
                $operations = array_filter(array_map('trim', $operations));

                if (empty($operations)) {
                    return $this->json([
                        'step' => 'confirm_diagnostic',
                        'message' => "Je vous recommande un diagnostic complet. Souhaitez-vous en programmer un ?",
                        'type' => 'confirm'
                    ]);
                }

                return $this->json([
                    'step' => 'choose_operations',
                    'message' => "Voici les opérations suggérées :",
                    'type' => 'checkbox',
                    'options' => $operations,
                    'data' => ['problem' => $input]
                ]);

            case 'choose_operations':
                if (empty($input)) {
                    return $this->json([
                        'step' => 'ask_reminder',
                        'message' => "Souhaitez-vous qu'on vous rappelle pour clarifier votre besoin ?",
                        'type' => 'confirm'
                    ]);
                }

                return $this->json([
                    'step' => 'ask_location',
                    'message' => "Merci ! Dans quelle ville se trouve votre véhicule ?",
                    'type' => 'text',
                    'data' => ['operations' => $input]
                ]);

            case 'ask_location':
                if (!$input) {
                    return $this->json([
                        'step' => 'ask_location',
                        'message' => "Merci de saisir une ville valide.",
                        'type' => 'text'
                    ]);
                }

                $results = $this->concessionLocator->findClosest($input);

                if (empty($results)) {
                    return $this->json([
                        'step' => 'ask_location',
                        'message' => "Je n’ai pas trouvé de garage à proximité. Pouvez-vous réessayer avec une autre adresse ?",
                        'type' => 'text'
                    ]);
                }

                return $this->json([
                    'step' => 'choose_garage',
                    'message' => "Voici les garages les plus proches de vous :",
                    'type' => 'checkbox',
                    'options' => array_map(fn($g) => "{$g['name']} – {$g['address']} ({$g['zipcode']} {$g['city']})", $results),
                    'data' => ['garages' => $results]
                ]);

            case 'choose_garage':
                if (empty($input)) {
                    return $this->json([
                        'step' => 'ask_reminder',
                        'message' => "Souhaitez-vous qu'on vous appelle pour fixer le rendez-vous ?",
                        'type' => 'confirm'
                    ]);
                }

                return $this->json([
                    'step' => 'ask_date_type',
                    'message' => "Souhaitez-vous choisir une date précise ou le premier créneau disponible ?",
                    'type' => 'confirm',
                    'data' => ['selected_garage' => $input]
                ]);

            case 'ask_date_type':
                if (strtolower($input) === 'oui') {
                    return $this->json([
                        'step' => 'choose_date',
                        'message' => "Merci ! Veuillez indiquer la date souhaitée (ex: 24/05/2025).",
                        'type' => 'text'
                    ]);
                }

                $slots = [
                    'Vendredi 24/05 à 10h30',
                    'Vendredi 24/05 à 15h00',
                    'Lundi 27/05 à 09h00'
                ];

                return $this->json([
                    'step' => 'confirm_slot',
                    'message' => "Voici les créneaux disponibles :",
                    'type' => 'checkbox',
                    'options' => $slots,
                    'data' => ['mode' => 'auto']
                ]);

            default:
                return $this->json(['message' => 'Étape inconnue.'], 400);
        }
    }
}
