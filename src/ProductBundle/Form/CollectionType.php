<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\Collection;
use App\ProductBundle\Form\Translation\CollectionTranslationType;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\MediaBundle\Form\SingleImageType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PN\LocaleBundle\Form\Type\TranslationsType;

class CollectionType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $postTypeModel = new PostTypeModel();
        $postTypeModel->add("brief", "Brief", ["required" => false]);
//        $postTypeModel->add("description", "Description", ["required" => false]);
        $builder
            ->add('title')
            ->add('seo', SeoType::class)
            ->add('post', PostType::class, [
                "attributes" => $postTypeModel,
            ])
//            ->add('image', SingleImageType::class, ["mapped" => false])
            ->add('tarteb', IntegerType::class, [
                "required" => false,
                "label" => "Sort No.",
                'attr' => ['min' => 0],
            ])
            ->add('featured')
            ->add('publish')
            ->add('translations', TranslationsType::class, [
                'entry_type' => CollectionTranslationType::class,
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collection::class,
        ]);
    }

}
