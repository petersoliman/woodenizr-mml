<?php

namespace App\ProductBundle\Form\Filter;

use App\ProductBundle\Entity\Product;
use App\ProductBundle\Enum\ContentRecommendationStateEnum;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentRecommendationFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productTitle', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Search by product title...'
                ]
            ])
            ->add('productId', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function (Product $product) {
                    return sprintf('#%d - %s', $product->getId(), $product->getTitle());
                },
                'placeholder' => 'Select a specific product...',
                'required' => false,
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Select a specific product...'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.deleted IS NULL')
                        ->orderBy('p.title', 'ASC');
                }
            ])
            ->add('state', ChoiceType::class, [
                'choices' => array_merge(
                    ['All States' => ''],
                    ContentRecommendationStateEnum::getChoices()
                ),
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('createdFrom', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker-metronic',
                    'placeholder' => 'From date...'
                ]
            ])
            ->add('createdTo', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker-metronic',
                    'placeholder' => 'To date...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}




