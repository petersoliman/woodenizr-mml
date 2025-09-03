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
 * Date: 2025-09-01 00:10
 * Reason: Console command to run GCData updates for all products with gcd_status = Ready
 */
#[AsCommand(name: 'app:gcdata:update-all', description: 'Run GCData updates for products with gcd_status = Ready')]
class GcdataUpdateAllCommand extends Command
{
    public function __construct(private readonly ProductGCDBatchService $batchService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('jobId', InputArgument::REQUIRED, 'Job ID for tracking progress');
        $this->addArgument('limit', InputArgument::OPTIONAL, 'Optional limit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = (string) $input->getArgument('jobId');
        $limit = $input->getArgument('limit');
        $limit = $limit !== null ? (int) $limit : null;

        $this->batchService->runAllReady($jobId, $limit);
        $output->writeln('<info>GCData batch completed.</info>');
        return Command::SUCCESS;
    }
}


