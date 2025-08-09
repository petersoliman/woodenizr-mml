<?php

namespace App\ReportBundle\Form;

use PN\ServiceBundle\Utils\Date;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSalesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod("get")
            ->add('orderId', TextType::class, [
                "required" => false,
                "label" => "Order ID",
                "attr" => [
                    "placeholder" => "Order #",
                    "autocomplete" => "off",
                ],
            ])
            ->add('productName', TextType::class, [
                "required" => false,
                "label" => "Product Name",
                "attr" => [
                    "placeholder" => "Product Name",
                    "autocomplete" => "off",
                ],
            ])
            ->add('publish', ChoiceType::class, [
                "required" => false,
                "label" => "Published",
                "placeholder" => "All",
                "choices" => [
                    "Yes" => true,
                    "No" => false,
                ],
                "attr" => [
                    "class" => "select-search",
                ],
            ])
            ->add('featured', ChoiceType::class, [
                "required" => false,
                "placeholder" => "All",
                "choices" => [
                    "Yes" => true,
                    "No" => false,
                ],
                "attr" => [
                    "class" => "select-search",
                ],
            ])
            ->add('startDate', TextType::class, array(
                'required' => false,
                'attr' => ['class' => 'anytimepicker', "data-date-format"=>"dd/mm/yyyy"],
            ))
            ->add('endDate', TextType::class, array(
                'required' => false,
                'attr' => ['class' => 'anytimepicker', "data-date-format"=>"dd/mm/yyyy"],
            ))
            ->add('sortBy', ChoiceType::class, [
                "required" => false,
                "data" => 1,
                "choices" => [
                    "Total Qty" => 1,
                    "Total Sales" => 2,
                ],
                "attr" => [
                    "class" => "select-search",
                ],
            ]);

        $builder->get('startDate')
            ->addModelTransformer(new CallbackTransformer(
                    function ($date) {
                        if ($date == null) {
                            return null;
                        }

                        // transform the DateTime to a string
                        return $date->format('d/m/Y');
                    }, function ($date) {
                    if ($date == null) {
                        $date = null;
                    }

                    // transform the string back to DateTime
                    return Date::convertDateFormatToDateTime($date, Date::DATE_FORMAT3);
                })
            );
        $builder->get('endDate')
            ->addModelTransformer(new CallbackTransformer(
                    function ($date) {
                        if ($date == null) {
                            return null;
                        }

                        // transform the DateTime to a string
                        return $date->format('d/m/Y');
                    }, function ($date) {
                    if ($date == null) {
                        $date = null;
                    }

                    // transform the string back to DateTime
                    return Date::convertDateFormatToDateTime($date, Date::DATE_FORMAT3);
                })
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}