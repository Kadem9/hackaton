<?php

namespace App\Service\Chatbot;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class ChatbotStepDispatcher
{
    public function __construct(
        private ChatbotVehicleService $vehicleService,
        private ChatbotUserRegistrationService $userRegistrationService,
        private ChatbotDiagnosisService $diagnosisService,
        private ChatbotConcessionService $concessionService,
        private ChatbotAppointmentService $appointmentService,
    ) {}

    public function dispatch(string $step, mixed $input, Request $request, ?UserInterface $user): JsonResponse
    {
        $session = $request->getSession();

        // je supp les variables si etape start
        if ($step === 'start') {
            foreach ([
                         'chatbot_immatriculation',
                         'chatbot_brand',
                         'chatbot_model',
                         'chatbot_mileage',
                         'chatbot_date',
                         'chatbot_vin',
                         'chatbot_is_driver',
                         'chatbot_conductor_id',
                         'chatbot_vehicle_id',
                         'chatbot_selected_garage',
                         'chatbot_problem',
                         'chatbot_selected_operations',
                         'chatbot_appointment_date',
                         'chatbot_slot',
                         'chatbot_mode',
                         'chatbot_slot_input',
                     ] as $key) {
                $session->remove($key);
            }
        }

        return match ($step) {
            // 1. VÉHICULE EXISTANT
            'start'                   => $this->vehicleService->handleStart($user),
            'choose_existing_or_new'  => $this->vehicleService->handleVehicleChoice($input, $request),
            'ask_immatriculation'     => $this->vehicleService->handleImmatriculation($input, $request, $user),
            'confirm_vehicle'         => $this->vehicleService->handleConfirmVehicle($input, $request, $user),

            // 2. AUTHENTIFICATION (login)
            'ask_check_email'         => $this->userRegistrationService->handleCheckEmail($input, $request),
            'ask_password_login'      => $this->userRegistrationService->handleLoginPassword($input, $request),

            // 3. NOUVEAU VÉHICULE (après plaque non trouvée + user connecté)
            'ask_brand'               => $this->vehicleService->handleBrand($input, $request, $user),
            'ask_model'               => $this->vehicleService->handleModel($input, $request),
            'ask_mileage'             => $this->vehicleService->handleMileage($input, $request),
            'ask_mileage2'             => $this->vehicleService->handleMileage2($input, $request),
            'ask_circulation_date'    => $this->vehicleService->handleCirculationDate($input, $request),
            'ask_vin'                 => $this->vehicleService->handleVin($input, $request, $user),
            'ask_is_driver'           => $this->vehicleService->handleIsDriver($input, $request, $user),
            'choose_conductor'        => $this->vehicleService->handleChooseConductor($input, $request),
            'create_conductor'        => $this->vehicleService->handleCreateConductor($input, $request),

            // 4. INSCRIPTION UTILISATEUR (non connecté)
            'ask_civility'            => $this->userRegistrationService->handleCivility($input, $request),
            'ask_email'               => $this->userRegistrationService->handleEmail($input, $request),
            'ask_user_type'           => $this->userRegistrationService->handleUserType($input, $request),
            'ask_name'                => $this->userRegistrationService->handleName($input, $request),
            'ask_phone'               => $this->userRegistrationService->handlePhone($input, $request),
            'ask_password'            => $this->userRegistrationService->handlePassword($input, $request),
            'finalize_registration'   => $this->userRegistrationService->handleFinalizeRegistration($request),

            // 5. DIAGNOSTIC PROBLÈME
            'ask_problem'             => $this->diagnosisService->handleProblem($input, $request),
            'choose_operations'       => $this->diagnosisService->handleChooseOperations($input, $request),

            // 6. LOCALISATION & GARAGE
            'ask_location'            => $this->concessionService->handleLocation($input, $request),
            'choose_garage'           => $this->concessionService->handleChooseGarage($input, $request),

            // 7. RDV / CRÉNEAUX
            'ask_date_type'           => $this->appointmentService->handleDateType($input, $request),
            'choose_date'             => $this->appointmentService->handleChooseDate($input, $request),
            'confirm_slot'            => $this->appointmentService->handleConfirmSlot($input, $request),

            // 8. RÉCAPITULATIF & VALIDATION
            'ask_reminder'            => $this->appointmentService->handleReminder($input, $request),
            'confirm_final'           => $this->appointmentService->handleFinalConfirmation($input, $request),
            'confirm_appointment'     => $this->appointmentService->handleConfirmAppointment($input, $request, $user),
            'finalize_appointment'    => $this->appointmentService->handleFinalizeAppointment($input, $request),

            // CAS PAR DÉFAUT
            default                   => new JsonResponse(['message' => 'Étape inconnue.'], 400),
        };
    }
}
