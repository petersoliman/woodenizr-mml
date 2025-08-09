<?php

namespace App\ProductBundle\Form;

use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\ProductVariantOption;
use App\ProductBundle\Enum\ProductVariantTypeEnum;
use App\ProductBundle\Form\Translation\ProductVariantOptionTranslationType;
use Doctrine\ORM\EntityManagerInterface;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Form\Type\TranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductVariantOptionType extends AbstractType
{
    private EntityManagerInterface $em;
    private ?ProductVariantTypeEnum $productVariantTypeEnum = null;
    private ?ProductVariantOption $productVariantOption = null;

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
                "label" => "Add New Variant",
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => ProductVariantOptionTranslationType::class,
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
        $this->productVariantTypeEnum = $entity->getVariant()->getType();
        $this->productVariantOption = $entity;

        if ($this->productVariantTypeEnum == ProductVariantTypeEnum::IMAGE) {
            $this->addImageField($form);
        } elseif ($this->productVariantTypeEnum == ProductVariantTypeEnum::COLOR) {
            $this->addColorField($form);
        }
    }

    public function preSubmit(FormEvent $event)
    {
        $entity = $event->getData();
        $form = $event->getForm();
        if ($this->productVariantTypeEnum == ProductVariantTypeEnum::IMAGE) {
            $this->addImageField($form);
        } elseif ($this->productVariantTypeEnum == ProductVariantTypeEnum::COLOR) {
            $this->addColorField($form);
        }
    }

    private function addImageField(FormInterface $form)
    {
        $required = true;
        if ($this->productVariantOption->getId() != null and $this->productVariantOption->getImage() instanceof Image) {
            $required = false;
        }

        $form
            ->add("image", FileType::class, [
                "mapped" => false,
                "required" => $required,
                "attr" => [
                    "accept" => "image/*",
                ],
                "constraints" => [
                    new File([
                        "mimeTypes" => [
                            "image/png",
                            "image/jpeg",
                            "image/gif",
                        ],
                    ]),
                ],
            ]);
    }

    private function addColorField(FormInterface $form)
    {
        $form->add('value', TextType::class, [
            "label" => "Color",
            "label_attr" => ["class" => "display-block"],
            "attr" => [
                "type" => "color",
                "class" => "color-picker",
                "data-preferred-format" => "hex",
            ],
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductVariantOption::class,
            "label" => false,
        ]);
    }

}
