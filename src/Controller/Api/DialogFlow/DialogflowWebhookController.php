<?php


namespace App\Controller\Api\DialogFlow;

use App\Repository\VehicleRepository;
use App\Service\Dialogflow\ConcessionLocator;
use App\Service\Dialogflow\DialogflowSessionStore;
use App\Service\Dialogflow\OperationSuggestion;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/webhook', name: 'dialogflow_webhook', methods: ['POST'])]
class DialogflowWebhookController extends AbstractController
{
    public function __construct(
        private readonly VehicleRepository $vehicleRepo,
        private readonly DialogflowSessionStore $store,
        private readonly OperationSuggestion $operationService,
        private readonly ConcessionLocator $concessionLocator,
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        $sessionId = $body['session'] ?? 'unknown-session';
        $intent = $body['queryResult']['intent']['displayName'] ?? '';

        return match ($intent) {
            'Ask Plaque' => $this->handleAskPlaque($body),
            'Confirm Vehicle' => $this->handleConfirmVehicle($body),
            'Ask Driver Info' => $this->handleAskDriverInfo($body),
            'Ask Problem' => $this->handleAskProblem($body),
            'Collect Driver Info' => $this->handleCollectDriverInfo($body),
            'Ask Kilometrage' => $this->handleAskKilometrage($body),
            'Verify Request' => $this->handleVerifyRequest($body),
            'Confirm Operation Intent' => $this->handleConfirmOperation($body),
            'Ask Location' => $this->handleAskLocation($body),
            default => new JsonResponse([
                'fulfillmentText' => "Intent non reconnu : $intent",
            ]),
        };
    }

    private function handleAskPlaque(array $body): JsonResponse
    {
        $sessionId = $body['session'];
        $plaque = strtoupper(str_replace('-', '', $body['queryResult']['parameters']['plaque'] ?? ''));
        $vehicle = $this->vehicleRepo->findOneBy(['immatriculation' => $plaque]);

        if ($vehicle) {
            $marque = $vehicle->getBrand();
            $modele = $vehicle->getModel();
            $annee = $vehicle->getDateOfCirculation()?->format('Y');

            $this->store->set($sessionId, 'plaque', $plaque);
            $this->store->set($sessionId, 'marque', $marque);
            $this->store->set($sessionId, 'modele', $modele);
            $this->store->set($sessionId, 'annee', $annee);
            $this->store->set($sessionId, 'source', 'bdd');

            return $this->json([
                'fulfillmentText' => "Est-ce bien une $marque $modele $annee ?",
                'outputContexts' => [[
                    'name' => $sessionId . '/contexts/confirm_vehicle',
                    'lifespanCount' => 5,
                    'parameters' => compact('plaque', 'marque', 'modele', 'annee')
                ]]
            ]);
        }

        $mock = ['marque' => 'Peugeot', 'modele' => '208', 'annee' => '2021'];

        $this->store->set($sessionId, 'plaque', $plaque);
        $this->store->set($sessionId, 'marque', $mock['marque']);
        $this->store->set($sessionId, 'modele', $mock['modele']);
        $this->store->set($sessionId, 'annee', $mock['annee']);
        $this->store->set($sessionId, 'source', 'api');

        return $this->json([
            'fulfillmentText' => "Est-ce bien une {$mock['marque']} {$mock['modele']} {$mock['annee']} ?",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/confirm_vehicle',
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
        $sessionId = $body['session'];
        $response = strtolower(trim($body['queryResult']['queryText'] ?? ''));

        $this->store->set($sessionId, 'confirm_vehicle', $response);

        if (in_array($response, ['oui', 'yes', 'c’est bien ça'])) {
            $this->store->set($sessionId, 'confirm_vehicle', 'yes');

            if ($this->store->get($sessionId, 'source') === 'api') {
                return $this->json([
                    'fulfillmentText' => "Êtes-vous le conducteur du véhicule ?",
                    'outputContexts' => [[
                        'name' => $sessionId . '/contexts/ask_driver_info',
                        'lifespanCount' => 5,
                    ]]
                ]);
            }

            return $this->json([
                'fulfillmentText' => "Très bien. Pouvez-vous m’expliquer le problème avec votre véhicule ?",
                'outputContexts' => [[
                    'name' => $sessionId . '/contexts/ask_problem',
                    'lifespanCount' => 5,
                ]]
            ]);
        }


        return $this->json([
            'fulfillmentText' => "D’accord. Pouvez-vous m’indiquer le modèle de votre véhicule ?",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/ask_driver_info',
                'lifespanCount' => 5,
            ]]
        ]);
    }

