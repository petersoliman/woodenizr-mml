<?php

namespace App\CMSBundle\Form;

use App\CMSBundle\Entity\Team;
use App\CMSBundle\Form\Translation\TeamTranslationType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\MediaBundle\Form\SingleImageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('position')
            ->add('image', SingleImageType::class, ["mapped" => false])
            //                ->add('shortDesc', TextareaType::class, [
            //                    "label" => "Short description",
            //                    'attr' => ['rows' => 5]
            //                ])
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('publish')
            ->add('translations', TranslationsType::class, [
                'entry_type' => TeamTranslationType::class,
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
            'data_class' => Team::class,
        ]);
    }

}
