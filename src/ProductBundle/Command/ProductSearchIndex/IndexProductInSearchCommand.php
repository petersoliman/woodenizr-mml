<?php

namespace App\ProductBundle\Command\ProductSearchIndex;

use App\ProductBundle\Messenger\IndexProduct;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Repository\ProductSearchRepository;
use App\ProductBundle\Service\ProductSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class IndexProductInSearchCommand extends Command
{
    protected static $defaultName = 'app:index-product-search';
    private MessageBusInterface $bus;
    private ProductSearchRepository $productSearchRepository;

    public function __construct(
        MessageBusInterface $bus,
        ProductSearchRepository $productSearchRepository
    ) {
        parent::__construct();
        $this->productSearchRepository = $productSearchRepository;
        $this->bus = $bus;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Update index product in search and remove unused products')
            ->setHelp('Run every day');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->removeUnusedProducts();
        $this->updateProductHasOfferAndExpired();

        return 0;
    }

    private function removeUnusedProducts(): void
    {
        $this->productSearchRepository->deleteUnUsedProducts();
    }

    private function updateProductHasOfferAndExpired(): void
    {
        $expiredProductSearchOffers = $this->productSearchRepository->getExpiredOffers();

        foreach ($expiredProductSearchOffers as $expiredProductSearchOffer) {
            $product = $expiredProductSearchOffer->getProduct();
            $this->bus->dispatch(new IndexProduct($product));
        }

    }
}
