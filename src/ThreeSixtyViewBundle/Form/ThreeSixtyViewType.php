<?php

namespace App\ThreeSixtyViewBundle\Form;

use App\ThreeSixtyViewBundle\Entity\ThreeSixtyView;
use App\ThreeSixtyViewBundle\Enums\ThreeSixtyViewImageExtensionEnums;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThreeSixtyViewType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('imageExtension', EnumType::class, [
                "class" => ThreeSixtyViewImageExtensionEnums::class,
                "attr" => ["class" => "select-search"],
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name();
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ThreeSixtyView::class,
        ]);
    }
}