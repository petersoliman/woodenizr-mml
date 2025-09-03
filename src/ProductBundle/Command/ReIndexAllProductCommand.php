<?php

namespace App\ProductBundle\Command;

use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductSearchService;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly ProductSearchService $productSearchService,
        private readonly EntityManagerInterface $entityManager
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
        
        // Recalculate category counts to reflect actual searchable products
        $this->recalculateCategoryCounts($io);

        return Command::SUCCESS;
    }
    
    private function recalculateCategoryCounts(SymfonyStyle $io): void
    {
        $io->section('Recalculating category counts...');
        
        $categoryRepository = $this->entityManager->getRepository(\App\ProductBundle\Entity\Category::class);
        $categories = $categoryRepository->findAll();
        
        $progressBar = $io->createProgressBar(count($categories));
        $progressBar->start();
        
        foreach ($categories as $category) {
            // Count products that meet search criteria (have prices, are published, etc.)
            $qb = $this->productRepository->createQueryBuilder('p');
            $qb->select('COUNT(DISTINCT p.id)')
               ->leftJoin('p.prices', 'pp')
               ->where('p.deleted IS NULL')
               ->andWhere('p.category = :category')
               ->andWhere('pp.id IS NOT NULL') // Must have at least one price
               ->setParameter('category', $category->getId());
            
            $totalCount = (int) $qb->getQuery()->getSingleScalarResult();
            
            // Count published products that meet search criteria
            $qb = $this->productRepository->createQueryBuilder('p');
            $qb->select('COUNT(DISTINCT p.id)')
               ->leftJoin('p.prices', 'pp')
               ->where('p.deleted IS NULL')
               ->andWhere('p.publish = :publish')
               ->andWhere('p.category = :category')
               ->andWhere('pp.id IS NOT NULL') // Must have at least one price
               ->setParameter('publish', true)
               ->setParameter('category', $category->getId());
            
            $publishedCount = (int) $qb->getQuery()->getSingleScalarResult();
            
            // Update category counts
            $category->setNoOfProducts($totalCount);
            $category->setNoOfPublishProducts($publishedCount);
            $this->entityManager->persist($category);
            
            $progressBar->advance();
        }
        
        $this->entityManager->flush();
        $progressBar->finish();
        
        $io->success('Category counts recalculated successfully.');
    }
}
