<?php

namespace App\UserBundle\Form\Filter;

use PN\ServiceBundle\Utils\Date;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilterType extends AbstractType
{


    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->setMethod("get")
            ->add('str', TextType::class, [
                "required" => false,
                "label" => "Search",
                "attr" => [
                    "placeholder" => "#, Name, Email or Phone",
                    "autocomplete" => "off",
                ],
            ])
            ->add('createdFrom', TextType::class, [
                "label" => "Reg. Date from",
                "required" => false,
                "attr" => ["class" => "anytimepicker", "readonly" => true, "placeholder" => "Reg. Date from"],
            ])
            ->add('createdTo', TextType::class, [
                "label" => "Reg. Date to",
                "required" => false,
                "attr" => ["class" => "anytimepicker", "readonly" => true, "placeholder" => "Reg. Date to"],
            ])
            ->add('enabled', ChoiceType::class, [
                "required" => false,
                "label" => "Status",
                "placeholder" => "All",
                "choices" => [
                    "Active" => 1,
                    "Blocked" => 0,
                ],
                "attr" => [
                    "class" => "select-search",
                ],
            ]);

        $builder->get('createdFrom')
            ->addModelTransformer(new CallbackTransformer(
                    function ($date) {
                        if ($date == null) {
                            return null;
                        }

                        // transform the DateTime to a string
                        return $date->format('d/m/Y');
                    }, function ($date) {
                    // transform the string back to DateTime
                    if ($date) {
                        return Date::convertDateFormatToDateTime($date, Date::DATE_FORMAT3);
                    }

                    return null;
                })
            );
        $builder->get('createdTo')
            ->addModelTransformer(new CallbackTransformer(
                    function ($date) {
                        if ($date == null) {
                            return null;
                        }

                        // transform the DateTime to a string
                        return $date->format('d/m/Y');
                    }, function ($date) {
                    // transform the string back to DateTime
                    if ($date) {
                        return Date::convertDateFormatToDateTime($date, Date::DATE_FORMAT3);
                    }

                    return null;
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
