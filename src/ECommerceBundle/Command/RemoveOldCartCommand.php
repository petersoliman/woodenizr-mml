<?php

namespace App\ECommerceBundle\Command;

use App\ECommerceBundle\Repository\CartRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveOldCartCommand extends Command
{

    protected static $defaultName = 'app:remove-old-cart';
    private CartRepository $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        parent::__construct();
        $this->cartRepository = $cartRepository;
    }

    protected function configure(): void
    {
        $this->setDescription('Remove Old cookie carts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cartRepository->removeAllCookieCartByDays();
        $this->cartRepository->removeAllUserCartByDays();

        return 0;
    }

}
