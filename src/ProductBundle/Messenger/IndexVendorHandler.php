<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Repository\ProductRepository;
use App\VendorBundle\Entity\Vendor;
use App\VendorBundle\Repository\VendorRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class IndexVendorHandler implements MessageHandlerInterface
{
    private MessageBusInterface $bus;
    private VendorRepository $vendorRepository;
    private ProductRepository $productRepository;

    public function __construct(
        MessageBusInterface $bus,
        VendorRepository    $vendorRepository,
        ProductRepository   $productRepository
    )
    {
        $this->bus = $bus;
        $this->vendorRepository = $vendorRepository;
        $this->productRepository = $productRepository;
    }

    public function __invoke(IndexVendor $indexVendor): void
    {
        $vendor = $this->vendorRepository->find($indexVendor->getVendorId());

        $products = $this->getProductsByVendor($vendor);

        foreach ($products as $product) {
            $this->bus->dispatch(new IndexProduct($product));
        }
    }

    private function getProductsByVendor(Vendor $vendor): array
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->publish = true;
        $search->vendor = $vendor->getId();

        return $this->productRepository->filter($search);
    }
}