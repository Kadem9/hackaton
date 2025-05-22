<?php

namespace App\Service\Chatbot;

use App\Entity\User;
use App\Entity\Conductor;
use App\Entity\Vehicle;
use App\Normalizer\VehicleNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class ChatbotRegistrationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private VehicleNormalizer $vehicleNormalizer
    ) {}

    public function register(
        string $email,
        string $plainPassword,
        string $firstname,
        string $lastname,
        string $phone,
        string $immatriculation,
        string $brand,
        string $model,
        ?\DateTimeInterface $circulationDate = null,
        ?int $mileage = null,
        ?string $vin = null
    ): Vehicle {                                        // ← changed return type
        // création de l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setPlainPassword($plainPassword);
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setTel($phone);
        $user->setType('client');
        $user->setRoles(['ROLE_USER']);

        // création du conducteur
        $conductor = new Conductor();
        $conductor->setFirstname($firstname);
        $conductor->setLastname($lastname);
        $conductor->setPhone($phone);
        $conductor->setUser($user);

        // création du véhicule
        $vehicle = new Vehicle();
        $vehicle->setBrand($brand);
        $vehicle->setModel($model);
        $vehicle->setImmatriculation($immatriculation);
        $vehicle->setDateOfCirculation($circulationDate);
        $vehicle->setMileage($mileage);
        $vehicle->setVin($vin);
        $vehicle->setConductor($conductor);

        // normalisation
        $this->vehicleNormalizer->normalize($vehicle);

        // persistance en base
        $this->em->persist($user);
        $this->em->persist($conductor);
        $this->em->persist($vehicle);
        $this->em->flush();

        return $vehicle;                                // ← on renvoie le Vehicle créé
    }
}
