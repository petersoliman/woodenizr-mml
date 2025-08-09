<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Entity\ProductPriceHasVariantOption;
use App\ProductBundle\Entity\ProductVariant;
use App\ProductBundle\Entity\ProductVariantOption;
use App\ProductBundle\Repository\ProductPriceHasVariantOptionRepository;
use App\ProductBundle\Repository\ProductPriceRepository;
use App\ProductBundle\Repository\ProductVariantOptionRepository;
use App\ProductBundle\Repository\ProductVariantRepository;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UserService;

class ProductVariantOptionService
{

    private EntityManagerInterface $em;
    private UserService $userService;
    private ProductPriceRepository $productPriceRepository;
    private ProductVariantRepository $productVariantRepository;
    private ProductPriceHasVariantOptionRepository $productPriceHasVariantOptionRepository;

    public function __construct(
        EntityManagerInterface $em,
        UserService $userService,
        ProductPriceRepository $productPriceRepository,
        ProductVariantRepository $productVariantRepository,
        ProductPriceHasVariantOptionRepository $productPriceHasVariantOptionRepository
    ) {
        $this->em = $em;
        $this->userService = $userService;
        $this->productPriceRepository = $productPriceRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->productPriceHasVariantOptionRepository = $productPriceHasVariantOptionRepository;
    }

    public function addNewVariantOptionCombos(ProductVariantOption $productVariantOption): void
    {
        $product = $productVariantOption->getVariant()->getProduct();
        $variants = $this->getProductVariants($product, $productVariantOption->getVariant());

        $data = [];

        foreach ($variants as $variant) {
            $node = [];
            foreach ($variant->getOptions() as $option) {
                $node[] = $option;
            }
            $data[] = $node;
        }
        $data[] = [$productVariantOption];

        $combos = $this->possibleCombos($data);

        foreach ($combos as $group) {
            $variantOptionIds = $this->getGroupIds($group);
            $addNewVariantOption = false;
            $productPrice = $this->getProductPriceByVariantOptionIdsWithoutCurrentOptionId($product,
                $productVariantOption, $variantOptionIds);

            if (!$productPrice instanceof ProductPrice) {
                $addNewVariantOption = true;
                $productPrice = new ProductPrice();
                $productPrice->setProduct($product);
                $productPrice->setUnitPrice(0);
            }
            foreach ($group as $node) {
                $productPriceHasVariantOption = null;
                if (!$addNewVariantOption) {
                    $productPriceHasVariantOption = $this->productPriceHasVariantOptionRepository->findOneBy([
                        "productPrice" => $productPrice,
                        "variant" => $node->getVariant(),
                        "option" => $node,
                    ]);

                }
                if (!$productPriceHasVariantOption instanceof ProductPriceHasVariantOption) {
                    $productPriceHasVariantOption = new ProductPriceHasVariantOption();
                    $productPriceHasVariantOption->setProductPrice($productPrice);
                    $productPriceHasVariantOption->setVariant($node->getVariant());
                    $productPriceHasVariantOption->setOption($node);
                    $this->em->persist($productPriceHasVariantOption);
                }
            }

            $productPrice->setTitle($this->getGroupTitle($group));
            $productPrice->setVariantOptionIds(implode("-", $variantOptionIds));
            $this->em->persist($productPrice);
        }
        $this->em->flush();
    }

    public function updateProductPrice(ProductVariant $productVariant): void
    {
        $product = $productVariant->getProduct();
        $variants = $this->getProductVariants($product);
        $data = [];

        foreach ($variants as $variant) {
            $node = [];
            foreach ($variant->getOptions() as $option) {
                $node[] = $option;
            }
            $data[] = $node;
        }

        $combos = $this->possibleCombos($data);

        foreach ($combos as $group) {
            $variantOptionIds = $this->getGroupIds($group);

            $productPrice = $this->productPriceRepository->findOneBy([
                'product' => $product,
                "variantOptionIds" => implode("-", $variantOptionIds),
            ]);

            if ($productPrice instanceof ProductPrice) {
                $productPrice->setTitle($this->getGroupTitle($group));
                $this->em->persist($productPrice);
            }
        }
        $this->em->flush();
    }
    public function deleteVariantOption(ProductVariantOption $productVariantOption): void
    {
        $productPriceHasVariantOptions = $this->productPriceHasVariantOptionRepository->findBy([
            "option" => $productVariantOption,
        ]);
        foreach ($productPriceHasVariantOptions as $productPriceHasVariantOption){
            $productPriceHasVariantOption->getProductPrice()->setDeleted(new \DateTime());
            $this->em->persist($productPriceHasVariantOption);
        }
        $this->em->flush();
    }
    /**
     * @param Product $product
     * @param ProductVariant|null $notProductVariant
     * @return array<ProductVariant>
     */
    private function getProductVariants(Product $product, ?ProductVariant $notProductVariant = null): array
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->product = $product->getId();
        $search->ordr = ["column" => 1, "dir" => "DESC"];
        if ($notProductVariant instanceof ProductVariant) {
            $search->notId = $notProductVariant->getId();
        }

        return $this->productVariantRepository->filter($search);
    }

    /**
     * Create Combos of product variants groups
     * @param $groups
     * @param array $array
     * @return array
     */
    private function possibleCombos($groups, array $array = []): array
    {
        $result = [];
        $group = array_shift($groups);

        foreach ($group as $selected) {
            if ($groups) {
                $result = array_merge($result, $this->possibleCombos($groups, array_merge($array, [$selected])));
            } else {
                $result[] = array_merge($array, [$selected]);
            }
        }

        return $result;
    }

    private function getGroupTitle(array $group): string
    {
        $groupTitle = [];
        foreach ($group as $node) {
            $groupTitle[] = $node->getTitle();
        }

        return implode(" / ", $groupTitle);
    }

    private function getGroupIds(array $group): array
    {
        $groupIds = [];
        foreach ($group as $node) {
            $groupIds[] = $node->getId();
            sort($groupIds);
        }

        return $groupIds;
    }

    private function removeValueFromArray(array $array, string|int $value): array
    {
        if (($key = array_search($value, $array)) !== false) {
            unset($array[$key]);
        }

        return $array;
    }

    private function getProductPriceByVariantOptionIdsWithoutCurrentOptionId(
        Product $product,
        ProductVariantOption $currentProductVariantOption,
        array $allOptionIds
    ): ?ProductPrice {
        $variantOptionIds = $this->removeValueFromArray($allOptionIds, $currentProductVariantOption->getId());

        return $this->productPriceRepository->findOneBy([
            'product' => $product,
            "variantOptionIds" => implode("-", $variantOptionIds),
            "deleted" => null,
        ]);
    }
}
