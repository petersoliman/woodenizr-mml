<?php

namespace App\UserBundle\Form;

use App\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdministrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordRequired = false;
        $passwordConstraints = [
            new Length([
                'min' => 6,
                'minMessage' => 'Your password should be at least {{ limit }} characters',
                // max length allowed by Symfony for security reasons
                'max' => 4096,
            ]),
        ];
        if ($builder->getData()->getId() == null) {
            $passwordRequired = true;
            $passwordConstraints[] = new NotBlank([
                'message' => 'Please enter a password',
            ]);
        }
        $builder
            ->add('fullName')
            ->add('email', EmailType::class)
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Male' => User::GENDER_MALE,
                    'Female' => User::GENDER_FEMALE,
                ],
            ])
            ->add('phone', TelType::class, [
                "required" => false,
                'attr' => [
                    'placeholder' => '01xxxxxxxxx',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => $passwordRequired,
                'attr' => ['autocomplete' => 'new-password'],
                'first_options' => ['label' => 'Password', 'required' => $passwordRequired,],
                'second_options' => ['label' => 'Confirm Password', 'required' => $passwordRequired,],
                'constraints' => $passwordConstraints,
            ])
            ->add('roles', ChoiceType::class, [
                "multiple" => true,
                'choices' => [
                    'Admin' => User::ROLE_ADMIN,
                ],
                "attr" => ["class" => "select-search"],
            ])
            ->add('enabled', null, [
                'label' => 'Active',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ["Default"],
        ]);
    }
}
