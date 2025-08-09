<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Form\ProductHasAttributeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class AttributeService
{

    private EntityManagerInterface $em;
    private FormFactoryInterface $formFactory;

    public function __construct(EntityManagerInterface $em, FormFactoryInterface $formFactory)
    {
        $this->em = $em;
        $this->formFactory = $formFactory;
    }

    public function getSpecsForm(?Category $category, ?Product $product): ?FormInterface
    {
        if ($category == null) {
            return null;
        }

        return $this->createFormBuilderNamed("product", $product)
            ->add('subAttributes', ProductHasAttributeType::class, [
                "mapped" => false,
                "label" => false,
                'category' => $category,
                'product' => $product,
            ]);

    }

    private function createFormBuilderNamed($name, $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->createNamed($name, FormType::class, $data, $options);
    }

}
