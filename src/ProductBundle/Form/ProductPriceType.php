<?php

namespace App\ProductBundle\Form;

use App\BaseBundle\SystemConfiguration;
use App\ProductBundle\Entity\ProductPrice;
use PN\ServiceBundle\Utils\Date;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductPriceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('unitPrice', TextType::class, [
                "attr" => ["min" => 0, "data-required" => "true"],
                "constraints" => [
                    new NotBlank(),
                    new GreaterThanOrEqual(0),
                ],
            ])
            ->add('promotionalPrice', TextType::class, [
                "required" => false,
                "attr" => [
                    "min" => 0,
                    "data-required" => "false",
                ],
                "constraints" => [
                    new LessThan([
                        "propertyPath" => "parent.all[unitPrice].data",
                        "message" => "Must be less than unit price",
                    ]),
                ],
            ])
            ->add('stock', IntegerType::class, [
                "required" => true,
                "attr" => ["min" => 0, "data-required" => "true"],
                "constraints" => [
                    new NotBlank(),
                    new GreaterThanOrEqual(0),
                ],
            ])
            ->add('weight', TextType::class, [
                "attr" => ["min" => 0],
                "constraints" => [
                    new NotBlank(),
                    new GreaterThanOrEqual(0),
                ],
                "required" => true,
            ])
            ->add('promotionalExpiryDate', TextType::class, [
                "required" => false,
                "attr" => [
                    "class" => "datepicker",
                    "data-required" => "false",
                ],
            ]);
        if (SystemConfiguration::PRODUCT_PRICE_TYPE->hasTitle()) {
            $builder->add('title', TextType::class, [
                "required" => false,
//                "attr" => ["data-required" => "true"],
                "constraints" => [
                    new NotBlank(),
                ],
            ]);
        }

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);

        $builder->get('promotionalExpiryDate')
            ->addModelTransformer(new CallbackTransformer(
                    function ($date) {
                        if ($date == null) {
                            return null;
                        }

                        return $date->format('d/m/Y');
                    }, function ($date) {
                    if ($date == null) {
                        return null;
                    }

                    return Date::convertDateFormatToDateTime($date, Date::DATE_FORMAT3);
                })
            );


    }

    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();
        if (!$entity->getPromotionalExpiryDate() && $entity->getPromotionalPrice()) {
            $form->get('promotionalExpiryDate')->addError(new FormError("Please enter the promotional expiry date"));
        }
        if ($entity->getPromotionalExpiryDate() && !$entity->getPromotionalPrice()) {
            $form->get('promotionalPrice')->addError(new FormError("Please enter the promotional price"));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductPrice::class,
        ]);
    }
}