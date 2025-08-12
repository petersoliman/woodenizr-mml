<?php

namespace App\NewShippingBundle\Form;

use App\NewShippingBundle\Entity\ShippingZonePrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class ShippingPriceFirstAndExtraKgType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstNoOfKg', IntegerType::class, [
                "required" => true,
                "attr" => ["class" => "only-float"],
                'property_path' => 'configuration[firstNoOfKg]',
                "constraints" => [
                    new NotBlank(),
                    new GreaterThanOrEqual(1),
                ],
            ])->add('firstKgRate', NumberType::class, [
                "required" => true,
                "attr" => ["class" => "only-float"],
                'property_path' => 'configuration[firstKgRate]',
                "constraints" => [
                    new NotBlank(),
                    new GreaterThan(0),
                ],
            ])
            ->add('extraKgRate', NumberType::class, [
                "required" => true,
                "attr" => ["class" => "only-float"],
                'property_path' => 'configuration[extraKgRate]',
                "constraints" => [
                    new NotBlank(),
                    new GreaterThan(0),
                ],
            ])
            ->add('moreThanKg', NumberType::class, [
                "required" => false,
                "attr" => ["class" => "only-float"],
                'property_path' => 'configuration[moreThanKg]',
                "constraints" => [
                    new GreaterThan(0),
                ],
            ])
            ->add('moreKgRate', NumberType::class, [
                "required" => false,
                "attr" => ["class" => "only-float"],
                'property_path' => 'configuration[moreKgRate]',
                "constraints" => [
                    new GreaterThan(0),
                ],
            ])
            //            ->add('maxWeight', NumberType::class, [
            //                "required" => true,
            //                "attr" => ["class" => "only-float"],
            //                'property_path' => 'configuration[maxWeight]',
            //                "constraints" => [
            //                    new NotBlank(),
            //                    new GreaterThan(0),
            //                ],
            //            ])
        ;
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);

    }

    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $configuration = $data->getConfiguration();
        $moreThanKg = null;
        if (array_key_exists("moreThanKg", $configuration)) {
            $moreThanKg = $configuration['moreThanKg'];
        } else {
            $configuration["moreThanKg"] = NULL;
        }
        $moreKgRate = null;
        if (array_key_exists("moreKgRate", $configuration)) {
            $moreKgRate = $configuration['moreKgRate'];
        } else {
            $configuration["moreKgRate"] = null;
        }

        if ($moreThanKg == null and $moreKgRate != null) {
            $form->get('moreThanKg')->addError(new FormError("This field is required"));
        } elseif ($moreThanKg != null and $moreKgRate == null) {
            $form->get('moreKgRate')->addError(new FormError("This field is required"));
        }

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ShippingZonePrice::class,
        ));
    }
}
