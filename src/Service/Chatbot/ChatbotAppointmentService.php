<?php

namespace App\Service\Chatbot;

use App\Entity\Appointment;
use App\Repository\VehicleRepository;
use App\Service\Appointment\AppointmentRecapService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;


readonly class ChatbotAppointmentService
{
public function __construct(
        private ChatbotAvailabilityService $availabilityService,
        private VehicleRepository $vehicleRepository,
        private EntityManagerInterface $em,
        private AppointmentRecapService $recapService,
    ) {}

    public function handleDateType(string $input, Request $request): JsonResponse
    {
        $session = $request->getSession();

        if (strtolower($input) === 'oui') {
            return new JsonResponse([
                'step'    => 'choose_date',
                'message' => "Merci ! Veuillez indiquer la date souhaitée (ex: 24/05/2025).",
                'type'    => 'text'
            ]);
        }

        $selected = $session->get('chatbot_selected_garage');
        if (!$selected || !preg_match('/^(.*?) – (.*?) \((\d{5}) (.*?)\)$/u', $selected, $matches)) {
            return new JsonResponse([
                'step'    => 'ask_date_type',
                'message' => "Impossible d’extraire le garage sélectionné.",
                'type'    => 'text'
            ]);
        }
        [, $name, , , $city] = $matches;

        $timeslots = $this->availabilityService->getAvailableSlots($name, $city);
        if (empty($timeslots)) {
            return new JsonResponse([
                'step'    => 'choose_date',
                'message' => "Aucun créneau n’est disponible actuellement. Souhaitez-vous choisir une date ?",
                'type'    => 'confirm'
            ]);
        }

        return new JsonResponse([
            'step'    => 'confirm_slot',
            'message' => "Voici les créneaux disponibles :",
            'type'    => 'timeslot',
            'data'    => ['timeslots' => $timeslots],
        ]);
    }


    public function handleChooseDate(mixed $input, Request $request): JsonResponse
    {
        $session = $request->getSession();

        $session->set('chatbot_mode', 'manual');
        $session->set('chatbot_slot_input', $input);

        return new JsonResponse([
            'step' => 'confirm_slot',
            'message' => "Parfait, nous avons bien noté la date souhaitée : $input",
            'type' => 'radio',
            'options' => [$input],
        ]);
    }


    public function handleConfirmSlot(mixed $input, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $data = json_decode($request->getContent(), true);
        $mode = $data['data']['mode'] ?? null;

        if (is_array($input)) {
            $input = $input[0] ?? '';
        }
        $input = trim((string)$input);

        if ($mode === 'manual') {
            $date = \DateTime::createFromFormat('d/m/Y', $input);
            if (!$date) {
                return new JsonResponse([
                    'step'    => 'choose_date',
                    'message' => "La date indiquée est invalide. Format attendu : 24/05/2025",
                    'type'    => 'text',
                ]);
            }
            $session->set('chatbot_slot', $date->format('Y-m-d à 10h30'));
            $session->set('chatbot_appointment_date', $date);

        }
        else {
            if (!preg_match('/^([A-Za-zéèûîàç]+) (\d{1,2}) (\w+) à ([0-9]{2}:[0-9]{2})$/u', $input, $m)) {
                return new JsonResponse([
                    'step'    => 'confirm_slot',
                    'message' => "Le format du créneau est invalide.",
                    'type'    => 'text',
                ]);
            }
            [, $dayName, $day, $monthFr, $time] = $m;

            $monthMap = [
                'janvier'   => 'January',
                'février'   => 'February',
                'mars'      => 'March',
                'avril'     => 'April',
                'mai'       => 'May',
                'juin'      => 'June',
                'juillet'   => 'July',
                'août'      => 'August',
                'septembre' => 'September',
                'octobre'   => 'October',
                'novembre'  => 'November',
                'décembre'  => 'December',
            ];
            $monthEn = $monthMap[mb_strtolower($monthFr)] ?? null;
            if (!$monthEn) {
                return new JsonResponse([
                    'step'    => 'choose_date',
                    'message' => "Mois invalide, veuillez réessayer.",
                    'type'    => 'text',
                ]);
            }

            $year       = (new \DateTime())->format('Y');
            $dateString = "{$day} {$monthEn} {$year} {$time}";
            $date       = \DateTime::createFromFormat('d F Y H:i', $dateString);
            if (!$date) {
                return new JsonResponse([
                    'step'    => 'choose_date',
                    'message' => "Impossible de traiter la date choisie.",
                    'type'    => 'text',
                ]);
            }

            $session->set('chatbot_appointment_date', $date);
            $session->set('chatbot_slot', $date->format('Y-m-d à H\hi'));
        }

        $vehicle = $session->get('chatbot_brand') . ' ' . $session->get('chatbot_model')
            . ' (' . $session->get('chatbot_immatriculation') . ')';
        $garage  = $session->get('chatbot_selected_garage') ?? 'Non précisé';
        $problem = $session->get('chatbot_problem') ?? 'Problème non précisé';
        $slot    = $session->get('chatbot_slot');

        $recap = "<b>🚗 Véhicule :</b> {$vehicle}<br/>"
            . "<b>📍 Garage :</b> {$garage}<br/>"
            . "<b>📅 Créneau :</b> {$slot}<br/>"
            . "<b>🛠 Problème :</b> {$problem}<br/><br/>"
            . "Souhaitez-vous confirmer ce rendez-vous ?";

        return new JsonResponse([
            'step'    => 'confirm_appointment',
            'message' => $recap,
            'type'    => 'confirm',
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

    $vehicle = $session->get('chatbot_brand') . ' ' . $session->get('chatbot_model') . ' (' . $session->get('chatbot_immatriculation') . ')';
    $garage  = $session->get('chatbot_selected_garage') ?? 'Non précisé';
    $problem = $session->get('chatbot_problem') ?? 'Problème non précisé';
    $slot    = $session->get('chatbot_slot');

    $recap = "<b>🚗 Véhicule :</b> $vehicle<br/>"
        . "<b>📍 Garage :</b> $garage<br/>"
        . "<b>📅 Créneau :</b> $slot<br/>"
        . "<b>🛠 Problème :</b> $problem<br/><br/>"
        . "Souhaitez-vous valider définitivement ce rendez-vous ?";

    return new JsonResponse([
        'step'    => 'confirm_appointment',
        'message' => $recap,
        'type'    => 'confirm'
    ]);
}


public function handleConfirmAppointment(string $input, Request $request, ?UserInterface $user): JsonResponse
{
    $session = $request->getSession();

    if (strtolower(trim($input)) !== 'oui') {
        return new JsonResponse([
            'step'    => 'ask_reminder',
            'message' => "Souhaitez-vous qu’un conseiller vous rappelle pour fixer le rendez-vous ?",
            'type'    => 'confirm',
        ]);
    }

    $vehicleId = $session->get('chatbot_vehicle_id');
    $date      = $session->get('chatbot_appointment_date');

    if (!$date instanceof \DateTimeInterface) {
        return new JsonResponse([
            'step' => 'error',
            'message' => "Impossible de récupérer la date de rendez-vous.",
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

    $firstname = $session->get('chatbot_firstname') ?? $user?->getFirstname() ?? 'Inconnu';
    $lastname  = $session->get('chatbot_lastname') ?? $user?->getLastname() ?? 'Inconnu';


    $appointment = new Appointment();
    $appointment->setVehicle($vehicle);
    $appointment->setDate($date);

    $this->em->persist($appointment);
    $this->em->flush();

    $slot = $date->format('d/m/Y à H\hi');

    $data = [
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'vehicle'   => strtoupper($session->get('chatbot_brand') . ' ' . $session->get('chatbot_model') . ' (' . $session->get('chatbot_immatriculation') . ')'),
        'garage'    => $session->get('chatbot_selected_garage'),
        'slot'      => $slot,
        'problem'   => $session->get('chatbot_problem') ?? 'Non précisé',
        'operations' => $session->get('chatbot_selected_operations') ?? [],
    ];

    $files = $this->recapService->generate($data);

    $message = sprintf(
        "Votre rendez-vous a bien été enregistré pour le <strong>%s</strong> !<br/><br/>" .
        "<a href=\"%s\" target=\"_blank\">Télécharger le PDF</a><br/>" .
        "<a href=\"%s\" target=\"_blank\">Télécharger les données JSON</a>",
        $slot,
        $files['pdf_url'],
        $files['json_url']
    );

    return new JsonResponse([
        'step'    => 'end',
        'message' => $message,
        'type'    => 'text',
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

    public function handleReminder(string $input, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $selected = $session->get('chatbot_selected_garage');

        if(strtolower($input) === 'oui' && $selected)
        {
            return new JsonResponse([
                'step'    => 'end',
                'message' => "Merci ! Le garage que vous avez choisi ($selected) va vous rappeler.",
                'type'    => 'text'
            ]);
        }

        if (strtolower($input) === 'oui') {
            return new JsonResponse([
                'step'    => 'end',
                'message' => "Merci ! Le garage le plus proche de chez vous va vous rappeler.",
                'type'    => 'text'
            ]);
        }

         return new JsonResponse([
                'step'    => 'end',
                'message' => "Très bien, merci d'avoir utilisé le chat. Au revoir.",
                'type'    => 'text'
            ]);
    }

}