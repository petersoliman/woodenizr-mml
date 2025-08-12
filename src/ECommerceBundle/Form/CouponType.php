<?php

namespace App\ECommerceBundle\Form;

use App\ECommerceBundle\Entity\Coupon;
use App\ECommerceBundle\Enum\CouponTypeEnum;
use PN\ServiceBundle\Utils\Date;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;

class CouponType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code')
            ->add('discountType', EnumType::class, array(
                'required' => true,
                "attr" => ["class" => "select-search"],
                "class" => CouponTypeEnum::class,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name();
                },
                'choice_attr' => function ($choice, $key, $value) {
                    return ["data-suffix" => $choice->suffix()];
                },
            ))
            ->add('startDate', TextType::class, array(
                'required' => true,
                'attr' => ['class' => 'datepicker'],
            ))
            ->add('expiryDate', TextType::class, array(
                'required' => true,
                'attr' => ['class' => 'datepicker'],
            ))
            ->add('freePaymentMethodFee')
            ->add('addDiscountAfterProductDiscount')
            ->add('firstOrderOnly', CheckboxType::class, [
                "label" => "Apply to the user's first order",
                "required" => false,
            ])
            ->add('discountValue')
            ->add('active', ChoiceType::class, [
                "attr" => ["class" => "select-search"],
                "choices" => [
                    "No" => false,
                    "Yes" => true,
                ],
            ])
            ->add('shipping', ChoiceType::class, [
                "attr" => ["class" => "select-search"],
                "choices" => [
                    "No" => false,
                    "Yes" => true,
                ],
                "label" => 'Assign Coupon To Shipping',
            ])
            ->add('description')
            ->add('comment')
            ->add('limitUsePerUser', NumberType::class, [
                'required' => false,
                "constraints" => [new GreaterThan(0)],
                'attr' => ['min' => 0],
            ])
            ->add('minimumPurchaseAmount', NumberType::class, [
                'required' => false,
                "constraints" => [new GreaterThan(0)],
                'attr' => ['min' => 0],
            ]);

        $builder->get('startDate')
            ->addModelTransformer(new CallbackTransformer(
                    function ($date) {
                        if ($date == null) {
                            return date('d/m/Y');
                        }

                        // transform the DateTime to a string
                        return $date->format('d/m/Y');
                    }, function ($date) {
                    if ($date == null) {
                        $date = date('d/m/Y');
                    }

                    // transform the string back to DateTime
                    return Date::convertDateFormatToDateTime($date, Date::DATE_FORMAT3);
                })
            );
        $builder->get('expiryDate')
            ->addModelTransformer(new CallbackTransformer(
                    function ($date) {
                        if ($date == null) {
                            return date('d/m/Y');
                        }

                        // transform the DateTime to a string
                        return $date->format('d/m/Y');
                    }, function ($date) {
                    if ($date == null) {
                        $date = date('d/m/Y');
                    }

                    // transform the string back to DateTime
                    return Date::convertDateFormatToDateTime($date, Date::DATE_FORMAT3);
                })
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Coupon::class,
        ]);
    }
}
