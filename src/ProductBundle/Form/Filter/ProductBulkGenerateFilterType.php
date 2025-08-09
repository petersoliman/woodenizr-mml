<?php

namespace App\ProductBundle\Form\Filter;

use App\ProductBundle\Enum\ProductBulkGenerateTypeEnum;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductBulkGenerateFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('generatedFor', ChoiceType::class, [
                'choices' => array_merge(
                    ['All Types' => ''],
                    ProductBulkGenerateTypeEnum::getChoices()
                ),
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'All Statuses' => '',
                    'Pending' => 'pending',
                    'Running' => 'running',
                    'Completed' => 'completed',
                    'Failed' => 'failed',
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('adminId', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return sprintf('%s (%s)', $user->getFullName(), $user->getEmail());
                },
                'placeholder' => 'All Admins',
                'required' => false,
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
                }
            ])
            ->add('createdFrom', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker-metronic',
                    'placeholder' => 'Created from date...'
                ]
            ])
            ->add('createdTo', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker-metronic',
                    'placeholder' => 'Created to date...'
                ]
            ])
            ->add('startDateFrom', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker-metronic',
                    'placeholder' => 'Start date from...'
                ]
            ])
            ->add('startDateTo', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker-metronic',
                    'placeholder' => 'Start date to...'
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




