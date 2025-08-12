<?php

namespace App\CMSBundle\Form\Translation;

use App\CMSBundle\Entity\Translation\BannerTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BannerTranslationType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            //                ->add('subTitle', null, ['required' => false])
            ->add('url', UrlType::class, ['required' => false])
            ->add('text', TextareaType::class, ['label' => 'Banner Text', 'required' => false])
            ->add('actionButton', TextType::class, [
                "required" => false,
                "label" => "Action Button Name",
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BannerTranslation::class,
        ]);
    }
}
