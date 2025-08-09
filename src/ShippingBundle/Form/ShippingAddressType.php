<?php

namespace App\ShippingBundle\Form;

use App\ShippingBundle\Entity\ShippingAddress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;
use App\ShippingBundle\Entity\City;

class ShippingAddressType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                "label" => "Address Title",
                "attr" => ["placeholder" => "Name your address ex. My home, My work, etc..."],
            ])
            ->add('fullAddress', TextType::class, [
                "constraints" => [
                    new NotBlank(),
                ],
                "attr" => [
                    "placeholder" => "Street address, apartment, suite, floor, etc",
                ],
            ])
            ->add('note')
            ->add('zone', EntityType::class, [
                "label" => "Zone",
                "constraints" => [new NotBlank()],
                'class' => City::class,
                "placeholder" => "Please select your zone",
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.deleted IS NULL')
                        ->orderBy('t.title', 'ASC');
                },
            ])
            ->add('mobileNumber', TextType::class, [
                "attr" => [
                    "maxlength" => "11",
                    "placeholder" => "01x xxxx xxxx",
                    "class" => "only-numeric",
                ],
                "label" => "Mobile",
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            "data_class" => ShippingAddress::class,
        ]);
    }

}
