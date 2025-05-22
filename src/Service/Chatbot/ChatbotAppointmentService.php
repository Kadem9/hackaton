<?php

namespace App\Service\Chatbot;

use App\Entity\Appointment;
use App\Repository\VehicleRepository;
use App\Service\Appointment\AppointmentRecapService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


readonly class ChatbotAppointmentService
{
public function __construct(
        private ChatbotAvailabilityService $availabilityService,
        private VehicleRepository $vehicleRepository,
        private EntityManagerInterface $em,
        private AppointmentRecapService $recapService
    ) {}

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
        $session = $request->getSession();

        $session->set('chatbot_mode', 'manual');
        $session->set('chatbot_slot_input', $input);

        return new JsonResponse([
            'step' => 'confirm_slot',
            'message' => "Parfait, nous avons bien noté la date souhaitée : $input",
            'type' => 'checkbox',
            'options' => [$input],
            // plus besoin de data ici
        ]);
    }


    public function handleConfirmSlot(mixed $input, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $mode    = $session->get('chatbot_mode', 'auto');

        if (is_array($input)) {
            $input = $input[0] ?? '';
        }
        $input = str_replace(['—','–'], '-', (string)$input);

        if ($mode === 'manual') {
            $raw = $session->get('chatbot_slot_input', $input);
            $date = \DateTime::createFromFormat('d/m/Y', $raw);
            if (!$date) {
                return new JsonResponse([
                    'step'    => 'choose_date',
                    'message' => "La date indiquée est invalide. Format attendu : JJ/MM/AAAA",
                    'type'    => 'text'
                ]);
            }

            $session->set('chatbot_appointment_date', $date);
            $session->set('chatbot_slot', $raw);

        } else {
            if (!preg_match('/^([A-Za-zéèûîàç]+)\s+(\d{2})\s+(\w+)\s*-\s*(\d+)\s+créneaux?$/u', $input, $m)) {
                return new JsonResponse([
                    'step'    => 'choose_date',
                    'message' => "Le format du créneau est invalide.",
                    'type'    => 'text'
                ]);
            }
            [,$dayName,$day,$monthFr,$count] = $m;

            $map = [
                'janvier'=>'January','février'=>'February','mars'=>'March','avril'=>'April',
                'mai'=>'May','juin'=>'June','juillet'=>'July','août'=>'August',
                'septembre'=>'September','octobre'=>'October','novembre'=>'November','décembre'=>'December'
            ];
            $monthEn = $map[strtolower($monthFr)] ?? null;
            if (!$monthEn) {
                return new JsonResponse([
                    'step'    => 'choose_date',
                    'message' => "Mois invalide, veuillez réessayer.",
                    'type'    => 'text'
                ]);
            }

            $year = (new \DateTime())->format('Y');
            $date = \DateTime::createFromFormat('d F Y', "$day $monthEn $year");
            if (!$date) {
                return new JsonResponse([
                    'step'    => 'choose_date',
                    'message' => "Impossible de traiter la date choisie.",
                    'type'    => 'text'
                ]);
            }

            $session->set('chatbot_appointment_date', $date);
            $session->set('chatbot_slot', $input);
        }

        $vehicle = $session->get('chatbot_brand')
            . ' ' . $session->get('chatbot_model')
            . ' (' . $session->get('chatbot_immatriculation') . ')';
        $garage  = $session->get('chatbot_selected_garage') ?? 'Non précisé';
        $problem = $session->get('chatbot_problem') ?? 'Problème non précisé';
        $slot    = $session->get('chatbot_slot');

        $recap = "<b>🚗 Véhicule :</b> $vehicle<br/>"
            . "<b>📍 Garage :</b> $garage<br/>"
            . "<b>📅 Créneau :</b> $slot<br/>"
            . "<b>🛠 Problème :</b> $problem<br/><br/>"
            . "Souhaitez-vous confirmer ce rendez-vous ?";

        return new JsonResponse([
            'step'    => 'confirm_final',
            'message' => $recap,
            'type'    => 'confirm'
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
        $session = $request->getSession();

        if (strtolower(trim($input)) !== 'oui') {
            return new JsonResponse([
                'step'    => 'ask_reminder',
                'message' => "Souhaitez-vous qu’un conseiller vous rappelle pour fixer le rendez-vous ?",
                'type'    => 'confirm',
            ]);
        }

        $vehicleId = $session->get('chatbot_vehicle_id');
        /** @var \DateTimeInterface|null $date */
        $date      = $session->get('chatbot_appointment_date');

        if (!$date instanceof \DateTimeInterface) {
            return new JsonResponse([
                'step'    => 'error',
                'message' => "Impossible de récupérer la date de rendez-vous.",
                'type'    => 'text',
            ]);
        }

        $vehicle = $this->vehicleRepository->find($vehicleId);
        if (!$vehicle) {
            return new JsonResponse([
                'step'    => 'error',
                'message' => "Véhicule introuvable. Impossible de valider le rendez-vous.",
                'type'    => 'text',
            ]);
        }

        $appointment = new Appointment();
        $appointment->setVehicle($vehicle);
        $appointment->setDate($date);

        $this->em->persist($appointment);
        $this->em->flush();

        // Préparer les données de récap
        $slot    = $date->format('d/m/Y à H\hi');
        $data = [
            'firstname' => $session->get('chatbot_firstname'),
            'lastname'  => $session->get('chatbot_lastname'),
            'vehicle'   => strtoupper($session->get('chatbot_brand')
                . ' ' . $session->get('chatbot_model')
                . ' (' . $session->get('chatbot_immatriculation') . ')'),
            'garage'    => $session->get('chatbot_selected_garage'),
            'slot'      => $slot,
            'problem'   => $session->get('chatbot_problem') ?? 'Non précisé',
        ];

        $files = $this->recapService->generate($data);

        $message = sprintf(
            "🎉 Votre rendez-vous a bien été enregistré pour le <strong>%s</strong> !<br/><br/>" .
            "📄 <a href=\"%s\" target=\"_blank\">Télécharger le PDF</a><br/>" .
            "🗂️ <a href=\"%s\" target=\"_blank\">Télécharger les données JSON</a>",
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

}