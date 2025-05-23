<?php

namespace App\Service\Chatbot;

use App\Entity\Conductor;
use App\Entity\Vehicle;
use App\Normalizer\VehicleNormalizer;
use App\Repository\VehicleRepository;
use App\Service\User\CurrentUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class ChatbotVehicleService
{
    public function __construct(
        private VehicleRepository $vehicleRepository,
        private string $fakeVehiclePath,
        private ChatbotSanitizerService $sanitizer,
        private VehicleNormalizer $normalizer,
        private EntityManagerInterface $em,
        private PexelsService $pexelsService,
        private CurrentUserService $currentUserService,
    ) {}

    public function handleStart(?UserInterface $user): JsonResponse
    {
        if ($user) {
            $vehicles = $this->vehicleRepository->createQueryBuilder('v')
                ->join('v.conductor', 'c')
                ->where('c.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();

            if ($vehicles) {
                $options = array_map(
                    fn($v) => $v->getBrand() . ' ' . $v->getModel() . ' (' . $v->getImmatriculation() . ')',
                    $vehicles
                );
                $options[] = 'Autre véhicule';

                return new JsonResponse([
                    'step' => 'choose_existing_or_new',
                    'message' => "Salut {$user->getFirstname()} 👋 ! Tu as déjà " . count($vehicles) . " véhicule(s) enregistré(s). Lequel veux-tu utiliser aujourd’hui ?",
                    'type' => 'radio',
                    'options' => $options,
                    'data' => ['existing' => true]
                ]);
            }

            return new JsonResponse([
                'step' => 'ask_immatriculation',
                'message' => "Bienvenue ! Tu n’as pas encore enregistré de véhicule. Quelle est ta plaque d’immatriculation ?",
                'type' => 'text'
            ]);
        }

        return new JsonResponse([
            'step' => 'ask_immatriculation',
            'message' => "Bonjour 👋 ! Quelle est la plaque d'immatriculation de votre véhicule ?",
            'type' => 'text'
        ]);
    }

    public function handleConfirmVehicle(string $input, Request $request, ?UserInterface $user): JsonResponse
    {
        $session = $request->getSession();
        $payload = json_decode($request->getContent(), true)['data'] ?? [];
        $source  = $payload['source']  ?? 'none';

        if (strtolower(trim($input)) !== 'oui') {
            return new JsonResponse([
                'step'    => 'ask_vehicle_name',
                'message' => "Merci d’indiquer la marque et le modèle de votre véhicule.",
                'type'    => 'text',
            ]);
        }

        if ($source === 'none') {
            if (!$user) {
                return new JsonResponse([
                    'step'    => 'ask_check_email',
                    'message' => "Avez-vous déjà un compte chez nous ? Merci d’indiquer votre email.",
                    'type'    => 'text',
                ]);
            }

            return new JsonResponse([
                'step'    => 'ask_brand',
                'message' => "Parfait ! Quelle est la marque du nouveau véhicule ?",
                'type'    => 'text',
            ]);
        }

        if ($source === 'bdd') {
            $vehicle = $this->vehicleRepository->find($payload['vehicle_id']);
            $session->set('chatbot_vehicle_id',       $vehicle->getId());
            $session->set('chatbot_immatriculation', $vehicle->getImmatriculation());
            $session->set('chatbot_brand',           $vehicle->getBrand());
            $session->set('chatbot_model',           $vehicle->getModel());

            return new JsonResponse([
                'step'    => 'ask_problem',
                'message' => "Pouvez-vous me décrire le problème rencontré ?",
                'type'    => 'text',
            ]);
        }

        if ($source === 'json') {
            $matched = $payload['vehicle'];

            $vehicle = new Vehicle();
            $vehicle->setBrand(strtoupper($matched['marque']));
            $vehicle->setModel(strtoupper($matched['modele']));
            $vehicle->setImmatriculation(strtoupper(str_replace(['-',' '], '', $matched['immatriculation'])));
            $vehicle->setVin($matched['vin']);
            $vehicle->setDateOfCirculation(new \DateTime($matched['date_mise_en_circulation']));

            if ($user) {
                $conductor = $user->getConductors()->first();
                $vehicle->setConductor($conductor);
            }

            $this->normalizer->normalize($vehicle);
            $this->em->persist($vehicle);
            $this->em->flush();

            $session->set('chatbot_vehicle_id',       $vehicle->getId());
            $session->set('chatbot_immatriculation', $vehicle->getImmatriculation());
            $session->set('chatbot_brand',           $vehicle->getBrand());
            $session->set('chatbot_model',           $vehicle->getModel());

            return new JsonResponse([
                'step'    => 'ask_problem',
                'message' => "Votre véhicule a bien été enregistré. Pouvez-vous décrire le problème rencontré ?",
                'type'    => 'text',
            ]);
        }

        return new JsonResponse([
            'message' => 'Étape inconnue.',
        ], 400);
    }


    public function handleVehicleChoice(mixed $input, Request $request): JsonResponse
    {
        $session  = $request->getSession();
        $selected = is_array($input) ? $input[0] : $input;

        if ($selected === 'Autre véhicule') {
            return new JsonResponse([
                'step'    => 'ask_immatriculation',
                'message' => "Entrez la plaque du nouveau véhicule.",
                'type'    => 'text',
            ]);
        }

        preg_match('/\(([^)]+)\)$/', $selected, $m);
        $plate = strtoupper(str_replace(['-',' '], '', $m[1]));

        $vehicle = $this->vehicleRepository->findOneBy(['immatriculation' => $plate]);
        if (!$vehicle) {

        }

        $session->set('chatbot_vehicle_id',       $vehicle->getId());
        $session->set('chatbot_immatriculation', $vehicle->getImmatriculation());
        $session->set('chatbot_brand',           $vehicle->getBrand());
        $session->set('chatbot_model',           $vehicle->getModel());

        return new JsonResponse([
            'step'    => 'ask_problem',
            'message' => "Parfait, quel est le problème avec votre véhicule ?",
            'type'    => 'text',
        ]);
    }

    public function handleImmatriculation(mixed $input, Request $request, ?UserInterface $user): JsonResponse
    {
        $session = $request->getSession();
        $plate   = strtoupper(str_replace(['-', ' '], '', (string)$input));

        $vehicle = $this->vehicleRepository->findOneBy(['immatriculation' => $plate]);
        if ($vehicle) {
            $message   = sprintf(
                "Est-ce bien une %s %s (%s) ?",
                $vehicle->getBrand(),
                $vehicle->getModel(),
                $vehicle->getDateOfCirculation()?->format('Y')
            );
            $allImages = $this->pexelsService->searchImages("{$vehicle->getBrand()} {$vehicle->getModel()}");
            $images    = $allImages ? [ $allImages[0] ] : [];

            return new JsonResponse([
                'step'    => 'confirm_vehicle',
                'message' => $message,
                'type'    => 'confirm',
                'data'    => [
                    'source'     => 'bdd',
                    'vehicle_id' => $vehicle->getId(),
                    'images'     => $images,
                ],
            ]);
        }

        $content  = json_decode(file_get_contents($this->fakeVehiclePath), true);
        $vehicles = $content['vehicules'] ?? [];
        foreach ($vehicles as $item) {
            $itemPlate = strtoupper(str_replace(['-', ' '], '', $item['immatriculation']));
            if ($itemPlate === $plate) {
                $message   = sprintf(
                    "Est-ce bien une %s %s (%s) ?",
                    strtoupper($item['marque']),
                    strtoupper($item['modele']),
                    (new \DateTime($item['date_mise_en_circulation']))->format('Y')
                );
                $allImages = $this->pexelsService->searchImages("{$item['marque']} {$item['modele']}");
                $images    = $allImages ? [ $allImages[0] ] : [];

                return new JsonResponse([
                    'step'    => 'confirm_vehicle',
                    'message' => $message,
                    'type'    => 'confirm',
                    'data'    => [
                        'source'  => 'json',
                        'vehicle' => $item,
                        'images'  => $images,
                    ],
                ]);
            }
        }

        $session->set('chatbot_immatriculation', $plate);
        return new JsonResponse([
            'step'    => 'ask_brand',
            'message' => "Nous ne trouvons pas cette plaque dans nos données. Quelle est la marque du véhicule ?",
            'type'    => 'text',
        ]);
    }

    public function handleBrand(mixed $input, Request $request, ?UserInterface $user): JsonResponse
    {
        $request->getSession()->set('chatbot_brand', $input);
        if ($user) {
            $request->getSession()->set('chatbot_mode', 'add_vehicle_only');
        }

        return new JsonResponse([
            'step' => 'ask_model',
            'message' => "Très bien. Quel est le modèle du véhicule ?",
            'type' => 'text'
        ]);
    }

    public function handleModel(mixed $input, Request $request): JsonResponse
    {
        $request->getSession()->set('chatbot_model', $input);
        return new JsonResponse([
            'step' => 'ask_mileage',
            'message' => "Combien de kilomètres a-t-elle ?",
            'type' => 'text'
        ]);
    }

    public function handleMileage(mixed $input, Request $request): JsonResponse
    {
        $mileage = $this->sanitizer->extractMileage((string)$input);

        if (!$mileage) {
            return new JsonResponse([
                'step' => 'ask_mileage',
                'message' => "Merci d’indiquer un nombre de kilomètres valide.",
                'type' => 'text'
            ]);
        }

        $request->getSession()->set('chatbot_mileage', $mileage);

        return new JsonResponse([
            'step' => 'ask_circulation_date',
            'message' => "Quelle est sa date de mise en circulation ? (ex: 20/10/2009)",
            'type' => 'text'
        ]);
    }

    public function handleCirculationDate(mixed $input, Request $request): JsonResponse
    {
        $date = $this->sanitizer->extractDate((string)$input);

        if (!$date) {
            return new JsonResponse([
                'step' => 'ask_circulation_date',
                'message' => "Format invalide. Merci d’indiquer une date au format JJ/MM/AAAA.",
                'type' => 'text'
            ]);
        }

        $request->getSession()->set('chatbot_date', $date);

        return new JsonResponse([
            'step' => 'ask_vin',
            'message' => "Parfait. Quel est le numéro VIN du véhicule ?",
            'type' => 'text'
        ]);
    }


    public function handleVin(mixed $input, Request $request, ?UserInterface $user): JsonResponse
    {
        $vin = $this->sanitizer->extractVin((string)$input);
        if (!$vin) {
            return new JsonResponse([
                'step'    => 'ask_vin',
                'message' => "Merci de saisir un numéro VIN valide.",
                'type'    => 'text'
            ]);
        }

        $session = $request->getSession();
        $session->set('chatbot_vin', $vin);

        if ($user === null) {
            return new JsonResponse([
                'step'    => 'ask_civility',
                'message' => "Parfait. Vous êtes madame ou monsieur ?",
                'type'    => 'text'
            ]);
        }

        return new JsonResponse([
            'step'    => 'ask_is_driver',
            'message' => "Êtes-vous le conducteur du véhicule ?",
            'type'    => 'confirm'
        ]);
    }



    public function handleIsDriver(string $input, Request $request, ?UserInterface $user): JsonResponse
    {
        $session    = $request->getSession();
        $isDriver   = strtolower(trim($input)) === 'oui';
        $session->set('chatbot_is_driver', $isDriver);

        if ($isDriver) {
            if ($user) {
                // ←— On crée et persiste le nouveau véhicule pour l'utilisateur connecté —→
                $vehicle = new Vehicle();
                $vehicle
                    ->setBrand(            $session->get('chatbot_brand'))
                    ->setModel(            $session->get('chatbot_model'))
                    ->setImmatriculation(  $session->get('chatbot_immatriculation'))
                    ->setVin(              $session->get('chatbot_vin'))
                    ->setDateOfCirculation($session->get('chatbot_date'))
                    ->setMileage(          $session->get('chatbot_mileage'));

                // On rattache le véhicule au premier conducteur de l'utilisateur
                $conductor = $user->getConductors()->first();
                $vehicle->setConductor($conductor);

                // Normalisation et sauvegarde
                $this->normalizer->normalize($vehicle);
                $this->em->persist($vehicle);
                $this->em->flush();

                // On stocke l'ID du véhicule en session
                $session->set('chatbot_vehicle_id', $vehicle->getId());

                return new JsonResponse([
                    'step'    => 'ask_problem',
                    'message' => "Votre véhicule a bien été ajouté. Pouvez-vous me décrire le problème rencontré ?",
                    'type'    => 'text',
                ]);
            }

            // Si pas connecté, on continue avec la création du profil conducteur
            return new JsonResponse([
                'step'    => 'ask_civility',
                'message' => "Parfait. Vous êtes madame ou monsieur ?",
                'type'    => 'text',
            ]);
        }

        // Cas où l'utilisateur n'est pas le conducteur
        $conductors = $user?->getConductors() ?? [];
        $options     = [];

        foreach ($conductors as $c) {
            $options[] = sprintf("%s %s (ID:%d)", $c->getFirstname(), $c->getLastname(), $c->getId());
        }

        $options[] = "Ajouter un nouveau conducteur";

        return new JsonResponse([
            'step'    => 'choose_conductor',
            'message' => "Qui est le conducteur ?",
            'type'    => 'radio',
            'options' => $options,
        ]);
    }


    public function handleChooseConductor(mixed $input, Request $request): JsonResponse
    {
        $session = $request->getSession();

        // Si l’utilisateur souhaite ajouter un nouveau conducteur
        if (in_array('Ajouter un nouveau conducteur', (array)$input, true)) {
            return new JsonResponse([
                'step'    => 'create_conductor',
                'message' => "Très bien. Merci d’indiquer le prénom, nom et téléphone du conducteur.",
                'type'    => 'text'
            ]);
        }

        // Extraction de l’ID du conducteur sélectionné
        $selected = is_array($input) ? $input[0] : $input;
        preg_match('/\(ID:(\d+)\)$/', $selected, $matches);
        if (!isset($matches[1])) {
            return new JsonResponse([
                'step'    => 'choose_conductor',
                'message' => "Erreur lors de la sélection du conducteur.",
                'type'    => 'text'
            ]);
        }
        $conductorId = (int)$matches[1];
        $session->set('chatbot_conductor_id', $conductorId);

        // Récupération de l'entité Conductor
        $conductor = $this->em->getRepository(Conductor::class)->find($conductorId);
        if (!$conductor) {
            return new JsonResponse([
                'step'    => 'choose_conductor',
                'message' => "Conducteur introuvable.",
                'type'    => 'text'
            ]);
        }

        // Création du véhicule à partir des données en session
        $vehicle = new Vehicle();
        $vehicle
            ->setBrand($session->get('chatbot_brand'))
            ->setModel($session->get('chatbot_model'))
            ->setImmatriculation($session->get('chatbot_immatriculation'))
            ->setDateOfCirculation($session->get('chatbot_date'))
            ->setMileage($session->get('chatbot_mileage'))
            ->setVin($session->get('chatbot_vin'))
            ->setConductor($conductor);

        // Normalisation et persistence
        $this->normalizer->normalize($vehicle);
        $this->em->persist($vehicle);
        $this->em->flush();

        // Stockage de l'ID du véhicule créé pour la suite
        $session->set('chatbot_vehicle_id', $vehicle->getId());

        // Passage à l'étape suivante
        return new JsonResponse([
            'step'    => 'ask_problem',
            'message' => "Parfait, votre véhicule a été enregistré et lié à ce conducteur. Quel est le problème rencontré ?",
            'type'    => 'text'
        ]);
    }


    public function handleCreateConductor(mixed $input, Request $request): JsonResponse
    {
        $parts = explode(' ', $input);
        if (count($parts) < 3) {
            return new JsonResponse([
                'step'    => 'create_conductor',
                'message' => "Merci d’indiquer : prénom nom téléphone",
                'type'    => 'text'
            ]);
        }

        $user    = $this->currentUserService->getCurrentUser();
        $session = $request->getSession();

        [$first, $last, $phone] = $parts;

        $conductor = new Conductor();
        $conductor
            ->setFirstname($first)
            ->setLastname($last)
            ->setPhone($phone)
            ->setUser($user);

        $this->em->persist($conductor);
        $this->em->flush();

        // Stockage de l'ID en session
        $session->set('chatbot_conductor_id', $conductor->getId());

        // 2) Si on était en mode "add_vehicle_only", on crée aussi le véhicule
        if ($session->get('chatbot_mode') === 'add_vehicle_only') {
            $vehicle = new Vehicle();
            $vehicle
                ->setBrand($session->get('chatbot_brand'))
                ->setModel($session->get('chatbot_model'))
                ->setImmatriculation($session->get('chatbot_immatriculation'))
                ->setDateOfCirculation($session->get('chatbot_date'))
                ->setMileage($session->get('chatbot_mileage'))
                ->setVin($session->get('chatbot_vin'))
                ->setConductor($conductor);

            $this->normalizer->normalize($vehicle);
            $this->em->persist($vehicle);
            $this->em->flush();

            // Stockage de l'ID du véhicule en session
            $session->set('chatbot_vehicle_id', $vehicle->getId());
        }

        return new JsonResponse([
            'step'    => 'ask_problem',
            'message' => "Parfait, votre conducteur a été noté. Quel est le problème avec le véhicule ?",
            'type'    => 'text'
        ]);
    }


}
