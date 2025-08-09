<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\Occasion;
use App\ProductBundle\Form\Translation\OccasionTranslationType;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\SeoBundle\Form\SeoType;
use PN\ServiceBundle\Utils\Date;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use PN\LocaleBundle\Form\Type\TranslationsType;

class OccasionType extends AbstractType
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
            ->add('seo', SeoType::class)
            ->add('post', PostType::class, [
                "attributes" => $postTypeModel,
            ])
            ->add('active', CheckboxType::class, [
                "required" => false,
                "label" => "Active",
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => OccasionTranslationType::class,
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
            'data_class' => Occasion::class,
        ]);
    }

}
