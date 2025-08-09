<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Entity\Category;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductSearchService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class IndexCategoryHandler implements MessageHandlerInterface
{
    private MessageBusInterface $bus;
    private CategoryRepository $categoryRepository;
    private ProductRepository $productRepository;

    public function __construct(
        MessageBusInterface $bus,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository
    ) {
        $this->bus = $bus;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    public function __invoke(IndexCategory $indexCategory): void
    {
        $category = $this->categoryRepository->find($indexCategory->getCategoryId());

        $products = $this->getProductsByCategory($category);

        foreach ($products as $product) {
            $this->bus->dispatch(new IndexProduct($product));
        }
    }

    private function getProductsByCategory(Category $category): array
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->publish = true;
        $search->categories = explode(",", $category->getConcatIds());

        return $this->productRepository->filter($search);
    }
}