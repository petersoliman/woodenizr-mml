<?php

namespace App\CMSBundle\Form;

use App\CMSBundle\Entity\DynamicPage;
use App\CMSBundle\Form\Translation\DynamicPageTranslationType;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DynamicPageType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $postTypeModel = new PostTypeModel();
        $postTypeModel->add("description", "Description");
        $postTypeModel->add("brief", "Brief", ["required" => false]);

        $builder
            ->add('title')
            ->add('seo', SeoType::class)
            ->add('post', PostType::class, [
                "attributes" => $postTypeModel,
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => DynamicPageTranslationType::class,
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
            'data_class' => DynamicPage::class,
        ]);
    }

}
