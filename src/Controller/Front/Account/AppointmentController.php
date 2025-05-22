<?php

namespace App\Controller\Front\Account;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Service\User\CurrentUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mon-compte/mes-rendez-vous', name: 'account_appointment_')]
class AppointmentController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(AppointmentRepository $repo, CurrentUserService $currentUserService): Response
    {
        $user = $currentUserService->getCurrentUser();
        $appointments = $repo->findByUser($user);

        return $this->render('front/account/appointment/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(Appointment $appointment): Response
    {
        return $this->render('front/account/appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }
}
