<?php

namespace App\CustomBundle\Form;

use App\CustomBundle\Entity\OriginalDesign;
use App\CustomBundle\Form\Translation\OriginalDesignTranslationType;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OriginalDesignType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $postTypeModel = new PostTypeModel();


        $builder
            ->add('title')
            ->add('publish', CheckboxType::class, [
                "label" => "Published",
            ])
            ->add('featured')
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('seo', SeoType::class)
            ->add('post', PostType::class)
            ->add('translations', TranslationsType::class, [
                'entry_type' => OriginalDesignTranslationType::class,
                //                    'query_builder' => function(EntityRepository $er) {
                //                        return $er->createQueryBuilder('languages')
                //                                ->where("languages.locale = 'fr'");
                //                    }, // optional
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
            'data_class' => OriginalDesign::class,
        ]);
    }

}
