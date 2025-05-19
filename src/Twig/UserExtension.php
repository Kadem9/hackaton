<?php

namespace App\Twig;

use App\Service\User\CurrentUserService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    public function __construct(private readonly CurrentUserService $currentUserService)
    {
    }

    public function getFunctions() : array
    {
        return [
            new TwigFunction('user_theme', [$this, 'userTheme']),
            new TwigFunction('current_username', [$this, 'currentUsername']),
        ];
    }
    public function userTheme() : string
    {
        return $this->currentUserService->getUserTheme();
    }

    public function currentUsername() : string
    {
        return $this->currentUserService->getCurrentUsername();
    }
}