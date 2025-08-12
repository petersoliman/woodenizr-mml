<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Repository\CategoryRepository;
use PN\ServiceBundle\Service\ContainerParameterService;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class CategoryWebsiteHeaderService
{
    const CACHE_NAME = 'menu.category.';
    private CategoryRepository $categoryRepository;
    private ContainerParameterService $containerParameterService;


    public function __construct(CategoryRepository $categoryRepository, ContainerParameterService $containerParameterService)
    {
        $this->categoryRepository = $categoryRepository;
        $this->containerParameterService = $containerParameterService;
    }

    public function getData(string $locale): array
    {
        $cacheKey = $this->getCacheName($locale);
        $categoryRepository = $this->categoryRepository;
        $cache = new FilesystemAdapter(directory: $this->containerParameterService->get("kernel.cache_dir") . "/filesystemAdapter-cache");
        return $cache->get($cacheKey, function (ItemInterface $item) use ($categoryRepository) {
            $item->expiresAfter(86400);// expire after 24 hrs

            $searchCategories = new \stdClass();
            $searchCategories->deleted = 0;
            $searchCategories->publish = 1;
            $searchCategories->parent = "";
//            $searchCategories->hasPublishProduct = true;
            return $categoryRepository->filter($searchCategories);
        });
    }

    public function removeAllCache(): void
    {
        $cache = new FilesystemAdapter(directory: $this->containerParameterService->get("kernel.cache_dir") . "/filesystemAdapter-cache");
        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $cacheKey = $this->getCacheName($locale);
            $cache->delete($cacheKey);
        }
    }

    private function getCacheName(string $locale): string
    {
        return self::CACHE_NAME . $locale;
    }

    private function getLocales(): array
    {
        $locales = [];
        if ($this->containerParameterService->has("app.locales")) {
            $locales = explode("|", $this->containerParameterService->get("app.locales"));
        } elseif ($this->containerParameterService->has("locale")) {
            $locales[] = $this->containerParameterService->get("locale");
        } elseif ($this->containerParameterService->has("default_locale")) {
            $locales[] = $this->containerParameterService->get("default_locale");
        } else {
            $locales[] = ["en"];
        }

//        if (($key = array_search("en", $locales)) !== false) {
//            unset($locales[$key]);
//        }

        return array_filter($locales, function ($var) {
            return ($var !== null && $var !== false && $var !== "");
        });
    }

}
