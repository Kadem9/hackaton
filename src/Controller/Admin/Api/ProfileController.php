<?php

namespace App\Controller\Admin\Api;

use App\Helper\JsonHelper;
use App\Service\User\CurrentUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/api/profile', name: 'admin_api_profile_')]
class ProfileController extends AbstractController
{
    public function __construct(private readonly CurrentUserService $currentUserService)
    {
    }

    #[Route('/theme', name: 'theme', methods: ['POST'])]
    public function theme(Request $request): Response
    {
        $json = new JsonHelper($request->getContent());
        $this->currentUserService->updateTheme($json->get('theme'));
        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}