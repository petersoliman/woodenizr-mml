<?php

namespace App\ECommerceBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('gtm_enhanced_ecommerce_purchase', [VarsRuntime::class, 'gtmEnhancedEcommercePurchase']),
            new TwigFunction('gtm_enhanced_ecommerce_product_object', [VarsRuntime::class, 'gtmEnhancedEcommerceProductObject']),
        ];
    }

    public function getName(): string
    {
        return 'ecommerce.extension';
    }

}
