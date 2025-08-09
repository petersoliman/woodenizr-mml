<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Entity\Category;

class IndexCategory
{
    private int $categoryId;

    public function __construct(Category $category)
    {
        $this->categoryId = $category->getId();
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }
}