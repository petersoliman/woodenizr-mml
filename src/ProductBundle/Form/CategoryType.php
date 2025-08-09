<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\Category;
use App\ProductBundle\Form\Translation\CategoryTranslationType;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\MediaBundle\Form\SingleImageType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PN\LocaleBundle\Form\Type\TranslationsType;

class CategoryType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $postTypeModel = new PostTypeModel();
        $postTypeModel->add("brief", "Brief", ["required" => false]);

        $builder
            ->add('title')
            ->add('publish')
            ->add('featured')
            ->add('seo', SeoType::class)
            ->add('post', PostType::class,[
                "attributes" => $postTypeModel,
            ])
            ->add('image', SingleImageType::class, ["mapped" => false])
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => CategoryTranslationType::class,
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
