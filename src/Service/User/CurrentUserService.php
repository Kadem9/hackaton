<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class CurrentUserService
{
    public function __construct(private Security $security, private readonly EntityManagerInterface $em)
    {
    }

    public function getUserTheme() : string
    {
        $theme = "light";
        $user = $this->getCurrentUser();
        if($user && $user->getTheme()) {
            $theme = $user->getTheme();
        }
        return $theme;
    }

    public function getCurrentUser() : ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    public function getCurrentUsername() : string
    {
        $username = "";
        $user = $this->getCurrentUser();
        if($user) {
            $username = $user->getFirstname();
            $firstLetter = substr($user->getLastname(), 0, 1);
            if($firstLetter) {
                $username .= " " . $firstLetter . ".";
            }
        }
        return $username;
    }

    public function updateTheme(string $theme) : void
    {
        $user = $this->getCurrentUser();
        if($user) {
            $user->setTheme($theme);
            $this->em->flush();
        }
    }
}