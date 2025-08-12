<?php

namespace App\ECommerceBundle\Form;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Enum\ShippingStatusEnum;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("state", EnumType::class, [
                "required" => false,
                "class" => OrderStatusEnum::class,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name();
                },
            ])
            ->add("waybillNumber")
            ->add("shippingState", EnumType::class, [
                "required" => false,
                "class" => ShippingStatusEnum::class,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name();
                },
            ])
            ->add("paymentState", EnumType::class, [
                "required" => false,
                "class" => PaymentStatusEnum::class,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name();
                },
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }

}
