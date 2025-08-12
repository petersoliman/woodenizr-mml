<?php

namespace App\NewShippingBundle\Form;

use App\NewShippingBundle\Entity\Zone;
use App\NewShippingBundle\Form\Translation\ZoneTranslationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PN\LocaleBundle\Form\Type\TranslationsType;

class ZoneType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('title')
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => ZoneTranslationType::class,
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => Zone::class,
        ));
    }

}
