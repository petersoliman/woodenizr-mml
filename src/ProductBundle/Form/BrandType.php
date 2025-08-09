<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\Brand;
use App\ProductBundle\Form\Translation\BrandTranslationType;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BrandType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $postTypeModel = new PostTypeModel();
        $postTypeModel->add("brief", "Brief", ["required" => false]);
//        $postTypeModel->add("description", "Description", ["required" => false]);
        $builder
            ->add('title')
            ->add('featured')
            ->add('publish')
            ->add('jpId')
            ->add('seo', SeoType::class)
            ->add('post', PostType::class, [
                "attributes" => $postTypeModel,
            ])
            
            ->add('translations', TranslationsType::class, [
                'entry_type' => BrandTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Brand::class,
        ]);
    }

}
