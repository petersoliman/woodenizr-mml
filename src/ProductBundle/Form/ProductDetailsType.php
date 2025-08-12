<?php

namespace App\ProductBundle\Form;

use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductDetailsType extends AbstractType
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
        $builder
            ->add('augmentedRealityUrl', UrlType::class, ['required' => false]);
        /*$builder
            ->add('tearSheet', FileType::class, [
                "mapped" => false,
                "required" => false,
                "attr" => [
                    "class" => "form-control",
                    "accept" => ".doc,.docx,application/pdf, application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                ],
                'constraints' => [
                    new File([
                        "maxSize" => "3M",
                        "mimeTypes" => [
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/pdf',
                        ],
                    ]),
                ],
            ]);*/

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }


    public function onPreSetData(FormEvent $event): void
    {
        $entity = $event->getData();
        $form = $event->getForm();

        $relatedProduct = $entity->getRelatedProducts();
        $this->addRelatedProductElements($form, $relatedProduct);
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        $relatedProducts = [];
        if (is_array($data) and array_key_exists('relatedProducts', $data)) {
            foreach ($data['relatedProducts'] as $relatedProduct) {
                $relatedProducts[] = $this->em->getRepository(Product::class)->find($relatedProduct);
            }
        }
        $this->addRelatedProductElements($form, $relatedProducts);
    }

    private function addRelatedProductElements(FormInterface $form, $relatedProduct = []): void
    {
        $form->add('relatedProducts', EntityType::class, [
            'required' => false,
            'label' => "Related Products",
            'multiple' => true,
            'placeholder' => 'Choose an option',
            'class' => Product::class,
            'choices' => $relatedProduct,
            'choice_label' => function ($product) {
                $label = $product->getTitle();
                if (!$product->isPublish()) {
                    $label .= " (Unpublished)";
                }

                return $label;
            },
            "attr" => [
                "class" => "select-search",
            ],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductDetails::class,
            "error_bubbling" => true,
        ]);
    }
}
