<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\ProductBulkGenerate;
use App\ProductBundle\Enum\ProductBulkGenerateTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
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
            ->add('startTimeOption', ChoiceType::class, [
                'choices' => [
                    'Immediately' => 'now',
                    'In 5 minutes' => '5min',
                    'In 15 minutes' => '15min',
                    'In 30 minutes' => '30min',
                    'In 1 hour' => '1hour',
                    'In 2 hours' => '2hours',
                    'In 4 hours' => '4hours',
                    'In 8 hours' => '8hours',
                    'Tomorrow morning (9 AM)' => 'tomorrow_9am',
                    'Tomorrow afternoon (2 PM)' => 'tomorrow_2pm',
                    'Custom time' => 'custom'
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'When to start the bulk generation process'
            ])
            ->add('customStartTime', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datetimepicker',
                    'placeholder' => 'Select custom start date and time...',
                    'style' => 'display: none;'
                ],
                'help' => 'Custom start date and time (only shown when "Custom time" is selected)'
            ])
            // Admin field removed - will be set automatically from session
            // Total recommendations field removed - will be calculated automatically
            ->add('adminNote', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Add any notes about this bulk generation...'
                ],
                'help' => 'Optional notes or comments about this bulk generation process'
            ])
            // Status, processedCount, and errorCount fields removed - will be set automatically during processing
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductBulkGenerate::class,
        ]);
    }
}




