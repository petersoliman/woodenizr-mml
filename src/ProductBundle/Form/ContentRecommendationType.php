<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\ContentRecommendation;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Enum\ContentRecommendationStateEnum;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContentRecommendationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function (Product $product) {
                    return sprintf('#%d - %s', $product->getId(), $product->getTitle());
                },
                'placeholder' => 'Select a product...',
                'required' => true,
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Select a product...'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.deleted IS NULL')
                        ->andWhere('p.publish = :publish')
                        ->setParameter('publish', true)
                        ->orderBy('p.title', 'ASC');
                },
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please select a product'])
                ]
            ])
            ->add('recommendedJson', TextareaType::class, [
                'label' => 'Recommended Content (JSON)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Enter recommended content in JSON format...'
                ],
                'mapped' => false,
                'help' => 'Enter the recommended content as valid JSON. Example: {"type": "product", "ids": [1, 2, 3]}'
            ])
            ->add('state', ChoiceType::class, [
                'choices' => ContentRecommendationStateEnum::getChoices(),
                'placeholder' => 'Select state...',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please select a state'])
                ]
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Add any notes or comments...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContentRecommendation::class,
        ]);
    }
}




