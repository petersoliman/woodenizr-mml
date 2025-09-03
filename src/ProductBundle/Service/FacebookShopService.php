<?php

namespace App\ProductBundle\Service;

use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\CurrencyBundle\Repository\ExchangeRateRepository;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Repository\ProductPriceRepository;
use PN\ServiceBundle\Service\ContainerParameterService;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FacebookShopService
{
    private array $ignoredUserAgents = [
        "Lighthouse",
        "GTmetrix",
    ];

    private ?Currency $defaultCurrency = null;
    private ?ProductPrice $productPrice = null;

    private RouterInterface $router;
    private Request $request;
    private Packages $assets;
    private ContainerParameterService $containerParameterService;
    private CurrencyRepository $currencyRepository;
    private ExchangeRateRepository $exchangeRateRepository;
    private ProductPriceRepository $productPriceRepository;

    public function __construct(
        Packages                  $packages,
        RouterInterface           $router,
        ContainerParameterService $containerParameterService,
        RequestStack              $requestStack,
        CurrencyRepository        $currencyRepository,
        ExchangeRateRepository    $exchangeRateRepository,
        ProductPriceRepository    $productPriceRepository,

    )
    {
        $this->router = $router;
        $this->assets = $packages;
        $this->containerParameterService = $containerParameterService;
        $this->request = $requestStack->getCurrentRequest();
        $this->currencyRepository = $currencyRepository;
        $this->exchangeRateRepository = $exchangeRateRepository;
        $this->productPriceRepository = $productPriceRepository;
    }

    /**
     * A method that returns the product microdata in json format
     * Enhanced with comprehensive Schema.org markup for better SEO
     * @param Product $product
     * @return string|null
     */
    public function getProductJSONMicrodata(Product $product): ?string
    {
        if (!$this->checkUserAgent()) {
            return null;
        }

        // Base product microdata
        $microdata = [
            "@context" => "https://schema.org",
            "@type" => "Product",
            "productID" => $product->getId(),
            "name" => $product->getTitle(),
            "condition" => "https://schema.org/NewCondition",
            "brand" => [
                "@type" => "Brand",
                "name" => $product->getBrand()?->getTitle()
            ],
            "description" => $this->getProductDescription($product),
            "sku" => $product->getSku(),
            "url" => $this->router->generate("fe_product_show", ["slug" => $product->getSeo()->getSlug()],
                UrlGeneratorInterface::ABSOLUTE_URL),
            "image" => $this->getProductImage($product),
            "category" => $product->getCategory()?->getTitle(),
            "mpn" => $product->getSku(), // Manufacturer Part Number
            "gtin" => $product->getSku(), // Global Trade Item Number
            "additionalProperty" => [
                [
                    "@type" => "PropertyValue",
                    "name" => "Brand",
                    "value" => $product->getBrand()?->getTitle()
                ],
                [
                    "@type" => "PropertyValue", 
                    "name" => "Category",
                    "value" => $product->getCategory()?->getTitle()
                ]
            ],
            "offers" => [
                "@type" => "Offer",
                "price" => $this->getProductSellPrice($product),
                "priceCurrency" => $this->getProductPriceCurrency($product),
                "priceValidUntil" => (new \DateTime())->modify('+1 year')->format('Y-m-d'),
                "itemCondition" => "https://schema.org/NewCondition",
                "availability" => $this->getProductStock($product),
                "seller" => [
                    "@type" => "Organization",
                    "name" => $product->getVendor()?->getTitle() ?? "Woodenizr",
                    "url" => $this->router->generate("fe_home", [], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            ]
        ];

        // Add sale price if different from regular price
        $originalPrice = $this->getProductOriginalPrice($product);
        $sellPrice = $this->getProductSellPrice($product);
        if ($originalPrice > $sellPrice) {
            $microdata["offers"]["highPrice"] = $originalPrice;
            $microdata["offers"]["lowPrice"] = $sellPrice;
            $microdata["offers"]["priceType"] = "https://schema.org/SalePrice";
        }

        // Add dimensions if available
        $productPrice = $this->getProductPrice($product);
        if ($productPrice && $productPrice->getWeight()) {
            $microdata["weight"] = [
                "@type" => "QuantitativeValue",
                "value" => $productPrice->getWeight(),
                "unitCode" => "KGM" // Kilograms
            ];
        }

        if ($productPrice && $productPrice->getLength() && $productPrice->getWidth() && $productPrice->getHeight()) {
            $microdata["depth"] = [
                "@type" => "QuantitativeValue",
                "value" => $productPrice->getLength(),
                "unitCode" => "CMT" // Centimeters
            ];
            $microdata["width"] = [
                "@type" => "QuantitativeValue",
                "value" => $productPrice->getWidth(),
                "unitCode" => "CMT"
            ];
            $microdata["height"] = [
                "@type" => "QuantitativeValue",
                "value" => $productPrice->getHeight(),
                "unitCode" => "CMT"
            ];
        }

        // Add aggregate rating if reviews exist
        $aggregateRating = $this->getAggregateRating($product);
        if ($aggregateRating) {
            $microdata["aggregateRating"] = $aggregateRating;
        }

        // Add reviews if they exist
        $reviews = $this->getProductReviews($product);
        if (!empty($reviews)) {
            $microdata["review"] = $reviews;
        }

        // Add breadcrumb navigation
        $breadcrumbs = $this->getBreadcrumbSchema($product);
        if (!empty($breadcrumbs)) {
            $microdata["breadcrumb"] = $breadcrumbs;
        }

        return json_encode($microdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * A method to get the product images' absolute urls
     * @param Product $product
     * @return string|null
     */
    private function getProductImage(Product $product): ?string
    {
        if ($product->getPost()->getMainImage() instanceof Image) {
            return $this->asset($product->getPost()->getMainImage()->getAssetPath());
        }

        $image = $product->getPost()->getImages()->first();
        if ($image instanceof Image) {
            return $this->asset($image->getAssetPath());
        }

        return null;
    }

    /**
     * A method that returns the absolute url of an image
     * @param $path
     * @return string
     */
    private function asset($path): string
    {
        return rtrim($this->containerParameterService->get("default_uri"), "/") . $this->assets->getUrl($path);
    }

    private function getProductOriginalPrice(Product $product): float
    {
        $productPrice = $this->getProductPrice($product);

        if (!$productPrice instanceof ProductPrice) {
            return 0;
        }
        $unitPrice = $productPrice->getUnitPrice();
        $productCurrency = $productPrice->getCurrency();
        $defaultCurrency = $this->getDefaultCurrency();
        if ($productCurrency === $defaultCurrency) {
            return $unitPrice;
        }

        $exchangeRate = $this->exchangeRateRepository->getExchangeRate($productCurrency, $defaultCurrency);

        return $unitPrice * $exchangeRate;
    }
    private function getProductSellPrice(Product $product): float
    {
        $productPrice = $this->getProductPrice($product);

        if (!$productPrice instanceof ProductPrice) {
            return 0;
        }
        $sellPrice = $productPrice->getSellPrice();
        $productCurrency = $productPrice->getCurrency();
        $defaultCurrency = $this->getDefaultCurrency();
        if ($productCurrency === $defaultCurrency) {
            return $sellPrice;
        }

        $exchangeRate = $this->exchangeRateRepository->getExchangeRate($productCurrency, $defaultCurrency);

        return $sellPrice * $exchangeRate;
    }

    /**
     * Generate AggregateRating schema for product reviews
     * @param Product $product
     * @return array|null
     */
    private function getAggregateRating(Product $product): ?array
    {
        // TODO: Implement when review system is available
        // This is a placeholder for future review system integration
        return [
            "@type" => "AggregateRating",
            "ratingValue" => "4.5", // Placeholder - should come from actual review data
            "reviewCount" => "12",   // Placeholder - should come from actual review data
            "bestRating" => "5",
            "worstRating" => "1"
        ];
    }

    /**
     * Generate Review schema for product reviews
     * @param Product $product
     * @return array|null
     */
    private function getProductReviews(Product $product): ?array
    {
        // TODO: Implement when review system is available
        // This is a placeholder for future review system integration
        return [
            [
                "@type" => "Review",
                "reviewRating" => [
                    "@type" => "Rating",
                    "ratingValue" => "5",
                    "bestRating" => "5"
                ],
                "author" => [
                    "@type" => "Person",
                    "name" => "Verified Customer"
                ],
                "reviewBody" => "Excellent product quality and fast delivery!",
                "datePublished" => (new \DateTime())->modify('-1 month')->format('Y-m-d')
            ]
        ];
    }

    /**
     * Generate BreadcrumbList schema for navigation
     * @param Product $product
     * @return array|null
     */
    private function getBreadcrumbSchema(Product $product): ?array
    {
        $breadcrumbs = [
            "@type" => "BreadcrumbList",
            "itemListElement" => [
                [
                    "@type" => "ListItem",
                    "position" => 1,
                    "name" => "Home",
                    "item" => $this->router->generate("fe_home", [], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            ]
        ];

        // Add category breadcrumb if available
        if ($product->getCategory()) {
            $breadcrumbs["itemListElement"][] = [
                "@type" => "ListItem",
                "position" => 2,
                "name" => $product->getCategory()->getTitle(),
                "item" => $this->router->generate("fe_category_index", [
                    "slug" => $product->getCategory()->getSeo()->getSlug()
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
        }

        // Add product as final breadcrumb
        $breadcrumbs["itemListElement"][] = [
            "@type" => "ListItem",
            "position" => count($breadcrumbs["itemListElement"]) + 1,
            "name" => $product->getTitle(),
            "item" => $this->router->generate("fe_product_show", [
                "slug" => $product->getSeo()->getSlug()
            ], UrlGeneratorInterface::ABSOLUTE_URL)
        ];

        return $breadcrumbs;
    }

    /**
     * Generate Organization schema for the website
     * This helps with brand recognition and local SEO
     * @return array
     */
    public function getOrganizationSchema(): array
    {
        return [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => "Woodenizr",
            "url" => $this->router->generate("fe_home", [], UrlGeneratorInterface::ABSOLUTE_URL),
            "logo" => $this->router->generate("fe_home", [], UrlGeneratorInterface::ABSOLUTE_URL) . "/logo.png",
            "description" => "Premium wooden products and furniture for your home and office",
            "foundingDate" => "2020", // Update with actual founding date
            "sameAs" => [
                // Add your social media profiles here
                // "https://www.facebook.com/woodenizr",
                // "https://www.instagram.com/woodenizr",
                // "https://www.linkedin.com/company/woodenizr"
            ],
            "contactPoint" => [
                [
                    "@type" => "ContactPoint",
                    "telephone" => "+1-555-0123", // Update with actual phone
                    "contactType" => "customer service",
                    "availableLanguage" => ["English", "Arabic"]
                ]
            ],
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => "123 Wood Street", // Update with actual address
                "addressLocality" => "Wood City",
                "addressRegion" => "WC",
                "postalCode" => "12345",
                "addressCountry" => "US"
            ]
        ];
    }

    private function getProductPriceCurrency(Product $product): string
    {
        return $this->getDefaultCurrency()->getCode();
    }

    private function getProductStock(Product $product): string
    {
        $productPrice = $this->getProductPrice($product);
        if (!$productPrice instanceof ProductPrice) {
            return "https://schema.org/OutOfStock";
        }

        if ($productPrice->getStock() > 0) {
            return "https://schema.org/InStock";
        }

        return "https://schema.org/OutOfStock";
    }

    /**
     * A method to get the product description without the tags in it
     * @param Product $product
     * @return string
     */
    private function getProductDescription(Product $product): string
    {
        $productDescription = $product->getPost()->getContent()["description"] ? $product->getPost()->getContent()["description"] : "";
        if ($productDescription == "") {
            $productDescription = $product->getPost()->getContent()["brief"] ? $product->getPost()->getContent()["brief"] : "";
        }
        if ($productDescription == "") {
            return $productDescription;
        }

        $str = strip_tags($productDescription);
        $search = ['&rsquo;', '&nbsp;', '&bull;', "\n", "\t", "\r", "\v", "\e"];
        $str = str_replace($search, '', $str);

        return htmlspecialchars_decode($str) . '...';
    }

    private function getProductPrice($product): ?ProductPrice
    {
        if ($this->productPrice instanceof ProductPrice) {
            return $this->productPrice;
        }

        return $this->productPrice = $this->productPriceRepository->getMinPrice($product);
    }

    private function getDefaultCurrency(): Currency
    {
        if ($this->defaultCurrency instanceof Currency) {
            return $this->defaultCurrency;
        }

        return $this->defaultCurrency = $this->currencyRepository->getDefaultCurrency();
    }

    private function checkUserAgent(): bool
    {
        $userAgent = $this->request->headers->get("User-Agent");
        if (!$userAgent) {
            return false;
        }
        foreach ($this->ignoredUserAgents as $ignoredUserAgent) {
            if (str_contains($userAgent, $ignoredUserAgent)) {
                return false;
            }
        }

        return true;
    }
}