<?php

namespace App\ProductBundle\Command;

use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductSearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:re-index-all-product',
    description: 'Add a short description for your command',
)]
class ReIndexAllProductCommand extends Command
{

    public function __construct(
        private readonly ProductRepository    $productRepository,
        private readonly ProductSearchService $productSearchService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Re-index all products');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $products = $this->productRepository->findBy(['deleted' => null]);

        $progressBar = $io->createProgressBar(count($products));
        $progressBar->start();

        foreach ($products as $product) {
            $this->productSearchService->insertOrDeleteProductInSearch($product);
            $progressBar->advance();

        }
        $progressBar->finish();

        $io->success('All products re-indexed successfully.');

        return Command::SUCCESS;
    }
}
