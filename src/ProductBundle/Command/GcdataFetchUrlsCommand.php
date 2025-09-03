<?php

namespace App\ProductBundle\Command;

use App\ProductBundle\Service\ProductGCDBatchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Created by: cursor
 * Date: 2025-09-01 16:10
 * Reason: Console command to fetch manufacturer URLs and save into ProductCGD.url
 */
#[AsCommand(name: 'app:gcdata:fetch-urls', description: 'Fetch manufacturer URLs for products and save them into ProductCGD')]
class GcdataFetchUrlsCommand extends Command
{
    public function __construct(private readonly ProductGCDBatchService $batchService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('limit', InputArgument::OPTIONAL, 'Max number of products to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getArgument('limit');
        $limit = $limit !== null ? (int) $limit : null;

        $result = $this->batchService->fetchAndSaveManufacturerUrls($limit);
        $output->writeln(sprintf(
            '<info>Done</info> processed=%d, updated=%d, skipped=%d, errors=%d',
            $result['processed'], $result['updated'], $result['skipped'], $result['errors']
        ));
        return Command::SUCCESS;
    }
}


