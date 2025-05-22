<?php

// src/Controller/Front/VehicleController.php

namespace App\Controller\Front;

use App\Entity\Vehicle;
use App\Service\Chatbot\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VehicleController extends AbstractController
{
    #[Route('/mon-compte/mes-vehicules/{id}/recommandations', name: 'vehicle_recommendations')]
    public function recommendations(Vehicle $vehicle, GeminiService $geminiService): Response
    {
        $recommendations = $geminiService->getMaintenanceRecommendations([
            'brand' => $vehicle->getBrand(),
            'model' => $vehicle->getModel(),
            'mileage' => $vehicle->getMileage(),
            'circulation_year' => $vehicle->getDateOfCirculation()?->format('Y') ?? 'inconnue',
            'last_visit' => '2024-11-20' // mocked for now
        ]);

        return $this->render('front/account/vehicle/recommendations.html.twig', [
            'vehicle' => $vehicle,
            'recommendations' => $recommendations,
        ]);
    }
}
