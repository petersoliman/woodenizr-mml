<?php

namespace App\UserBundle\Form;

use App\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName')
            ->add('email', EmailType::class)
//            ->add('gender', ChoiceType::class, [
//                'choices' => [
//                    'Male' => User::GENDER_MALE,
//                    'Female' => User::GENDER_FEMALE,
//                ],
//            ])
            ->add('phone', TelType::class, [
                "required" => false,
                'attr' => [
                    'placeholder' => '01xxxxxxxxx',
                ],
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
