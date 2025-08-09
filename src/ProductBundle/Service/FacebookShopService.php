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
     * @param Product $product
     * @return string|null
     */
    public function getProductJSONMicrodata(Product $product): ?string
    {
        if (!$this->checkUserAgent()) {
            return null;
        }

        $microdata = [
            "@context" => "https://schema.org",
            "@type" => "Product",
            "productID" => $product->getId(),
            "name" => $product->getTitle(),
            "condition" => "new",
            "brand" => $product->getBrand()?->getTitle(),
            "description" => $this->getProductDescription($product),
            "sku" => $product->getSku(),
            "url" => $this->router->generate("fe_product_show", ["slug" => $product->getSeo()->getSlug()],
                UrlGeneratorInterface::ABSOLUTE_URL),
            "image" => $this->getProductImage($product),
//            "category" => (string)$this->getProductGoogleCategoryId($product),
            "offers" => [
                "@type" => "Offer",
                "price" => $this->getProductSellPrice($product),
                "priceCurrency" => $this->getProductPriceCurrency($product),
                "salePrice" => $this->getProductOriginalPrice($product),
                "salePriceCurrency" => $this->getProductPriceCurrency($product),
                "itemCondition" => "https://schema.org/NewCondition",
                "availability" => $this->getProductStock($product),
            ],
        ];

        return json_encode($microdata);
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