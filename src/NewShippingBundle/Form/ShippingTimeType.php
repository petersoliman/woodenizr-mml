<?php

namespace App\NewShippingBundle\Form;

use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Form\Translation\ShippingTimeTranslationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PN\LocaleBundle\Form\Type\TranslationsType;

class ShippingTimeType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('noOfDeliveryDays', IntegerType::class, [
                "required" => false,
                "label" => "Number of delivery days",
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => ShippingTimeTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => ShippingTime::class,
        ));
    }

}
