<?php

namespace App\Normalizer;

use App\Entity\Vehicle;

class VehicleNormalizer
{
    public function normalize(Vehicle $vehicle): void
    {
        if ($vehicle->getModel()) {
            $vehicle->setModel(strtoupper($vehicle->getModel()));
        }

        if ($vehicle->getBrand()) {
            $vehicle->setBrand(strtoupper($vehicle->getBrand()));
        }

        if ($vehicle->getImmatriculation()) {
            $vehicle->setImmatriculation(
                strtoupper(str_replace('-', '', $vehicle->getImmatriculation()))
            );
        }

        if ($vehicle->getMileage() !== null) {
            $vehicle->setMileage((int)preg_replace('/\D/', '', (string)$vehicle->getMileage()));
        }
    }
}
