<?php

namespace App\ECommerceBundle\Twig;

use App\CurrencyBundle\Service\GoogleAnalyticService;
use App\ECommerceBundle\Entity\Order;
use App\ProductBundle\Entity\ProductPrice;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsRuntime implements RuntimeExtensionInterface
{
    private GoogleAnalyticService $googleAnalyticService;

    public function __construct(GoogleAnalyticService $googleAnalyticService)
    {
        $this->googleAnalyticService = $googleAnalyticService;
    }
    public function gtmEnhancedEcommercePurchase(Order $order): array
    {
        return $this->googleAnalyticService->purchase($order);
    }

    public function gtmEnhancedEcommerceProductObject(ProductPrice $productPrice): array
    {
        return $this->googleAnalyticService->getProductObject($productPrice);
    }

}
