<?php

namespace App\Controller\Api;

use App\Repository\ConductorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/conductors', name: 'api_conductors_')]
class ConductorApiController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(ConductorRepository $repo): JsonResponse
    {
        $user = $this->getUser();

        $conductors = $repo->findBy(['user' => $user]);

        $data = array_map(static function ($c) {
            return [
                'id' => $c->getId(),
                'firstname' => $c->getFirstname(),
                'lastname' => $c->getLastname(),
                'phone' => $c->getPhone(),
            ];
        }, $conductors);

        return $this->json($data);
    }
}
