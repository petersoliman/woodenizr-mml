<?php

namespace App\CMSBundle\Form;

use App\CMSBundle\Entity\BlogCategory;
use App\CMSBundle\Form\Translation\BlogCategoryTranslationType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlogCategoryType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('seo', SeoType::class)
            ->add('publish')
            ->add('translations', TranslationsType::class, [
                'entry_type' => BlogCategoryTranslationType::class,
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
            'data_class' => BlogCategory::class,
        ]);
    }

}
