<?php

namespace App\Controller\Api\DialogFlow;

use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/plaque', name: 'api_plaque_')]
class DialogflowPlaqueController extends AbstractController
{
    #[Route('/check', name: 'check', methods: ['POST'])]
    public function check(Request $request, VehicleRepository $vehicleRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $plaque = strtoupper(str_replace('-', '', $data['plaque'] ?? ''));

        if (!$plaque) {
            return $this->json(['fulfillmentText' => 'Merci de fournir une plaque.'], 400);
        }

        // 🔍 Recherche en BDD
        $vehicle = $vehicleRepo->findOneBy(['immatriculation' => $plaque]);

        if ($vehicle) {
            $marque = $vehicle->getBrand();
            $modele = $vehicle->getModel();
            $annee = $vehicle->getDateOfCirculation()?->format('Y');

            return $this->json([
                'fulfillmentText' => "Est-ce bien une $marque $modele $annee ?",
                'outputContexts' => [[
                    'name' => $data['session'] . '/contexts/confirm_vehicle',
                    'lifespanCount' => 5,
                    'parameters' => [
                        'plaque' => $plaque,
                        'marque' => $marque,
                        'modele' => $modele,
                        'annee' => $annee,
                        'source' => 'local'
                    ]
                ]]
            ]);
        }

        $mock = [
            'brand' => 'Peugeot',
            'model' => '208',
            'year' => '2021'
        ];

        return $this->json([
            'fulfillmentText' => "Est-ce bien une {$mock['brand']} {$mock['model']} {$mock['year']} ?",
            'outputContexts' => [[
                'name' => $data['session'] . '/contexts/confirm_vehicle',
                'lifespanCount' => 5,
                'parameters' => [
                    'plaque' => $plaque,
                    'marque' => $mock['brand'],
                    'modele' => $mock['model'],
                    'annee' => $mock['year'],
                    'source' => 'api'
                ]
            ]]
        ]);
    }
}
