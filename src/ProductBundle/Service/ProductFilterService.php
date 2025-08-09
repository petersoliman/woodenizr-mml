<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Repository\ProductSearchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductFilterService
{

    const FRONT_END_FILTER_SHOW_NUMBER_OF_PRODUCTS_IN_EACH_LIST = true;

    private TranslatorInterface $translator;
    private ProductSearchRepository $productSearchRepository;


    public function __construct(
        TranslatorInterface     $translator,
        ProductSearchRepository $productSearchRepository
    )
    {
        $this->translator = $translator;
        $this->productSearchRepository = $productSearchRepository;
    }

    public function frontEndConvertFilterToArray(
        Request   $request,
        int       $numberOfProducts = 0,
        \stdClass $search = null,
        array     $specs = [],
        array     $categories = [],
        array     $brands = [],
    ): array
    {
        $routeName = $request->attributes->get('_route');

        $filtersIntoArray = [];

        // Search
        $filtersIntoArray[] = [
            "type" => "search",
            "inputName" => "str",
            "title" => $this->translator->trans("search_in_product_count_txt",
                ["%numberOfProducts%" => number_format($numberOfProducts)]),
            "value" => $search->string,
        ];


        if ($routeName != "fe_product_filter_sale") {
            // On Sale
            $filtersIntoArray[] = [
                "type" => "checkbox",
                "title" => $this->translator->trans("on_sale_txt"),
                "inputName" => "offer",
                "value" => 1,
                "checked" => $search->offer,
            ];
        }
        // In Stock
        $filtersIntoArray[] = [
            "type" => "checkbox",
            "title" => $this->translator->trans("in_stock_txt"),
            "inputName" => "inStock",
            "value" => 1,
            "checked" => $search->hasStock,
        ];
        $this->frontEndConvertCategoriesFilterToArray($filtersIntoArray, $search, $categories);
        $this->frontEndConvertBrandsFilterToArray($filtersIntoArray, $search, $brands);

        $this->frontEndConvertSpecsFilterToArray($filtersIntoArray, $search, $specs);
        $this->frontEndConvertPriceRangeFilterToArray($filtersIntoArray, $search, $numberOfProducts);

        return $filtersIntoArray;
    }

    private function frontEndConvertCategoriesFilterToArray(
        &$filtersIntoArray = [],
        \stdClass $search = null,
        array $categories = []
    ): void
    {
        if (count($categories) < 1) {
            return;
        }
        $categoriesArray = [
            "type" => "checkbox-list",
            "title" => $this->translator->trans("categories_txt"),
            "inputName" => "categories[]",
            "options" => [],
        ];
        foreach ($categories as $category) {
            $title = $category->getTitle();
            if (self::FRONT_END_FILTER_SHOW_NUMBER_OF_PRODUCTS_IN_EACH_LIST) {
                $title .= " (" . number_format($category->noOfProductss) . ")";
            }

            $categoriesArray["options"][] = [
                "value" => $category->getId(),
                "name" => $title,
                "checked" => (is_array($search->categories) and in_array($category->getId(), $search->categories)),
            ];
        }
        $filtersIntoArray[] = $categoriesArray;
    }

    private function frontEndConvertBrandsFilterToArray(
        &$filtersIntoArray = [],
        \stdClass $search = null,
        array $brands = []
    ): void
    {
        if (count($brands) < 1) {
            return;
        }
        $brandsArray = [
            "type" => "checkbox-list",
            "title" => $this->translator->trans("brands_txt"),
            "inputName" => "brands[]",
            "options" => [],
        ];
        foreach ($brands as $brand) {
            $title = $brand->getTitle();
            if (self::FRONT_END_FILTER_SHOW_NUMBER_OF_PRODUCTS_IN_EACH_LIST) {
                $title .= " (" . number_format($brand->noOfProductss) . ")";
            }

            $brandsArray["options"][] = [
                "value" => $brand->getId(),
                "name" => $title,
                "checked" => (is_array($search->brands) and in_array($brand->getId(), $search->brands)),
            ];
        }
        $filtersIntoArray[] = $brandsArray;
    }

    private function frontEndConvertSpecsFilterToArray(
        &$filtersIntoArray = [],
        \stdClass $search = null,
        array $specs = []
    ): void
    {
        if (count($specs) < 1) {
            return;
        }
        foreach ($specs as $spec) {
            if (!isset($spec->subSpecs)) {
                continue;
            }
            $specArray = [
                "type" => "checkbox-list",
                "title" => $spec->getTitle(),
                "inputName" => "specs[{$spec->getId()}][]",
                "options" => [],
            ];
            foreach ($spec->subSpecs as $subSpec) {
                $title = $subSpec->getTitle();
                if (self::FRONT_END_FILTER_SHOW_NUMBER_OF_PRODUCTS_IN_EACH_LIST) {
                    $title .= " (" . number_format($subSpec->noOfProducts) . ")";
                }
                $isChecked = false;
                if (
                    is_array($search->specs)
                    and array_key_exists($spec->getId(), $search->specs)
                    and in_array($subSpec->getId(), $search->specs[$spec->getId()])
                ) {
                    $isChecked = true;

                }

                $specArray["options"][] = [
                    "value" => $subSpec->getId(),
                    "name" => $title,
                    "checked" => $isChecked,
                ];
            }

            $filtersIntoArray[] = $specArray;
        }
    }

    private function frontEndConvertPriceRangeFilterToArray(
        &$filtersIntoArray = [],
        \stdClass $search = null,
        int $numberOfProducts = 0
    ): void
    {
        if ($numberOfProducts < 1) {
            return;
        }
        $filterMaxPrice = $this->productSearchRepository->maxPriceForFilter($search);
        if ($filterMaxPrice < 1) {
            return;
        }
        $filtersIntoArray[] = [
            "type" => "price-range",
            "filterMaxPrice" => $filterMaxPrice,
            "title" => $this->translator->trans("price_range_txt"),
            "minimum" => [
                "inputName" => "priceFrom",
                "value" => (int)$search->priceFrom > 0 ? $search->priceFrom : 1,
            ],
            "maximum" => [
                "inputName" => "priceTo",
                "value" => (int)$search->priceTo > 0 ? $search->priceTo : $filterMaxPrice,
            ],
        ];
    }
}
