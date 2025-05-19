<?php

namespace App\Service\User;

use App\Entity\UserPreference;
use App\Repository\UserPreferenceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserPreferenceService
{
    public function __construct(private UserPreferenceRepository $repository, private readonly UserRepository $userRepository, private readonly EntityManagerInterface $manager)
    {
    }

    public function getPreference(int $userId, string $preferenceKey): ?array
    {
        $preference = $this->getStringPreference($userId, $preferenceKey);
        if ($preference) {
            return json_decode($preference, true);
        }
        return null;
    }

    public function getStringPreference(int $userId, string $preferenceKey): ?string
    {
        $preference = $this->repository->findOneBy(['user' => $userId, 'preferenceKey' => $preferenceKey]);
        if ($preference) {
            return $preference->getPreference();
        }
        return null;
    }

    public function savePreference(int $userId, string $preferenceKey, string $preferenceValue): void
    {
        $preference = $this->repository->findOneBy(['user' => $userId, 'preferenceKey' => $preferenceKey]);
        if ($preference) {
            $preference->setPreference($preferenceValue);
        } else {
            $user = $this->userRepository->find($userId);
            $preference = (new UserPreference())
                ->setUser($user)
                ->setPreferenceKey($preferenceKey)
                ->setPreference($preferenceValue);

            $this->manager->persist($preference);
        }
        $this->manager->flush();
    }
}