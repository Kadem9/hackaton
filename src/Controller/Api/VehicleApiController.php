<?php

namespace App\Controller\Api;

use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/vehicles', name: 'api_vehicles_')]
class VehicleApiController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(VehicleRepository $vehicleRepo): JsonResponse
    {
        $user = $this->getUser();

        $vehicles = $vehicleRepo->createQueryBuilder('v')
            ->join('v.conductor', 'c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $data = [];

        foreach ($vehicles as $vehicle) {
            $data[] = [
                'id' => $vehicle->getId(),
                'brand' => $vehicle->getBrand(),
                'model' => $vehicle->getModel(),
                'immatriculation' => $vehicle->getImmatriculation(),
                'mileage' => $vehicle->getMileage(),
                'date_of_circulation' => $vehicle->getDateOfCirculation()?->format('Y-m-d'),
                'conductor' => [
                    'id' => $vehicle->getConductor()?->getId(),
                    'name' => $vehicle->getConductor()?->getFirstname() . ' ' . $vehicle->getConductor()?->getLastname()
                ]
            ];
        }

        return $this->json($data);
    }
}
