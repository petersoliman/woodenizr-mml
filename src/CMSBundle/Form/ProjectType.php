<?php

namespace App\CMSBundle\Form;

use App\CMSBundle\Entity\Project;
use App\CMSBundle\Form\Translation\ProjectTranslationType;
use App\ProductBundle\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $postTypeModel = new PostTypeModel();
        $postTypeModel->add("description", "Description");
        $postTypeModel->add("brief", "Brief");
        $builder
            ->add('title', TextType::class, [
                "label" => "Project Name"
            ])
            ->add('publish')
            ->add('featured')
            ->add('tarteb', IntegerType::class, [
                "label" => "Sort No.",
                'required' => false,
                "attr" => ['min' => 0],
            ])
            ->add('seo', SeoType::class)
            ->add('post', PostType::class, [
                "attributes" => $postTypeModel,
            ])
            ->add('data', CollectionType::class, [
                "entry_type" => ProjectDataType::class,
                "allow_add" => true,
                "allow_delete" => true,
                "prototype" => true,
                "label" => false,
                "by_reference" => false,
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => ProjectTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        $relatedProducts = [];
        if (array_key_exists('relatedProducts', $data)) {
            foreach ($data['relatedProducts'] as $relatedProduct) {
                if ($relatedProduct) {
                    $foundProduct = $this->em->getRepository(Product::class)->find($relatedProduct);
                    if ($foundProduct) {
                        $relatedProducts[] = $foundProduct;
                    }
                }
            }
        }
        $this->addRelatedProductElements($form, $relatedProducts);

        if (array_key_exists('data', $data)) {
            $features = $data['data'];
            $sortedFeatures = [];

            foreach ($features as $specification) {
                $sortedFeatures[] = $specification;
            }
            $data['data'] = $sortedFeatures;
            $event->setData($data);
        }
    }

    public function onPreSetData(FormEvent $event): void
    {
        $entity = $event->getData();
        $form = $event->getForm();

        // Check if entity exists and has relatedProducts before calling getRelatedProducts()
        $relatedProduct = [];
        if ($entity && method_exists($entity, 'getRelatedProducts')) {
            $relatedProduct = $entity->getRelatedProducts();
        }
        
        $this->addRelatedProductElements($form, $relatedProduct);
    }

    private function addRelatedProductElements(FormInterface $form, $relatedProduct = []): void
    {
        $form->add('relatedProducts', EntityType::class, [
            'required' => false,
            'multiple' => true,
            'placeholder' => 'Choose an option',
            'class' => Product::class,
            'choices' => $relatedProduct,
            'choice_label' => function ($product) {
                $title = $product->getTitle();
                if (!$product->isPublish()) {
                    $title .= " (Unpublished)";
                }
                return $title;
            },
            "attr" => [
//                "class" => "select-search"
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
