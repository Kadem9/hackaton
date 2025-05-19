<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Droits',
                'expanded' => true,
                'multiple' => true,

                'choices' => [
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => !$options['isEdit'],
                'constraints' => [
                    new Length(['min' => 8, 'minMessage' => 'Votre mot de passe doit contenir au moins 8 caractères.']),
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'isEdit' => false
        ]);
    }
}
