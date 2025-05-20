<?php


namespace App\Controller\Api\DialogFlow;

use App\Repository\VehicleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/webhook', name: 'dialogflow_webhook', methods: ['POST'])]
class DialogflowWebhookController extends AbstractController
{
    public function __construct(
        private VehicleRepository   $vehicleRepo,
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        $intent = $body['queryResult']['intent']['displayName'] ?? '';

        return match ($intent) {
            'Ask Plaque' => $this->handleAskPlaque($body),
            'Confirm Vehicle' => $this->handleConfirmVehicle($body),
            'Ask Driver Info' => $this->handleAskDriverInfo($body),
            'Collect Driver Info' => $this->handleCollectDriverInfo($body),
            'Ask Problem Intent' => $this->handleAskProblem($body),
            default => new JsonResponse([
                'fulfillmentText' => "Intent non reconnu : $intent",
            ]),
        };
    }

    private function handleAskPlaque(array $body): JsonResponse
    {
        $plaque = strtoupper(str_replace('-', '', $body['queryResult']['parameters']['plaque'] ?? ''));
        $vehicle = $this->vehicleRepo->findOneBy(['immatriculation' => $plaque]);

        if ($vehicle) {
            $marque = $vehicle->getBrand();
            $modele = $vehicle->getModel();
            $annee = $vehicle->getDateOfCirculation()?->format('Y');

            return $this->json([
                'fulfillmentText' => "Est-ce bien une $marque $modele $annee ?",
                'outputContexts' => [[
                    'name' => $body['session'] . '/contexts/confirm_vehicle',
                    'lifespanCount' => 5,
                    'parameters' => compact('plaque', 'marque', 'modele', 'annee')
                ]]
            ]);
        }

        // ici mettre l'api externe
        $mock = [
            'marque' => 'Peugeot',
            'modele' => '208',
            'annee' => '2021'
        ];

        return $this->json([
            'fulfillmentText' => "Est-ce bien une {$mock['marque']} {$mock['modele']} {$mock['annee']} ?",
            'outputContexts' => [[
                'name' => $body['session'] . '/contexts/confirm_vehicle',
                'lifespanCount' => 5,
                'parameters' => [
                    'plaque' => $plaque,
                    'marque' => $mock['marque'],
                    'modele' => $mock['modele'],
                    'annee' => $mock['annee'],
                    'source' => 'api'
                ]
            ]]
        ]);
    }

    private function handleConfirmVehicle(array $body): JsonResponse
    {
        $response = strtolower(trim($body['queryResult']['queryText'] ?? ''));

        if ($response === 'oui') {
            return $this->json([
                'fulfillmentText' => "Très bien. Quel est le problème avec votre véhicule ?",
                'outputContexts' => [[
                    'name' => $body['session'] . '/contexts/ask_problem',
                    'lifespanCount' => 5,
                ]]
            ]);
        }

        return $this->json([
            'fulfillmentText' => "D’accord. Quel est le modèle de votre véhicule ?",
            'outputContexts' => [[
                'name' => $body['session'] . '/contexts/ask_driver_info',
                'lifespanCount' => 5,
            ]]
        ]);
    }

    private function handleAskDriverInfo(array $body): JsonResponse
    {
        $response = strtolower(trim($body['queryResult']['queryText'] ?? ''));

        if ($response === 'oui') {
            return $this->json([
                'fulfillmentText' => "Très bien. Quel est le problème avec votre véhicule ?",
                'outputContexts' => [[
                    'name' => $body['session'] . '/contexts/ask_problem',
                    'lifespanCount' => 5,
                ]]
            ]);
        }

        return $this->json([
            'fulfillmentText' => "Pouvez-vous me donner vos informations (nom, prénom, téléphone) ?",
            'outputContexts' => [[
                'name' => $body['session'] . '/contexts/collect_driver_info',
                'lifespanCount' => 5,
            ]]
        ]);
    }

    private function handleCollectDriverInfo(array $body): JsonResponse
    {
        $params = $body['queryResult']['parameters'] ?? [];


        return $this->json([
            'fulfillmentText' => "Merci {${params['prenom']}}, quel est le problème avec votre véhicule ?",
            'outputContexts' => [[
                'name' => $body['session'] . '/contexts/ask_problem',
                'lifespanCount' => 5,
            ]]
        ]);
    }

    private function handleAskProblem(array $body): JsonResponse
    {
        $description = $body['queryResult']['queryText'] ?? '';

        $match = str_contains(strtolower($description), 'frein') ? 'Remplacement plaquettes de frein' : null;

        if (!$match) {
            return $this->json([
                'fulfillmentText' => "Je vous recommande un diagnostic. Voulez-vous qu’un expert vous rappelle ?",
                'outputContexts' => [[
                    'name' => $body['session'] . '/contexts/propose_rappel',
                    'lifespanCount' => 5,
                ]]
            ]);
        }

        return $this->json([
            'fulfillmentText' => "Merci, cela semble être : $match. Quel est le kilométrage actuel du véhicule ?",
            'outputContexts' => [[
                'name' => $body['session'] . '/contexts/ask_kilometrage',
                'lifespanCount' => 5,
                'parameters' => ['operation' => $match]
            ]]
        ]);
    }
}
