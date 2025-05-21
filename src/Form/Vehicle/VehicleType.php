<?php

namespace App\Form\Vehicle;

use App\Entity\Conductor;
use App\Entity\Vehicle;
use App\Repository\ConductorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType; 


class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', TextType::class, ['label' => 'Marque'])
            ->add('model', TextType::class, ['label' => 'Modèle'])
            ->add('immatriculation', TextType::class, ['label' => 'Immatriculation'])
            ->add('vin', TextType::class, ['label' => 'N° de châssis (VIN)', 'required' => false])
            ->add('dateOfCirculation', DateType::class, [
                'label' => 'Date de mise en circulation',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('conductor', EntityType::class, [
                'class' => Conductor::class,
                'choice_label' => fn (Conductor $c) => $c->getFirstname() . ' ' . $c->getLastname(),
                'label' => 'Conducteur',
                'query_builder' => function (ConductorRepository $repo) use ($options) {
                    return $repo->createQueryBuilder('c')
                        ->andWhere('c.user = :user')
                        ->setParameter('user', $options['user']);
                },
            ])
            ->add('mileage', IntegerType::class, ['label' => 'Kilométrage', 'required' => false])
            ->add('brand', ChoiceType::class, [
                'choices' => [
                    'Peugeot' => 'peugeot',
                    'BMW' => 'bmw',
                    'Citroën' => 'citroen',
                    'Bugatti' => 'bugatti',
                    'Fiat' => 'fiat',
                    'Ford' => 'ford',
                    'Porsche' => 'porsche',
                    'Renault' => 'renault',
                    'Volkswagen' => 'volkswagen',
                ],
                'placeholder' => 'Choisissez une marque',
                'attr' => ['id' => 'brand-selector']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'user' => null,
        ]);
    }
}
