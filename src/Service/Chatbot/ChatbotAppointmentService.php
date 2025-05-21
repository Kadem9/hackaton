<?php

namespace App\Service\Chatbot;

use App\Entity\Appointment;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

readonly class ChatbotAppointmentService
{
    public function __construct(private ChatbotAvailabilityService $availabilityService, private VehicleRepository $vehicleRepository, private EntityManagerInterface $em)
    {
    }

    public function handleDateType(string $input, Request $request): JsonResponse
    {
        $session = $request->getSession();

        if (strtolower($input) === 'oui') {
            return new JsonResponse([
                'step' => 'choose_date',
                'message' => "Merci ! Veuillez indiquer la date souhaitée (ex: 24/05/2025).",
                'type' => 'text'
            ]);
        }

        $selected = $session->get('chatbot_selected_garage');
        if (!$selected || !preg_match('/^(.*?) – (.*?) \((\d{5}) (.*?)\)$/u', $selected, $matches)) {
            return new JsonResponse([
                'step' => 'ask_date_type',
                'message' => "Impossible d’extraire le garage sélectionné.",
                'type' => 'text'
            ]);
        }

        [$full, $name, $address, $zip, $city] = $matches;

        $available = $this->availabilityService->getAvailableDates($name, $city);
        if (empty($available)) {
            return new JsonResponse([
                'step' => 'choose_date',
                'message' => "Aucun créneau n’est disponible actuellement. Souhaitez-vous choisir une date ?",
                'type' => 'confirm'
            ]);
        }

        return new JsonResponse([
            'step' => 'confirm_slot',
            'message' => "Voici les créneaux disponibles :",
            'type' => 'checkbox',
            'options' => $available
        ]);
    }


    public function handleChooseDate(mixed $input, Request $request): JsonResponse
    {
        return new JsonResponse([
            'step' => 'confirm_slot',
            'message' => "Parfait, nous avons bien noté la date souhaitée : $input",
            'type' => 'checkbox',
            'options' => [$input],
            'data' => ['mode' => 'manual']
        ]);
    }

    public function handleConfirmSlot(mixed $input, Request $request): JsonResponse
    {
        $session = $request->getSession();

        if (is_array($input)) {
            $input = $input[0] ?? '';
        }

        $input = str_replace(['—', '–'], '-', (string)$input);

        if (!preg_match('/^([A-Za-zéèûîàç]+) (\d{2}) (\w+) - (\d+) créneaux$/u', $input, $matches)) {
            return new JsonResponse([
                'step' => 'choose_date',
                'message' => "Le format du créneau est invalide.",
                'type' => 'text'
            ]);
        }

        [$full, $dayName, $day, $monthFr, $count] = $matches;

        $monthMap = [
            'janvier' => 'January',
            'février' => 'February',
            'mars' => 'March',
            'avril' => 'April',
            'mai' => 'May',
            'juin' => 'June',
            'juillet' => 'July',
            'août' => 'August',
            'septembre' => 'September',
            'octobre' => 'October',
            'novembre' => 'November',
            'décembre' => 'December',
        ];

        $monthEn = $monthMap[strtolower($monthFr)] ?? null;
        if (!$monthEn) {
            return new JsonResponse([
                'step' => 'choose_date',
                'message' => "Mois invalide, veuillez réessayer.",
                'type' => 'text'
            ]);
        }

        $year = (new \DateTime())->format('Y');
        $dateString = "$day $monthEn $year";
        $date = \DateTime::createFromFormat('d F Y', $dateString);

        if (!$date) {
            return new JsonResponse([
                'step' => 'choose_date',
                'message' => "Impossible de traiter la date choisie.",
                'type' => 'text'
            ]);
        }

        $session->set('chatbot_appointment_date', $date);

        $vehicle = $session->get('chatbot_brand') . ' ' . $session->get('chatbot_model') . ' (' . $session->get('chatbot_immatriculation') . ')';
        $garage = $session->get('chatbot_selected_garage') ?? 'Non précisé';
        $problem = $session->get('chatbot_problem') ?? 'Problème non précisé';

        $recap = "<b>🚗 Véhicule :</b> {$vehicle}<br/>" .
            "<b>📍 Garage :</b> {$garage}<br/>" .
            "<b>📅 Créneau :</b> {$input}<br/>" .
            "<b>🛠 Problème :</b> {$problem}<br/><br/>" .
            "Souhaitez-vous confirmer ce rendez-vous ?";

        return new JsonResponse([
            'step' => 'confirm_final',
            'message' => $recap,
            'type' => 'confirm'
        ]);
    }


    public function handleFinalConfirmation(string $input, Request $request): JsonResponse
    {
        $session = $request->getSession();

        if (strtolower($input) !== 'oui') {
            return new JsonResponse([
                'step' => 'ask_reminder',
                'message' => "Souhaitez-vous qu’on vous rappelle pour fixer le rendez-vous à un autre moment ?",
                'type' => 'confirm'
            ]);
        }

        $firstname = $session->get('chatbot_firstname');
        $lastname = $session->get('chatbot_lastname');
        $vehicle = strtoupper($session->get('chatbot_brand') . ' ' . $session->get('chatbot_model') . ' (' . $session->get('chatbot_immatriculation') . ')');
        $problem = $session->get('chatbot_problem') ?? 'Problème non précisé';
        $slot = $session->get('chatbot_slot');
        $garage = $session->get('chatbot_selected_garage');

        $message = <<<HTML
                <b>🚗 Véhicule :</b> {$vehicle}<br/>
                <b>📍 Garage :</b> {$garage}<br/>
                <b>📅 Créneau :</b> {$slot}<br/>
                <b>🛠 Problème :</b> {$problem}<br/><br/>
                Souhaitez-vous confirmer ce rendez-vous ?
                HTML;

        return new JsonResponse([
            'step' => 'confirm_appointment',
            'message' => $message,
            'type' => 'confirm'
        ]);
    }

    public function handleConfirmAppointment(string $input, Request $request): JsonResponse
    {
        if (strtolower($input) !== 'oui') {
            return new JsonResponse([
                'step' => 'ask_reminder',
                'message' => "Souhaitez-vous qu’un conseiller vous rappelle pour fixer le rendez-vous ?",
                'type' => 'confirm'
            ]);
        }

        $session = $request->getSession();

        $vehicleId = $session->get('chatbot_vehicle_id');
        $dateString = $session->get('chatbot_slot');

        try {
            $date = \DateTime::createFromFormat('Y-m-d \à H\hi', $dateString);
            if (!$date) {
                return new JsonResponse([
                    'step' => 'error',
                    'message' => "Le format du créneau est invalide.",
                    'type' => 'text'
                ]);
            }
        } catch (\Exception) {
            return new JsonResponse([
                'step' => 'error',
                'message' => "Impossible d’interpréter la date sélectionnée.",
                'type' => 'text'
            ]);
        }

        $vehicle = $this->vehicleRepository->find($vehicleId);
        if (!$vehicle) {
            return new JsonResponse([
                'step' => 'error',
                'message' => "Véhicule introuvable. Impossible de valider le rendez-vous.",
                'type' => 'text'
            ]);
        }

        $appointment = new Appointment();
        $appointment->setVehicle($vehicle);
        $appointment->setDate($date);

        $this->em->persist($appointment);
        $this->em->flush();

        return new JsonResponse([
            'step' => 'end',
            'message' => "🎉 Votre rendez-vous a bien été enregistré dans notre système. Merci !",
            'type' => 'text'
        ]);
    }

    public function handleFinalizeAppointment(mixed $input, Request $request): JsonResponse
    {
        $session = $request->getSession();

        if (strtolower($input) !== 'oui') {
            return new JsonResponse([
                'step' => 'cancel_appointment',
                'message' => "D'accord, le rendez-vous n’a pas été confirmé.",
                'type' => 'text'
            ]);
        }

        $vehicle = $this->vehicleRepository->findOneBy([
            'immatriculation' => $session->get('chatbot_immatriculation')
        ]);

        if (!$vehicle) {
            return new JsonResponse([
                'step' => 'error',
                'message' => "Impossible de retrouver le véhicule pour enregistrer le rendez-vous.",
                'type' => 'text'
            ]);
        }

        $slot = $session->get('chatbot_selected_slot');
        if (!preg_match('/\d{2} [a-zéû]+/i', $slot, $matches)) {
            return new JsonResponse([
                'step' => 'error',
                'message' => "Le format du créneau est invalide.",
                'type' => 'text'
            ]);
        }

        $frToNum = [
            'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12
        ];

        $parts = explode(' ', $matches[0]);
        $day = $parts[0];
        $month = strtolower($parts[1]);

        $now = new \DateTime();
        $year = $now->format('Y');
        $date = \DateTime::createFromFormat('Y-m-d', "$year-{$frToNum[$month]}-$day");

        if (!$date) {
            return new JsonResponse([
                'step' => 'error',
                'message' => "Date de rendez-vous invalide.",
                'type' => 'text'
            ]);
        }

        $appointment = new Appointment();
        $appointment->setVehicle($vehicle);
        $appointment->setDate($date);

        $this->em->persist($appointment);
        $this->em->flush();

        return new JsonResponse([
            'step' => 'end',
            'message' => "✅ Votre rendez-vous est bien enregistré pour le {$date->format('d/m/Y')} !",
            'type' => 'text'
        ]);
    }

}