<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Repository\CategoryRepository;

class CategoryService
{

    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function parentsByChild(Category $parent = null): array
    {
        $parents = [];
        if ($parent == null) {
            return $parents;
        }

        $lastParent = null;
        if ($parent->getParent() != null) {
            do {
                $parents[] = $parent;
                $lastParent = $parent->getParent();
                $parent = $lastParent;
            } while ($parent->getParent() != null);
            if ($lastParent != null) {
                $parents[] = $lastParent;
            }
        } else {
            $parents[] = $parent;
        }

        return array_reverse($parents);
    }

    public function getCategoryForFancyTree(Product $product = null)
    {
        $categoriesArr = [];
        $categories = $this->categoryRepository->findBy(['parent' => null, 'deleted' => null]);
        foreach ($categories as $category) {
            $array = $this->fancyTreeCategories($category, $categoriesArr, $product);
            array_push($categoriesArr, $array);
        }

        return $categoriesArr;
    }

    private function fancyTreeCategories(Category $category, array $array, Product $product = null)
    {
        $productCategoryId = null;
        if ($product != null and $product->getCategory() != null) {
            $productCategoryId = $product->getCategory()->getId();
        }
        if (count($category->getChildren()) > 0) {
            $childrenIds = explode(",", $category->getConcatIds());
            $expanded = false;
            if (in_array($productCategoryId, $childrenIds)) {
                $expanded = true;
            }
            $node = [
                "title" => $category->getTitle(),
                "checkbox" => false,
                "folder" => true,
                "expanded" => $expanded,
                "children" => [],
            ];
            foreach ($category->getChildren() as $category) {
                $children = $this->fancyTreeCategories($category, $array, $product);
                $node["children"][] = $children;

            }
        } else {
            $selected = false;

            if ($category->getId() == $productCategoryId) {
                $selected = true;
            }
            $node = [
                "id" => $category->getId(),
                "title" => $category->getTitle(),
                "selected" => $selected,
            ];
        }

        return $node;
    }
}
