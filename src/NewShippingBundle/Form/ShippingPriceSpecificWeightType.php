<?php

namespace App\NewShippingBundle\Form;

use App\NewShippingBundle\Entity\ShippingZonePriceSpecificWeight;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class ShippingPriceSpecificWeightType extends AbstractType
{

    private $container;
    private $em;
    private $addedWeights = [];

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('weight', TextType::class, [
                "constraints" => [
                    new NotBlank(),
                    new GreaterThanOrEqual(0),
                ],
                "required" => true,
            ])
            ->add('rate', NumberType::class, [
                "attr" => ["class" => "only-float"],
                "constraints" => [
                    new NotBlank(),
                    new GreaterThanOrEqual(0),
                ],
                "required" => true,
            ])
            ->addEventListener(FormEvents::SUBMIT, [$this, 'onPreSubmit']);

    }

    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        $this->addCreatorAndModifiedBy($data);
        $this->validate($form, $data);
    }

    private function addCreatorAndModifiedBy(ShippingZonePriceSpecificWeight $shippingZonePriceSpecificWeight): void
    {
        $userName = $this->container->get('user')->getUserName();
        if ($shippingZonePriceSpecificWeight->getId() == null) {
            $shippingZonePriceSpecificWeight->setCreator($userName);
        }
        $shippingZonePriceSpecificWeight->setModifiedBy($userName);
    }

    private function validate(Form $form, ShippingZonePriceSpecificWeight $shippingZonePriceSpecificWeight)
    {

        if (!in_array($shippingZonePriceSpecificWeight->getWeight(), $this->addedWeights)) {
            $this->addedWeights[] = $shippingZonePriceSpecificWeight->getWeight();
        } else {
            $form->get('weight')->addError(new FormError("This weight is already exist"));
        }


//        $shippingZonePrice = $form->getParent()->getParent()->getData();
//
//        $isExist = $this->em->getRepository(ShippingZonePriceSpecificWeight::class)->checkIfWeightExist(
//            $shippingZonePrice,
//            $shippingZonePriceSpecificWeight->getWeight(),
//            $shippingZonePriceSpecificWeight
//        );
//        if ($isExist) {
//            $form->get('weight')->addError(new FormError("This weight is already exist"));
//        }
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => ShippingZonePriceSpecificWeight::class,
            "error_bubbling" => true,
        ));
    }
}
