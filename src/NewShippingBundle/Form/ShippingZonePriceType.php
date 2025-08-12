<?php

namespace App\NewShippingBundle\Form;

use App\CurrencyBundle\Entity\Currency;
use App\NewShippingBundle\Entity\Courier;
use App\NewShippingBundle\Entity\ShippingZone;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingZonePriceType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('sourceShippingZone', EntityType::class, [
                'required' => true,
                "placeholder" => "Choose an option",
                "attr" => ["class" => "select-search"],
                'class' => ShippingZone::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.deleted IS NULL')
                        ->orderBy('t.id', 'ASC');
                },
            ])
            ->add('targetShippingZone', EntityType::class, [
                'required' => true,
                "placeholder" => "Choose an option",
                "attr" => ["class" => "select-search"],
                'class' => ShippingZone::class,
                'query_builder' => function (EntityRepository $er)  {
                    return $er->createQueryBuilder('sz')
                        ->leftJoin('sz.zones', 'szzs')
                        ->andWhere('sz.deleted IS NULL')
                        ->andWhere('szzs.deleted IS NULL')
                        ->orderBy('sz.id', 'ASC');
                },
            ])
            ->add('currency', EntityType::class, [
                'required' => true,
                "placeholder" => "Choose an option",
                "attr" => ["class" => "select-search"],
                'class' => Currency::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.deleted IS NULL')
                        ->andWhere("t.id IN (1,2)") // Shipping price with EGP AND USD only
                        ->orderBy('t.id', 'ASC');
                },
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->getTitle() . " (" . $choice->getSymbol() . ")";
                },
            ])
            ->add('courier', EntityType::class, [
                'required' => true,
                "placeholder" => "Choose an option",
                "attr" => ["class" => "select-search"],
                'class' => Courier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.deleted IS NULL')
                        ->orderBy('t.id', 'ASC');
                },
            ]);
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
