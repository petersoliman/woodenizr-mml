<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\Attribute;
use App\ProductBundle\Form\Translation\AttributeTranslationType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $disableTypeField = $options['disableTypeField'];

        if ($disableTypeField === false) {
            $builder->add('type', ChoiceType::class, [
                'choices' => Attribute::$types,
                "attr" => ["class" => "select-search"],
            ]);
        }
        $builder
            ->add('title', TextType::class, [
                "label" => "Spec",
            ])
            ->add('search')
            ->add('mandatory')
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => AttributeTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $entity = $event->getData();

        if (in_array($entity->getType(), [Attribute::TYPE_NUMBER, Attribute::TYPE_TEXT])) {
            $entity->setSearch(false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Attribute::class,
            //            "allow_extra_fields" => true,
            "label" => false,
            'disableTypeField' => false,
        ]);
    }

}
