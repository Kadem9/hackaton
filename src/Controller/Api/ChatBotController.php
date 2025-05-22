<?php


namespace App\Controller\Api;

use App\Repository\VehicleRepository;
use App\Service\Chatbot\ConcessionLocatorService;
use App\Service\Chatbot\GeminiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\Chatbot\PexelsService;


#[Route('/api/chatbot', name: 'api_chatbot_')]
class ChatBotController extends AbstractController
{
    public function __construct(
        private readonly VehicleRepository $vehicleRepository,
        private readonly GeminiService $geminiService,
        private readonly ConcessionLocatorService $concessionLocator,
        private readonly PexelsService $pexelsService, 
        private readonly string $fakeVehiclePath
    ) {}
    #[Route('/step', name: 'step', methods: ['POST'])]
    public function step(Request $request): JsonResponse
    {
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
            
                    $allImages = $this->pexelsService->searchImages($vehicle->getBrand() . ' ' . $vehicle->getModel());
                    $images = [];
                    
                    if (!empty($allImages)) {
                        $images[] = $allImages[0];
                    }                    
            
                    return $this->json([
                        'step' => 'confirm_vehicle',
                        'message' => $message,
                        'type' => 'confirm',
                        'data' => [
                            'source' => 'bdd',
                            'vehicle_id' => $vehicle->getId(),
                            'images' => $images
                        ]
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
                
                    $allImages = $this->pexelsService->searchImages($matched['marque'] . ' ' . $matched['modele']);
                    $images = [];
                
                    if (!empty($allImages)) {
                        $images[] = $allImages[0]; // On ne garde que la première image
                    }
                
                    return $this->json([
                        'step' => 'confirm_vehicle',
                        'message' => $message,
                        'type' => 'confirm',
                        'data' => [
                            'source' => 'json',
                            'vehicle' => $matched,
                            'images' => $images
                        ]
                    ]);
                }
                
                

                return $this->json([
                    'step' => 'confirm_vehicle',
                    'message' => "Je n’ai pas trouvé cette plaque. Est-ce un véhicule de type PEUGEOT 2008 2020 ? (exemple)",
                    'type' => 'confirm',
                    'data' => ['source' => 'none']
                ]);


            case 'confirm_vehicle':
                if (strtolower($input) === 'oui') {
                    return $this->json([
                        'step' => 'ask_problem',
                        'message' => "Pouvez-vous me décrire le problème rencontré ?",
                        'type' => 'text'
                    ]);
                } else {
                    return $this->json([
                        'step' => 'ask_vehicle_name',
                        'message' => "Merci d’indiquer la marque et le modèle de votre véhicule.",
                        'type' => 'text'
                    ]);
                }
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



            default:
                return $this->json(['message' => 'Étape inconnue.'], 400);
        }
    }
}