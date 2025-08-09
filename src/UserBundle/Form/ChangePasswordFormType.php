<?php

namespace App\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class'=>'input',
                        'data-stack-input-input'=>'true',
                        'data-validate-input'=>'password',
                        'data-form-validate-input-rules'=>'required',
                        'data-ui-form-password'=>'sign-password',
                        'placeholder'=>'new_password_txt',
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter a password',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                        ]),
                    ],
                    'label' => 'new_password_txt',
                ],
                'second_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class'=>'input',
                        'data-stack-input-input'=>'true',
                        'data-validate-input'=>'confirm_password',
                        'data-form-validate-input-rules'=>'required|passwordMatch:[data-validate-input=password]',
                        'data-ui-form-password'=>'sign-password',
                        'placeholder'=>'confirm_new_password_txt',
                    ],
                    'label' => 'confirm_new_password_txt',
                ],
                'invalid_message' => 'The password fields must match.',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
