<?php

namespace App\ProductBundle\Form;

use App\BaseBundle\ProductPriceTypeEnum;
use App\BaseBundle\SystemConfiguration;
use App\CurrencyBundle\Entity\Currency;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Collection;
use App\ProductBundle\Entity\Occasion;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductHasCollection;
use App\ProductBundle\Entity\ProductHasOccasion;
use App\ProductBundle\Form\Transformer\CategoryTransformer;
use App\ProductBundle\Form\Translation\ProductTranslationType;
use App\ProductBundle\Repository\BrandRepository;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Repository\CollectionRepository;
use App\ProductBundle\Repository\OccasionRepository;
use App\VendorBundle\Entity\StoreAddress;
use App\VendorBundle\Entity\Vendor;
use App\VendorBundle\Repository\VendorRepository;
use Doctrine\ORM\EntityRepository;
use App\ProductBundle\Entity\Brand;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\MediaBundle\Form\SingleImageType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductType extends AbstractType
{

    private SessionInterface $session;
    private ?Product $product;
    private array $selectedCollections = [];
    private array $selectedOccasions = [];

    public function __construct(
        RequestStack $requestStack,
        public CategoryTransformer $categoryTransformer,
        public CategoryRepository $categoryRepository,
        public CollectionRepository $collectionRepository,
        public OccasionRepository $occasionRepository,
        public BrandRepository $brandRepository,
        public VendorRepository $vendorRepository
    ) {
        $this->session = $requestStack->getSession();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->product = $builder->getData();
        $postTypeModel = new PostTypeModel();
        $postTypeModel->add("description", "Description");
        $builder
            ->add('title')
            ->add('sku', TextType::class, [
                "label" => "SKU",
                "required" => false,
            ])
            ->add('searchTerms', TextType::class, [
                "label" => "Search Terms",
                "required" => false,
            ])
            ->add('image', SingleImageType::class, ["mapped" => false])
            ->add('publish', CheckboxType::class, [
                "required" => false,
                "label" => "Published",
            ])
            ->add('featured')
            ->add('newArrival', CheckboxType::class, [
                "required" => false,
                "label" => "New Arrival",
            ])
            ->add('seo', SeoType::class)
            ->add('post', PostType::class, ["attributes" => $postTypeModel])
            ->add('details', ProductDetailsType::class)
            ->add('translations', TranslationsType::class, [
                'entry_type' => ProductTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);

        if (SystemConfiguration::PRODUCT_PRICE_TYPE == ProductPriceTypeEnum::VARIANTS) {
            $builder->add('enableVariants', CheckboxType::class, [
                "required" => false,
                "label" => "Enable Variants",
            ]);
        }
        $this->addProductPriceField($builder);
        $this->addCurrencyField($builder);
        $this->addCategoryField($builder);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $entity = $event->getData();

        $this->getSubAttributesField($form, $entity, $entity->getCategory());
        $this->getBrandsField($form, $entity->getBrand());

        if (SystemConfiguration::ENABLE_COLLECTION) {
            $collections = [];
            foreach ($entity->getProductHasCollections() as $productHasCollection) {
                $collections[] = $productHasCollection->getCollection();
            }
            $this->addCollectionField($form, $collections);
        }

        if (SystemConfiguration::ENABLE_OCCASION) {
            $occasions = [];
            foreach ($entity->getProductHasOccasions() as $productHasOccasion) {
                $occasions[] = $productHasOccasion->getOccasion();
            }
            $this->addOccasionField($form, $occasions);
        }

        $vendor = $entity->getVendor() ? $entity->getVendor() : null;
        $this->addStoresElements($form, $vendor);
        $this->addVendorElement($form, $vendor);

    }


    public function onPreSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $entity = $event->getData();

        $category = $this->categoryRepository->find($entity["category"]);
        $this->getSubAttributesField($form, $this->product, $category);

        $brand = null;
        if ($entity['brand'] != "") {
            $brand = $this->brandRepository->find($entity['brand']);
        }
        $this->getBrandsField($form, $brand);

        if (SystemConfiguration::ENABLE_COLLECTION) {
            if (array_key_exists("collections", $entity) and count($entity['collections']) > 0) {
                $this->selectedCollections = $this->collectionRepository->findBy(["id" => $entity['collections']]);
            }
        }

        if (SystemConfiguration::ENABLE_OCCASION) {
            if (array_key_exists("occasions", $entity) and count($entity['occasions']) > 0) {
                $this->selectedOccasions = $this->occasionRepository->findBy(["id" => $entity['occasions']]);
            }
        }

        $vendor = null;
        if ($entity['vendor'] != "") {
            $vendor = $this->vendorRepository->find($entity['vendor']);
        }
        $this->addStoresElements($form, $vendor);
        $this->addVendorElement($form, $vendor);
    }

    public function onSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $entity = $event->getData();


        if ($entity->getCategory() === null) {
            $this->session->getFlashBag()->add('error', "You must select the category");
        }
        if ($entity->getPrices()->count() < 1 and !$entity->isEnableVariants()) {
            $this->session->getFlashBag()->add('error', "You must enter at least one (1) price");
        }

        if (SystemConfiguration::ENABLE_COLLECTION) {
            $collectionAlreadyExist = [];
            foreach ($entity->getProductHasCollections() as $productHasCollection) {
                $collection = $productHasCollection->getCollection();
                if (!in_array($collection, $this->selectedCollections)) {
                    $entity->removeProductHasCollection($productHasCollection);
                } else {
                    $collectionAlreadyExist[] = $collection;
                }
            }
            if (count($this->selectedCollections) > 0) {
                foreach ($this->selectedCollections as $collection) {
                    if (in_array($collection, $collectionAlreadyExist)) {
                        continue;
                    }
                    $productHasCollection = new ProductHasCollection();
                    $productHasCollection->setCollection($collection);
                    $productHasCollection->setProduct($entity);
                    $entity->addProductHasCollection($productHasCollection);
                }
            }
        }
        if (SystemConfiguration::ENABLE_OCCASION) {
            $occasionAlreadyExist = [];
            foreach ($entity->getProductHasOccasions() as $productHasOccasion) {
                $occasion = $productHasOccasion->getOccasion();
                if (!in_array($occasion, $this->selectedOccasions)) {
                    $entity->removeProductHasOccasion($productHasOccasion);
                } else {
                    $occasionAlreadyExist[] = $occasion;
                }
            }

            if (count($this->selectedOccasions) > 0) {
                foreach ($this->selectedOccasions as $occasion) {
                    if (in_array($occasion, $occasionAlreadyExist)) {
                        continue;
                    }
                    $productHasOccasion = new ProductHasOccasion();
                    $productHasOccasion->setOccasion($occasion);
                    $productHasOccasion->setProduct($entity);
                    $entity->addProductHasOccasion($productHasOccasion);
                }
            }
        }

    }

    private function addCategoryField(FormBuilderInterface $builder)
    {
        if (SystemConfiguration::CATEGORY_MAXIMAL_DEPTH_LEVEL <= 2) {
            $categoryOptions = [
                'required' => true,
                'placeholder' => 'Choose an option',
                "attr" => ["class" => "select-search"],
                'class' => Category::class,
                "constraints" => [new NotBlank()],
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


    private function getSubAttributesField(FormInterface $form, ?Product $product, ?Category $category): void
    {
        $form->add('subAttributes', ProductHasAttributeType::class, [
            "mapped" => false,
            "label" => false,
            'category' => $category,
            'product' => $product,
        ]);
    }


    private function addProductPriceField(FormBuilderInterface $builder): void
    {
        $constraints = [];

        switch (SystemConfiguration::PRODUCT_PRICE_TYPE) {
            case ProductPriceTypeEnum::SINGLE_PRICE:
                $constraints[] = new Count([
                    "min" => 1,
                    "max" => 1,
                    'minMessage' => 'Must have at least one price',
                    'exactMessage' => 'You must have only one price',
                ]);
                break;
            case ProductPriceTypeEnum::MULTI_PRICES:

                $constraints[] = new Count([
                    'min' => 1,
                    'minMessage' => 'You must enter at least one (1) product price',
                ]);
                break;
            case ProductPriceTypeEnum::VARIANTS:
                break;
        }

        $builder->add("prices", CollectionType::class, [
            "required" => true,
            "entry_type" => ProductPriceType::class,
            "allow_add" => true,
            "allow_delete" => SystemConfiguration::PRODUCT_PRICE_TYPE->allowDelete(),
            "prototype" => true,
            "label" => false,
            "by_reference" => false,
            "error_bubbling" => false,
            "constraints" => $constraints,
        ]);
    }

    private function addCollectionField(FormInterface $form, array $selectedCollections = []): void
    {
        if (!SystemConfiguration::ENABLE_COLLECTION) {
            return;
        }
        $form->add('collections', EntityType::class, [
            "required" => false,
            "mapped" => false,
            "multiple" => true,
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
            "data" => $selectedCollections,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->where('t.deleted IS NULL')
                    ->orderBy('t.id', 'DESC');
            },
        ]);
    }

    private function addOccasionField(FormInterface $form, array $selectedOccasions = []): void
    {
        if (!SystemConfiguration::ENABLE_OCCASION) {
            return;
        }
        $form->add('occasions', EntityType::class, [
            "required" => false,
            "mapped" => false,
            "multiple" => true,
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
            "data" => $selectedOccasions,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->where('t.deleted IS NULL')
                    ->orderBy('t.id', 'DESC');
            },
        ]);
    }

    private function getBrandsField(FormInterface $form, ?Brand $brand = null)
    {
        $choices = [];
        if ($brand) {
            $choices[] = $brand;
        }

        $form->add('brand', EntityType::class, [
            "label" => "Brand",
            "placeholder" => "Choose an option",
            "choice_translation_domain" => false,
            'required' => false,
            'class' => Brand::class,
//            "attr" => ["class" => "select-search"],
            "choices" => $choices,
        ]);
    }

    private function addVendorElement(FormInterface $form, Vendor $vendor = null): void
    {
        $vendors = [];
        if ($vendor) {
            $vendors[] = $vendor;
        }
        $form->add('vendor', EntityType::class, [
            'required' => true,
            'multiple' => false,
            'placeholder' => 'Choose an option',
            'class' => Vendor::class,
            'constraints' => [new NotBlank()],
            'data' => $vendor,
            'choices' => $vendors,

        ]);
    }
    private function addStoresElements(FormInterface $form, Vendor $vendor = null): void
    {
        $vendorStores = [];
        if ($vendor != null) {
            $vendorStores = $vendor->getStoreAddresses()->toArray();
        }

        $form->add('storeAddress', EntityType::class, [
            'required' => true,
            'multiple' => false,
            'placeholder' => 'Choose an option',
            'class' => StoreAddress::class,
            'constraints' => [new NotBlank()],
            'choices' => $vendorStores,
            "attr" => ["class" => "select-search"],
        ]);
    }

    private function addCurrencyField(FormBuilderInterface $builder): void
    {
        if (!SystemConfiguration::ENABLE_MULTI_CURRENCIES) {
            return;
        }
        $builder->add('currency', EntityType::class, [
            'required' => true,
            "attr" => ["class" => "select-search"],
            'placeholder' => 'Choose an option',
            'class' => Currency::class,
            "constraints" => [new NotBlank()],
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->where('c.deleted IS NULL')
                    ->orderBy('c.id', 'DESC');
            },
            'choice_attr' => function ($choice, $key, $value) {
                return [
                    'data-symbol' => $choice->getSymbol(),
                ];
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
