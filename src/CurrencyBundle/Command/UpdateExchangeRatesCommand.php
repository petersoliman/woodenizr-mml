<?php

namespace App\CurrencyBundle\Command;

use App\BaseBundle\SystemConfiguration;
use App\CurrencyBundle\Service\UpdateExchangeRateUsingAPIService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateExchangeRatesCommand extends Command
{

    protected static $defaultName = 'app:update-exchange-rates';
    private UpdateExchangeRateUsingAPIService $updateExchangeRateUsingAPIService;

    public function __construct(
        UpdateExchangeRateUsingAPIService $updateExchangeRateUsingAPIService,
    ) {
        parent::__construct();
        $this->updateExchangeRateUsingAPIService = $updateExchangeRateUsingAPIService;
    }

    protected function configure(): void
    {
        $this->setDescription('Update Exchange rates from API');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!SystemConfiguration::ENABLE_MULTI_CURRENCIES) {
            return 0;
        }
        $this->updateExchangeRateUsingAPIService->update();

        return 0;
    }

}
