<?php

namespace App\ProductBundle\Form\Transformer;

use App\ProductBundle\Entity\Category;
use App\ProductBundle\Repository\CategoryRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CategoryTransformer implements DataTransformerInterface
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Transforms an object (course) to a string (id).
     */
    public function transform($category): mixed
    {
        if (null === $category) {
            return '';
        }

        return $category->getId();
    }

    /**
     * Transforms a string (id) to an object (category).
     */
    public function reverseTransform($categoryId): ?Category
    {
        // no course id? It's optional, so that's ok
        if (!$categoryId) {
            return null;
        }

        $category = $this->categoryRepository->find($categoryId);
        if (null === $category) {
            // causes a validation error
            // this message is not shown to the user
            throw new TransformationFailedException(sprintf('An course with id "%s" does not exist!', $categoryId));
        }

        return $category;
    }
}