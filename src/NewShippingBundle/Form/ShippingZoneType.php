<?php

namespace App\NewShippingBundle\Form;

use App\NewShippingBundle\Entity\ShippingZone;
use App\NewShippingBundle\Entity\Zone;
use App\NewShippingBundle\Repository\ZoneRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingZoneType extends AbstractType
{

    public function __construct(private readonly ZoneRepository $zoneRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->zoneRepository->getUnusedZonesInShippingZones($builder->getData());
        $builder
            ->add('title')
            ->add('zones', EntityType::class, [
                'required' => true,
                'multiple' => true,
                "attr" => ['class' => "listbox"],
                'class' => Zone::class,
                "choices" => $choices,

            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => ShippingZone::class,
        ));
    }
}
