<?php

namespace App\SeoBundle\Service\Sitemap;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SitemapUrlsService
{
    private RouterInterface $route;

    private array $prepareURLsForSitemap = [];

    public function __construct(RouterInterface $route)
    {
        $this->route = $route;
    }

    public function generateUrl($route, $parameters = []): string
    {
        return $this->route->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function addPrepareURLsForSitemap(string $url, \DateTime $lastModifiedDate = null): void
    {
        if ($lastModifiedDate == null) {
            $lastModifiedDate = new \DateTime();
        }
        if (!array_key_exists($url, $this->prepareURLsForSitemap)) {
            $this->prepareURLsForSitemap[$url] = [
                "url" => $url,
                "lastModifiedDate" => $lastModifiedDate,
            ];
        }
    }

    public function getPrepareURLsForSitemap(): array
    {
        return $this->prepareURLsForSitemap;
    }

}