    private function handleAskDriverInfo(array $body): JsonResponse
    {
        $sessionId = $body['session'];
        $response = strtolower(trim($body['queryResult']['queryText'] ?? ''));

        $this->store->set($sessionId, 'is_driver', $response);

        if ($response === 'oui') {
            return $this->json([
                'fulfillmentText' => "Très bien. Quel est le problème avec votre véhicule ?",
                'outputContexts' => [[
                    'name' => $sessionId . '/contexts/ask_problem',
                    'lifespanCount' => 5,
                ]]
            ]);
        }

        return $this->json([
            'fulfillmentText' => "Pouvez-vous me donner vos informations (nom, prénom, téléphone) ?",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/collect_driver_info',
                'lifespanCount' => 5,
            ]]
        ]);
    }

    private function handleCollectDriverInfo(array $body): JsonResponse
    {
        $sessionId = $body['session'];
        $params = $body['queryResult']['parameters'] ?? [];

        $this->store->set($sessionId, 'prenom', $params['prenom'] ?? null);
        $this->store->set($sessionId, 'nom', $params['nom'] ?? null);
        $this->store->set($sessionId, 'tel', $params['tel'] ?? null);

        return $this->json([
            'fulfillmentText' => "Merci pour vos informations.",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/ask_problem',
                'lifespanCount' => 5,
            ]]
        ]);
    }

    private function handleAskProblem(array $body): JsonResponse
    {
        file_put_contents('/tmp/debug_ask_problem.log', "✅ Reçu : " . $body['queryResult']['queryText'] . "\n", FILE_APPEND);

        $description = $body['queryResult']['queryText'] ?? '';
        $sessionId = $body['session'];

        $suggested = $this->operationService->suggest($description);

        if ($suggested) {
            $this->store->set($sessionId, 'probleme', $description);
            $this->store->set($sessionId, 'operation', $suggested['operation_name']);

            return $this->json([
                'fulfillmentText' => "Merci, cela semble correspondre à : {$suggested['operation_name']}. Quel est le kilométrage de votre véhicule ?",
                'outputContexts' => [[
                    'name' => $sessionId . '/contexts/ask_kilometrage',
                    'lifespanCount' => 5,
                ]]
            ]);
        }

        return $this->json([
            'fulfillmentText' => "Je vous recommande un diagnostic complet. Souhaitez-vous qu’un expert vous rappelle ?",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/propose_rappel',
                'lifespanCount' => 5,
            ]]
        ]);
    }

    private function handleAskKilometrage(array $body): JsonResponse
    {
        $sessionId = $body['session'];
        $kilometrage = (int) ($body['queryResult']['parameters']['kilometrage'] ?? 0);

        $this->store->set($sessionId, 'kilometrage', $kilometrage);

        return $this->json([
            'fulfillmentText' => "Merci. Nous avons enregistré $kilometrage km. Souhaitez-vous qu’un conseiller vérifie les opérations proposées ?",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/verify_request',
                'lifespanCount' => 5
            ]]
        ]);
    }

    private function handleVerifyRequest(array $body): JsonResponse
    {
        $sessionId = $body['session'];
        $operation = $this->store->get($sessionId, 'operation') ?? 'non spécifiée';
        $kilometrage = $this->store->get($sessionId, 'kilometrage') ?? 'inconnu';

        return $this->json([
            'fulfillmentText' => "Nous avons enregistré l’opération : $operation pour $kilometrage km. Souhaitez-vous confirmer cette demande ou la modifier ?",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/ask_location',
                'lifespanCount' => 5
            ]]
        ]);
    }

    private function handleConfirmOperation(array $body): JsonResponse
    {
        $sessionId = $body['session'];

        return $this->json([
            'fulfillmentText' => "Parfait. Où souhaitez-vous effectuer l’intervention ? Merci d’indiquer votre ville et code postal.",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/ask_location',
                'lifespanCount' => 5,
            ]]
        ]);
    }

    private function handleAskLocation(array $body): JsonResponse
    {
        file_put_contents('/tmp/debug_location.log', json_encode($body, JSON_PRETTY_PRINT), FILE_APPEND);

        $sessionId = $body['session'];
        $params = $body['queryResult']['parameters'] ?? [];

        $ville = $params['ville']['city'] ?? ($params['ville'] ?? '');
        $codePostal = $params['code_postal'] ?? '';
        $lat = $params['ville']['lat'] ?? null;
        $lon = $params['ville']['lng'] ?? null;

        $this->store->set($sessionId, 'ville', $ville);
        $this->store->set($sessionId, 'code_postal', $codePostal);

        $concession = $this->concessionLocator->findNearest($codePostal, $lat, $lon);

        if ($concession) {
            $this->store->set($sessionId, 'concession', $concession);

            return $this->json([
                'fulfillmentText' => "Merci. La concession la plus proche est : {$concession['dealership_name']} à {$concession['city']} ({$concession['zipcode']}). Souhaitez-vous une date précise ou au plus tôt ?",
                'outputContexts' => [[
                    'name' => $sessionId . '/contexts/ask_date',
                    'lifespanCount' => 5,
                ]]
            ]);
        }

        return $this->json([
            'fulfillmentText' => "Aucune concession trouvée à proximité de $codePostal. Pouvez-vous réessayer ?",
            'outputContexts' => [[
                'name' => $sessionId . '/contexts/ask_location',
                'lifespanCount' => 5,
            ]]
        ]);
    }
}
