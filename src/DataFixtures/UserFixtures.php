<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = (new User())
            ->setFirstname('Antigone')
            ->setLastname('Web')
            ->setEmail('webteam@antigone.fr')
            ->setRoles(['ROLE_ADMIN'])
            ->setTheme('light');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'zaza'));
        $manager->persist($user);
        $manager->flush();
    }
}
