<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\Attribute;
use App\ProductBundle\Entity\ProductVariant;
use App\ProductBundle\Enum\ProductVariantTypeEnum;
use App\ProductBundle\Form\Translation\AttributeTranslationType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductVariantType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $disableTypeField = $options['disableTypeField'];

        if ($disableTypeField === false) {
            $builder
                ->add('type', EnumType::class, [
                    "class" => ProductVariantTypeEnum::class,
                    "attr" => ["class" => "select-search"],
                    'choice_label' => function ($choice, $key, $value) {
                        return $choice->name();
                    },
                ]);
        }
        $builder
            ->add('title')
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => AttributeTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductVariant::class,
            "label" => false,
            'disableTypeField' => false,
        ]);
    }

}
