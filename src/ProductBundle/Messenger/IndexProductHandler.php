<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Entity\Product;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductSearchService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class IndexProductHandler implements MessageHandlerInterface
{
    private ProductRepository $productRepository;
    private ProductSearchService $productSearchService;

    public function __construct(ProductRepository $productRepository, ProductSearchService $productSearchService)
    {
        $this->productRepository = $productRepository;
        $this->productSearchService = $productSearchService;
    }

    public function __invoke(IndexProduct $indexProduct): void
    {
        $product = $this->productRepository->find($indexProduct->getProductId());
        if ($product instanceof Product) {
            $this->productSearchService->insertOrDeleteProductInSearch($product);
        }
    }
}