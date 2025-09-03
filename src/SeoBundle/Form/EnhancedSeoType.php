<?php

namespace App\SeoBundle\Form;

use PN\SeoBundle\Form\SeoType as BaseSeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Enhanced SEO Form Type with additional fields for better meta tag management
 */
class EnhancedSeoType extends BaseSeoType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Call parent to get base SEO fields
        parent::buildForm($builder, $options);

        // Add enhanced SEO fields
        $builder
            ->add('canonicalUrl', UrlType::class, [
                'label' => 'Canonical URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://example.com/page',
                    'class' => 'form-control',
                ],
                'help' => 'Leave empty to use current page URL',
            ])
            ->add('robots', TextType::class, [
                'label' => 'Robots Meta Tag',
                'required' => false,
                'attr' => [
                    'placeholder' => 'index, follow, max-snippet:-1, max-image-preview:large',
                    'class' => 'form-control',
                ],
                'help' => 'Default: index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
            ])



            ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults([
            'data_class' => \App\SeoBundle\Entity\Seo::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'app_seobundle_enhanced_seo';
    }
}
