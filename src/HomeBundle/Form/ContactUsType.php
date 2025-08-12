<?php

namespace App\HomeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactUsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'subject_txt',
                "attr" => [
                    'placeholder' => 'subject_txt',
                    "class" => "input",
                    "data-stack-input-input" => "true",
                    "autocomplete" => "subject",
                    "data-validate-input" => "subject",
                    "data-form-validate-input-rules" => "required",
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide the subject"]),
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'name_txt',
                "attr" => [
                    'placeholder' => 'name_txt',
                    "class" => "input",
                    "data-stack-input-input" => "true",
                    "autocomplete" => "name",
                    "data-validate-input" => "name",
                    "data-form-validate-input-rules" => "required",
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide your name"]),
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'email_txt',
                "attr" => [
                    'placeholder' => 'email_txt',
                    "class" => "input",
                    "autocomplete" => "email",
                    "data-stack-input-input" => "true",
                    "data-validate-input" => "email",
                    "data-form-validate-input-rules" => "required|email",
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide a valid email"]),
                    new Email(["message" => "Your email doesn't seems to be valid"]),
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => 'phone_txt',
                "attr" => [
                    'placeholder' => 'phone_txt',
                    "class" => "input",
                    "data-stack-input-input" => "true",
                    "autocomplete" => "tel",
                    "data-validate-input" => "phone",
                    "data-form-validate-input-rules" => "required|phone",
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'message_txt',
                "attr" => [
                    'placeholder' => 'message_txt',
                    "rows" => 8,
                    "class" => "input",
                    "data-stack-input-input" => "true",
                    "data-validate-input" => "message",
                    "data-form-validate-input-rules" => "required",
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide your message"]),
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'error_bubbling' => true
        ]);
    }

}
