<?php

namespace App\HomeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class CareerType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("title", TextType::class, [
                "label" => "Title *",
                "attr" => [
                    'placeholder' => 'Title *',
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide the position title"]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Name *',
                "attr" => [
                    'placeholder' => 'Name *',
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide your name"]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address *',
                "attr" => [
                    'placeholder' => 'Email Address *',
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide a valid email"]),
                    new Email(["message" => "Your email doesn't seems to be valid"]),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number *',
                "attr" => [
                    'placeholder' => 'Phone Number *',
                    "class" => 'only-numeric',
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide your phone number"]),
                ],
            ])
            ->add('resume', FileType::class, [
                'label' => 'CV *',
                "attr" => [
                    "class" => "form-control",
                    "accept" => ".doc,.docx,application/pdf, application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                ],
                'constraints' => [
                    new NotBlank(["message" => "Please provide your CV"]),
                    new File([
                        "maxSize" => "3M",
                        "mimeTypes" => [
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/pdf',
                        ],
                    ]),
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => true,
        ]);
    }

}
