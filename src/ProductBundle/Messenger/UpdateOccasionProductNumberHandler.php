<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Entity\Occasion;
use App\ProductBundle\Repository\OccasionRepository;
use App\ProductBundle\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

#[AsMessageHandler]
class UpdateOccasionProductNumberHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private ProductRepository $productRepository;
    private OccasionRepository $occasionRepository;

    public function __construct(
        EntityManagerInterface $em,
        ProductRepository      $productRepository,
        OccasionRepository     $occasionRepository
    )
    {
        $this->em = $em;
        $this->productRepository = $productRepository;
        $this->occasionRepository = $occasionRepository;
    }

    public function __invoke(UpdateOccasionProductNumber $updateOccasionProductNumber): void
    {
        $occasion = $this->occasionRepository->find($updateOccasionProductNumber->getOccasionId());

        $noOfAllProducts = $this->updateNoOfAllProducts($occasion);
        $noOfPublishedProducts = $this->updateNoOfPublishedProducts($occasion);

        $this->occasionRepository->updateNumberOfProducts($occasion, $noOfAllProducts, $noOfPublishedProducts);
    }

    private function updateNoOfAllProducts(Occasion $occasion): int
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->occasion = $occasion->getId();

        return $this->productRepository->filter($search, true);
    }

    private function updateNoOfPublishedProducts(Occasion $occasion): int
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->publish = true;
        $search->occasion = $occasion->getId();

        return $this->productRepository->filter($search, true);
    }
}