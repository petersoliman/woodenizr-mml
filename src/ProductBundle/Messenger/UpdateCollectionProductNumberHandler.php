<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Entity\Collection;
use App\ProductBundle\Repository\CollectionRepository;
use App\ProductBundle\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

#[AsMessageHandler]
class UpdateCollectionProductNumberHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private ProductRepository $productRepository;
    private CollectionRepository $collectionRepository;

    public function __construct(
        EntityManagerInterface $em,
        ProductRepository      $productRepository,
        CollectionRepository   $collectionRepository
    )
    {
        $this->em = $em;
        $this->productRepository = $productRepository;
        $this->collectionRepository = $collectionRepository;
    }

    public function __invoke(UpdateCollectionProductNumber $collectionProductNumber): void
    {
        $collection = $this->collectionRepository->find($collectionProductNumber->getCollectionId());

        $noOfAllProducts = $this->updateNoOfAllProducts($collection);
        $noOfPublishedProducts = $this->updateNoOfPublishedProducts($collection);
        $this->collectionRepository->updateNumberOfProducts($collection, $noOfAllProducts, $noOfPublishedProducts);

    }

    private function updateNoOfAllProducts(Collection $collection): int
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->collection = $collection->getId();

        return $this->productRepository->filter($search, true);
    }

    private function updateNoOfPublishedProducts(Collection $collection): int
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->publish = true;
        $search->collection = $collection->getId();

        return $this->productRepository->filter($search, true);
    }
}