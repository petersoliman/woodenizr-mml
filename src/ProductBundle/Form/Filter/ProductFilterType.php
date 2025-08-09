<?php

namespace App\ProductBundle\Form\Filter;

use App\BaseBundle\SystemConfiguration;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Collection;
use App\ProductBundle\Entity\Occasion;
use App\ProductBundle\Form\Transformer\CategoryTransformer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFilterType extends AbstractType
{

    private CategoryTransformer $categoryTransformer;

    public function __construct(CategoryTransformer $categoryTransformer)
    {
        $this->categoryTransformer = $categoryTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->setMethod("get")
            ->add('str', TextType::class, [
                "required" => false,
                "label" => "Search",
                "attr" => [
                    "placeholder" => "Title, SKU",
                    "autocomplete" => "off",
                ],
            ])
            ->add('newArrival', ChoiceType::class, [
                "required" => false,
                "label" => "New Arrival",
                "placeholder" => "All",
                "choices" => [
                    "Yes" => true,
                    "No" => false,
                ],
                "attr" => [
                    "class" => "select-search",
                ],
            ])
            ->add('publish', ChoiceType::class, [
                "required" => false,
                "label" => "Published",
                "placeholder" => "All",
                "choices" => [
                    "Yes" => true,
                    "No" => false,
                ],
                "attr" => [
                    "class" => "select-search",
                ],
            ])
            ->add('featured', ChoiceType::class, [
                "required" => false,
                "placeholder" => "All",
                "choices" => [
                    "Yes" => true,
                    "No" => false,
                ],
                "attr" => [
                    "class" => "select-search",
                ],
            ]);
        $this->addCategoryField($builder);
        $this->addCollectionField($builder);
        $this->addOccasionField($builder);
    }

    private function addCategoryField(FormBuilderInterface $builder): void
    {
        if (SystemConfiguration::CATEGORY_MAXIMAL_DEPTH_LEVEL <= 2) {
            $categoryOptions = [
                "required" => false,
                "multiple" => true,
                'placeholder' => 'Choose an option',
                'class' => Category::class,
                "attr" => [
                    "class" => "select-search",
                    'data-placeholder' => 'Choose One or More',
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.deleted IS NULL')
                        ->orderBy('c.id', 'DESC');
                },
            ];
            if (SystemConfiguration::CATEGORY_MAXIMAL_DEPTH_LEVEL == 2) {
                $categoryOptions['group_by'] = function ($choice, $key, $value) {
                    if ($choice->getParent()) {
                        return $choice->getParent()->getTitle();
                    }

                    return 'Other';
                };
            }

            $builder->add('category', EntityType::class, $categoryOptions);
        } else {
            $builder
                ->add('category', HiddenType::class)
                ->get('category')->addModelTransformer($this->categoryTransformer);
        }
    }

    private function addCollectionField(FormBuilderInterface $builder): void
    {
        if (!SystemConfiguration::ENABLE_COLLECTION) {
            return;
        }
        $builder->add('collection', EntityType::class, [
            "required" => false,
            'placeholder' => 'All',
            'class' => Collection::class,
            "attr" => [
                "class" => "select-search",
            ],
            'choice_label' => function ($collection) {
                $label = $collection->getTitle();
                if (!$collection->isPublish()) {
                    $label .= " (Unpublished)";
                }

                return $label;
            },
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->where('t.deleted IS NULL')
                    ->orderBy('t.id', 'DESC');
            },
        ]);
    }

    private function addOccasionField(FormBuilderInterface $builder): void
    {
        if (!SystemConfiguration::ENABLE_OCCASION) {
            return;
        }
        $builder->add('occasion', EntityType::class, [
            "required" => false,
            'placeholder' => 'All',
            'class' => Occasion::class,
            "attr" => [
                "class" => "select-search",
            ],
            'choice_label' => function ($occasion) {
                $label = $occasion->getTitle();
                if (!$occasion->isActive()) {
                    $label .= " (Not Active)";
                }

                return $label;
            },
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->where('t.deleted IS NULL')
                    ->orderBy('t.id', 'DESC');
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
