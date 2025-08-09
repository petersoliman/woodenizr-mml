<?php

namespace App\NewShippingBundle\Form;

use App\NewShippingBundle\Entity\ShippingZonePrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType as CollectionTypee;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class ShippingPriceSpecificWeightModelType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('extraKgRate', TextType::class, [
                "required" => true,
                'property_path' => 'configuration[extraKgRate]',
                "attr" => ["class" => "only-float"],
                "constraints" => [
                    new NotBlank(),
                    new GreaterThanOrEqual(0),
                ],
            ])
//            ->add('maxWeight', NumberType::class, [
//                "required" => true,
//                'property_path' => 'configuration[maxWeight]',
//                "attr" => ["class" => "only-float"],
//                "constraints" => [
//                    new NotBlank(),
//                    new GreaterThan(0),
//                ],
//            ])
            ->add("specificWeights", CollectionTypee::class, array(
                "entry_type" => ShippingPriceSpecificWeightType::class,
                "allow_add" => true,
                "allow_delete" => true,
                "prototype" => true,
                "label" => false,
                "constraints" => [
                    new Count([
                            "min" => 1,
                            'minMessage' => 'Must have at least one weight',
                        ]
                    ),
                ],
                "by_reference" => false,
            ));;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => ShippingZonePrice::class,
        ));
    }
}
