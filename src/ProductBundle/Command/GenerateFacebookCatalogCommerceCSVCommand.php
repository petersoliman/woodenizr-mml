<?php

namespace App\ProductBundle\Command;

use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\CurrencyBundle\Service\ExchangeRateService;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Brand;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Model\ProductSearchModel;
use App\ProductBundle\Repository\ProductPriceRepository;
use App\ProductBundle\Repository\ProductSearchRepository;
use PN\ServiceBundle\Lib\UploadPath;
use PN\ServiceBundle\Service\UrlService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class GenerateFacebookCatalogCommerceCSVCommand extends Command
{
    protected static $defaultName = 'app:generate-facebook-catalog-commerce-csv';


    public function __construct(
        private RouterInterface         $router,
        private UrlService              $urlService,
        private ProductSearchRepository $productSearchRepository,
        private ProductPriceRepository  $productPriceRepository,
        private CurrencyRepository      $currencyRepository,
        private ExchangeRateService     $exchangeRateService,

    )
    {
        parent::__construct();

    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate CSV file with all product data for Facebook Catalog Commerce')
            ->setHelp('Run every 3 days'); // 0 3 */3 * *
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $search = new \stdClass();
        $products = $this->productSearchRepository->filter($search);

        $csvArray = $this->createCSV($output, $products);
        $this->save($csvArray);
        return Command::SUCCESS;
    }

    private function createCSV(OutputInterface $output, array $products): array
    {
        $progressBar = new ProgressBar($output, count($products));


        $list = [];
        $list [] = [
            'id', # Required | A unique content ID for the item. Use the item's SKU if you can. Each content ID must appear only once in your catalog. To run dynamic ads this ID must exactly match the content ID for the same item in your Meta Pixel code. Character limit: 100
            'title', # Required | A specific and relevant title for the item. See title specifications: https://www.facebook.com/business/help/2104231189874655 Character limit: 200
            'description', // Required | A short and relevant description of the item. Include specific or unique product features like material or color. Use plain text and don't enter text in all capital letters. See description specifications: https://www.facebook.com/business/help/2302017289821154 Character limit: 9999
            'availability', # Required | The current availability of the item. | Supported values: in stock; out of stock
            'condition',# Required | The current condition of the item. | Supported values: new; used
            'price', # Required | The price of the item. Format the price as a number followed by the 3-letter currency code (ISO 4217 standards). Use a period (.) as the decimal point; don't use a comma.
            'link', # Required | The URL of the specific product page where people can buy the item.
            'image_link',# Required | The URL for the main image of your item. Images must be in a supported format (JPG/GIF/PNG) and at least 500 x 500 pixels.
            'brand',# Required | The brand name of the item. Character limit: 100.
            'google_product_category', # Optional | The Google product category for the item. Learn more about product categories: https://www.facebook.com/business/help/526764014610932.
            'fb_product_category', # Optional | The Facebook product category for the item. Learn more about product categories: https://www.facebook.com/business/help/526764014610932.
            'quantity_to_sell_on_facebook', # Optional | The quantity of this item you have to sell on Facebook and Instagram with checkout. Must be 1 or higher or the item won't be buyable
            'sale_price', # Optional | The discounted price of the item if it's on sale. Format the price as a number followed by the 3-letter currency code (ISO 4217 standards). Use a period (.) as the decimal point; don't use a comma. A sale price is required if you want to use an overlay for discounted prices.
            'sale_price_effective_date', # Optional | The time range for your sale period. Includes the date and time/time zone when your sale starts and ends. If this field is blank any items with a sale_price remain on sale until you remove the sale price. Use this format: YYYY-MM-DDT23:59+00:00/YYYY-MM-DDT23:59+00:00. Enter the start date as YYYY-MM-DD. Enter a 'T'. Enter the start time in 24-hour format (00:00 to 23:59) followed by the UTC time zone (-12:00 to +14:00). Enter '/' and then repeat the same format for your end date and time. The example row below uses PST time zone (-08:00).
            'item_group_id', # Optional | Use this field to create variants of the same item. Enter the same group ID for all variants within a group. Learn more about variants: https://www.facebook.com/business/help/2256580051262113 Character limit: 100.
            'gender', # Optional | The gender of a person that the item is targeted towards. | Supported values: female; male; unisex
            'color', # Optional | The color of the item. Use one or more words to describe the color. Don't use a hex code. Character limit: 200.
            'size', # Optional | The size of the item written as a word or abbreviation or number. For example: small; XL; 12. Character limit: 200.
            'age_group', # Optional | The age group that the item is targeted towards. | Supported values: adult; all ages; infant; kids; newborn; teen; toddler
            'material', # Optional | The material that the item is made from; such as cotton; denim or leather. Character limit: 200.
            'pattern', # Optional | The pattern or graphic print on the item. Character limit: 100.
            'shipping', # Optional | Delivery details for the item. Format as Country:Region:Service:Price. Include the 3-letter ISO 4217 currency code in the price. Enter the price as 0.0 to use the free delivery overlay in your ads. Use a semi-colon ";" or a comma ";" to separate multiple delivery details for different regions or countries. Only people in the specified region or country will see delivery details for that region or country. You can leave out the region (keep the double "::") if your delivery details are the same for an entire country.
            'shipping_weight', # Optional | The shipping weight of the item. Include the unit of measurement (lb/oz/g/kg).
            'gtin', # Optional | The item's Global Trade Item Number (GTIN). Recommended to help classify the item. May appear on the barcode; packaging or book cover. Only provide GTIN if you're sure that it's correct. GTIN types include UPC (12 digits); EAN (13 digits); JAN (8 or 13 digits); ISBN (13 digits) or ITF-14 (14 digits)
            'video[0].url', # Optional | URLs and tags for videos to be used in your ads or in shops. Supports up to 20 different videos. Must be a direct link to download the video file; not a link to a video player; such as YouTube. Tags are optional and; if used; should describe what is in the video. Learn more about video field specifications at: https://www.facebook.com/business/help/120325381656392
            'video[0].tag[0]', # Optional | URLs and tags for videos to be used in your ads or in shops. Supports up to 20 different videos. Must be a direct link to download the video file; not a link to a video player; such as YouTube. Tags are optional and; if used; should describe what is in the video. Learn more about video field specifications at: https://www.facebook.com/business/help/120325381656392
            'product_tags[0]', # Optional | The list of all tags the product should have
            'product_tags[1]', # Optional | The list of all tags the product should have
            'style[0]'
        ];
        /**
         * @var ProductSearchModel $product
         */
        foreach ($products as $product) {
            $product = $product->getProduct();
            $url = $this->router->generate("fe_product_show", ["slug" => $product->getSeo()->getSlug()],
                UrlGeneratorInterface::ABSOLUTE_URL);

            $urlArabic = $this->router->generate("fe_product_show",
                ["_locale" => "ar", "slug" => $product->getSeo()->getSlug()],
                UrlGeneratorInterface::ABSOLUTE_URL);

            $originalPrice = $this->getProductOriginalPrice($product);

            $productSellPrice = $this->getProductSellPrice($product);
            $salePriceEffectiveDate = $salePrice = "";
            if ($originalPrice != $productSellPrice) {
                $salePrice = $productSellPrice . " " . $this->getProductPriceCurrency($product);
                $promotionExpiryDate = $this->getProductPrice($product)->getPromotionalExpiryDate();
                if ($promotionExpiryDate instanceof \DateTime) {
                    $salePriceEffectiveDate = date('Y-m-d') . "T23:59+00:00/" . $promotionExpiryDate->format('Y-m-d') . "T23:59+00:00";
                }
            }

            $list [] = [
                $product->getId(), # Required | A unique content ID for the item. Use the item's SKU if you can. Each content ID must appear only once in your catalog. To run dynamic ads this ID must exactly match the content ID for the same item in your Meta Pixel code. Character limit: 100
                $product->getTitle(), # Required | A specific and relevant title for the item. See title specifications: https://www.facebook.com/business/help/2104231189874655 Character limit: 200
                $this->getProductDescription($product), // Required | A short and relevant description of the item. Include specific or unique product features like material or color. Use plain text and don't enter text in all capital letters. See description specifications: https://www.facebook.com/business/help/2302017289821154 Character limit: 9999
                $this->getProductStock($product), # Required | The current availability of the item. | Supported values: in stock; out of stock
                'new',# Required | The current condition of the item. | Supported values: new; used
                $originalPrice . " " . $this->getProductPriceCurrency($product), # Required | The price of the item. Format the price as a number followed by the 3-letter currency code (ISO 4217 standards). Use a period (.) as the decimal point; don't use a comma.
                $urlArabic, # Required | The URL of the specific product page where people can buy the item.
                $this->getProductImages($product),# Required | The URL for the main image of your item. Images must be in a supported format (JPG/GIF/PNG) and at least 500 x 500 pixels.
                $this->getProductBrand($product),# Required | The brand name of the item. Character limit: 100.
                '', # Optional | The Google product category for the item. Learn more about product categories: https://www.facebook.com/business/help/526764014610932.
                '', # Optional | The Facebook product category for the item. Learn more about product categories: https://www.facebook.com/business/help/526764014610932.
                $this->getProductPrice($product)->getStock(), # Optional | The quantity of this item you have to sell on Facebook and Instagram with checkout. Must be 1 or higher or the item won't be buyable
                $salePrice, # Optional | The discounted price of the item if it's on sale. Format the price as a number followed by the 3-letter currency code (ISO 4217 standards). Use a period (.) as the decimal point; don't use a comma. A sale price is required if you want to use an overlay for discounted prices.
                $salePriceEffectiveDate, # Optional | The time range for your sale period. Includes the date and time/time zone when your sale starts and ends. If this field is blank any items with a sale_price remain on sale until you remove the sale price. Use this format: YYYY-MM-DDT23:59+00:00/YYYY-MM-DDT23:59+00:00. Enter the start date as YYYY-MM-DD. Enter a 'T'. Enter the start time in 24-hour format (00:00 to 23:59) followed by the UTC time zone (-12:00 to +14:00). Enter '/' and then repeat the same format for your end date and time. The example row below uses PST time zone (-08:00).
                '', # Optional | Use this field to create variants of the same item. Enter the same group ID for all variants within a group. Learn more about variants: https://www.facebook.com/business/help/2256580051262113 Character limit: 100.
                'unisex', # Optional | The gender of a person that the item is targeted towards. | Supported values: female; male; unisex
                '', # Optional | The color of the item. Use one or more words to describe the color. Don't use a hex code. Character limit: 200.
                '', # Optional | The size of the item written as a word or abbreviation or number. For example: small; XL; 12. Character limit: 200.
                '', # Optional | The age group that the item is targeted towards. | Supported values: adult; all ages; infant; kids; newborn; teen; toddler
                '', # Optional | The material that the item is made from; such as cotton; denim or leather. Character limit: 200.
                '', # Optional | The pattern or graphic print on the item. Character limit: 100.
                '', # Optional | Delivery details for the item. Format as Country:Region:Service:Price. Include the 3-letter ISO 4217 currency code in the price. Enter the price as 0.0 to use the free delivery overlay in your ads. Use a semi-colon ";" or a comma ";" to separate multiple delivery details for different regions or countries. Only people in the specified region or country will see delivery details for that region or country. You can leave out the region (keep the double "::") if your delivery details are the same for an entire country.
                '', # Optional | The shipping weight of the item. Include the unit of measurement (lb/oz/g/kg).
                '', # Optional | The item's Global Trade Item Number (GTIN). Recommended to help classify the item. May appear on the barcode; packaging or book cover. Only provide GTIN if you're sure that it's correct. GTIN types include UPC (12 digits); EAN (13 digits); JAN (8 or 13 digits); ISBN (13 digits) or ITF-14 (14 digits)
                '', # Optional | URLs and tags for videos to be used in your ads or in shops. Supports up to 20 different videos. Must be a direct link to download the video file; not a link to a video player; such as YouTube. Tags are optional and; if used; should describe what is in the video. Learn more about video field specifications at: https://www.facebook.com/business/help/120325381656392
                '', # Optional | URLs and tags for videos to be used in your ads or in shops. Supports up to 20 different videos. Must be a direct link to download the video file; not a link to a video player; such as YouTube. Tags are optional and; if used; should describe what is in the video. Learn more about video field specifications at: https://www.facebook.com/business/help/120325381656392
                '', # Optional | The list of all tags the product should have
                '', # Optional | The list of all tags the product should have
                ''
            ];

            $progressBar->advance();
        }

        return $list;
    }

    private function save(array $list): void
    {

        $filePath = UploadPath::getRootDir() . "facebook-commerce-catalog.csv";

        // Open/Create the file
        $f = fopen($filePath, 'w');

        // Write to the csv
        foreach ($list as $fields) {
            fputcsv($f, $fields, ",");
        }

        // Close the file
        fclose($f);
    }

    private function getProductOriginalPrice(Product $product): string
    {
        $productPrice = $this->getProductPrice($product);
        $commissionPrice = $productPrice->getUnitPrice();
        $productCurrency = $productPrice->getCurrency();
        if ($productCurrency->getId() == Currency::EGP) {
            return round($commissionPrice, 2);
        }

        $EGPCurrency = $this->currencyRepository->find(Currency::EGP);

        $exchangeRate = $this->exchangeRateService->getExchangeRate($productCurrency, $EGPCurrency);

        return round($commissionPrice * $exchangeRate, 2);
    }

    private function getProductSellPrice(Product $product): string
    {
        $productPrice = $this->getProductPrice($product);
        $commissionPrice = $productPrice->getSellPrice();
        $productCurrency = $productPrice->getCurrency();
        if ($productCurrency->getId() == Currency::EGP) {
            return round($commissionPrice, 2);
        }

        $EGPCurrency = $this->currencyRepository->find(Currency::EGP);

        $exchangeRate = $this->exchangeRateService->getExchangeRate($productCurrency, $EGPCurrency);

        return round($commissionPrice * $exchangeRate, 2);
    }

    private function getProductPriceCurrency(Product $product): string
    {
        return "EGP";
    }

    private function getProductBrand(Product $product): ?string
    {
        if ($product->getBrand() instanceof Brand) {
            return $product->getBrand()->getTitle();
        }

        return null;
    }

    private function getProductStock(Product $product): string
    {
        $productPrice = $this->getProductPrice($product);
        if ($productPrice->getStock() > 0) {
            return "in stock";
        }

        return "out of stock";
    }

    private function getProductImages(Product $product): string
    {
        $mainImage = $product->getMainImage();
        if ($mainImage instanceof Image) {
            return $this->urlService->asset($mainImage->getAssetPath());
        }
        return "";
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
        $search = array('&rsquo;', '&nbsp;', '&bull;', "\n", "\t", "\r", "\v", "\e");
        $str = str_replace($search, '', $str);

        if (strlen($str) > 9999) {
            $str = substr($str, 0, 9999);
        }
        return htmlspecialchars_decode($str) . '...';
    }

    private function getProductPrice($product): ProductPrice
    {
        if (isset($product->minPrice) and $product->minPrice instanceof ProductPrice) {
            return $product->minPrice;
        }

        return $this->productPriceRepository->getMinPrice($product);
    }
}
