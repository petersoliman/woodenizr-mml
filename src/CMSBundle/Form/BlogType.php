<?php

namespace App\CMSBundle\Form;

use App\CMSBundle\Entity\Blog;
use App\CMSBundle\Entity\BlogCategory;
use App\CMSBundle\Entity\BlogTag;
use App\CMSBundle\Form\Translation\BlogTranslationType;
use Doctrine\ORM\EntityRepository;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\SeoBundle\Form\SeoType;
use PN\ServiceBundle\Utils\Date;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlogType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $postTypeModel = new PostTypeModel();


        $builder
            ->add('title')
            ->add('subtitle', TextType::class, ["required" => false])
            ->add('publish', CheckboxType::class, [
                "label" => "Published",
            ])
            ->add('featured')
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('date', TextType::class, [
                "required" => false,
                "attr" => [
                    "class" => "datepicker",
                ],
            ])
            ->add('seo', SeoType::class)
            ->add('post', PostType::class)
            ->add('tags', EntityType::class, [
                'required' => false,
                'multiple' => true,
                "attr" => ["class" => "select-search"],
                'class' => BlogTag::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('bt')
                        ->where('bt.deleted IS NULL ')
                        ->orderBy('bt.id', 'DESC');
                },
            ])
            ->add('category', EntityType::class, [
                'required' => false,
                "placeholder" => "Choose an option",
                'class' => BlogCategory::class,
                "attr" => ["class" => "select-search"],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('bc')
                        ->where('bc.deleted IS NULL ')
                        ->orderBy('bc.id', 'DESC');
                },
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => BlogTranslationType::class,
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

        $builder->get('date')
            ->addModelTransformer(new CallbackTransformer(
                    function ($date) {
                        if ($date == null) {
                            return date('d/m/Y');
                        }

                        // transform the DateTime to a string
                        return $date->format('d/m/Y');
                    }, function ($date) {
                    if ($date == null) {
                        $date = date('d/m/Y');
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
            'data_class' => Blog::class,
        ]);
    }

}
