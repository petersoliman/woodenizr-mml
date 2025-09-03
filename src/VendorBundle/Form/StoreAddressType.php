<?php

namespace App\VendorBundle\Form;

use App\NewShippingBundle\Entity\Zone;
use App\VendorBundle\Entity\StoreAddress;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StoreAddressType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullAddress', TextType::class)
            ->add('zone', EntityType::class, array(
                "label" => "City",
                "constraints" => [new NotBlank()],
                'class' => Zone::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.deleted IS NULL')
                        ->orderBy('t.title', 'ASC');
                },
            ))
            ->add('mobileNumber');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => StoreAddress::class,
        ));
    }

}
