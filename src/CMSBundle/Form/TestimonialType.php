<?php

namespace App\CMSBundle\Form;

use App\CMSBundle\Entity\Testimonial;
use App\CMSBundle\Form\Translation\TestimonialTranslationType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TestimonialType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('client', TextType::class, [
                "constraints" => new NotBlank(),
            ])
            ->add('position')
            ->add("message", TextType::class, [
                "constraints" => new NotBlank(),
            ])
            ->add('publish')
            ->add('translations', TranslationsType::class, [
                'entry_type' => TestimonialTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Testimonial::class,
        ]);
    }
}
