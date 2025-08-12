<?php

namespace App\SeoBundle\Service;

use App\SeoBundle\Service\Sitemap\GenerateDynamicSitemapService;
use App\SeoBundle\Service\Sitemap\GenerateStaticSitemapService;
use App\SeoBundle\Service\Sitemap\SitemapUrlsService;

class GenerateSitemapService
{
    private SitemapUrlsService $sitemapUrlsService;
    private GenerateStaticSitemapService $staticSitemapService;
    private GenerateDynamicSitemapService $dynamicSitemapService;

    public function __construct(
        SitemapUrlsService $sitemapUrlsService,
        GenerateStaticSitemapService $staticSitemapService,
        GenerateDynamicSitemapService $dynamicSitemapService
    ) {
        $this->sitemapUrlsService = $sitemapUrlsService;
        $this->staticSitemapService = $staticSitemapService;
        $this->dynamicSitemapService = $dynamicSitemapService;
    }

    public function generate(): array
    {
        $this->staticSitemapService->generate();
        $this->dynamicSitemapService->generate();

        return $this->sitemapUrlsService->getPrepareURLsForSitemap();
    }

}
