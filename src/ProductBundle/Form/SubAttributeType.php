<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\SubAttribute;
use App\ProductBundle\Form\Translation\SubAttributeTranslationType;
use Doctrine\ORM\EntityManagerInterface;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Form\Type\TranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubAttributeType extends AbstractType
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $languages = $this->em->getRepository(Language::class)->findAll();
        $entryLanguageOptions = [
            "en" => ["required" => true],
        ];
        foreach ($languages as $language) {
            $entryLanguageOptions[$language->getLocale()] = ["required" => true];
        }

        $builder
            ->add('title', TextType::class, [
                "label" => "Add New Attribute",
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => SubAttributeTranslationType::class,
                //                    'query_builder' => function(EntityRepository $er) {
                //                        return $er->createQueryBuilder('languages')
                //                                ->where("languages.locale = 'fr'");
                //                    }, // optional
                "label" => false,
                'entry_language_options' => $entryLanguageOptions,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();

    }

    public function preSubmit(FormEvent $event)
    {
        $entity = $event->getData();
        $form = $event->getForm();

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SubAttribute::class,
            "label" => false,
        ]);
    }

}
