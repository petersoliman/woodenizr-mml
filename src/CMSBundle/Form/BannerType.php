<?php

namespace App\CMSBundle\Form;

use App\CMSBundle\Entity\Banner;
use App\CMSBundle\Enum\BannerActionButtonPositionEnum;
use App\CMSBundle\Enum\BannerPlacementEnum;
use App\CMSBundle\Form\Translation\BannerTranslationType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\MediaBundle\Form\SingleImageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BannerType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('placement', EnumType::class, [
                "class" => BannerPlacementEnum::class,
                "attr" => ["class" => "select-search"],
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name();
                },
            ])
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('publish')
            ->add('actionButtonName', TextType::class, [
                "required" => false,
                "label" => "Action Button Name",
            ])
            ->add('actionButtonPosition', EnumType::class, [
                "class" => BannerActionButtonPositionEnum::class,
                "attr" => ["class" => "select-search"],
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name();
                },
            ])
            ->add('url', UrlType::class, ['required' => false])
            ->add('text', TextareaType::class, ['label' => 'Banner Text', 'required' => false])
            ->add('openInNewTab')
            ->add('image', SingleImageType::class, ["mapped" => false])
            ->add('translations', TranslationsType::class, [
                'entry_type' => BannerTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);

    }

    public function onPreSetData(FormEvent $event): void
    {
        $entity = $event->getData();
        $form = $event->getForm();

        $placementName = null;
        if ($entity->getPlacement() instanceof BannerPlacementEnum) {
            $placementName = $entity->getPlacement()->name();
        }
        $entity->setPlacementName($placementName);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Banner::class,
        ]);
    }

}
