<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\ProductBulkGenerate;
use App\ProductBundle\Enum\ProductBulkGenerateTypeEnum;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProductBulkGenerateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('generatedFor', ChoiceType::class, [
                'choices' => ProductBulkGenerateTypeEnum::getChoices(),
                'placeholder' => 'Select generation type...',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please select a generation type'])
                ],
                'help' => 'Choose what type of bulk generation to perform'
            ])
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'form-control datetimepicker',
                    'placeholder' => 'Select start date and time...'
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please select a start date'])
                ],
                'help' => 'When the bulk generation process started or will start'
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datetimepicker',
                    'placeholder' => 'Select end date and time...'
                ],
                'help' => 'When the bulk generation process ended (leave empty for ongoing processes)'
            ])
            ->add('admin', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return sprintf('%s (%s)', $user->getFullName(), $user->getEmail());
                },
                'placeholder' => 'Select admin user...',
                'required' => true,
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Select admin user...'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.deleted IS NULL')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_ADMIN%')
                        ->orderBy('u.fullName', 'ASC');
                },
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please select an admin user'])
                ],
                'help' => 'The admin user responsible for this bulk generation'
            ])
            ->add('totalRecommendations', IntegerType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Enter total number of recommendations...'
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please enter the total recommendations']),
                    new Assert\PositiveOrZero(['message' => 'Total recommendations must be 0 or greater'])
                ],
                'help' => 'The total number of recommendations to be generated'
            ])
            ->add('adminNote', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Add any notes about this bulk generation...'
                ],
                'help' => 'Optional notes or comments about this bulk generation process'
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Running' => 'running',
                    'Completed' => 'completed',
                    'Failed' => 'failed',
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Current status of the bulk generation process'
            ])
            ->add('processedCount', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Number of processed items...'
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Processed count must be 0 or greater'])
                ],
                'help' => 'Number of items successfully processed'
            ])
            ->add('errorCount', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Number of errors...'
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Error count must be 0 or greater'])
                ],
                'help' => 'Number of errors encountered during processing'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductBulkGenerate::class,
        ]);
    }
}




