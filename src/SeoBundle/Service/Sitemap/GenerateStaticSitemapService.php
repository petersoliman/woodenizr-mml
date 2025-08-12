<?php

namespace App\SeoBundle\Service\Sitemap;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class GenerateStaticSitemapService
{
    private RouterInterface $route;
    private SitemapUrlsService $sitemapUrlsService;

    const DEBUG = false;
    private array $errorRouteNames = [];

    const EXCLUDE_ROUTE_NAMES = [
        "fe_profile_edit",
        "fe_profile_wishlist",
        "fe_profile_address_book",
        "fe_profile_my_orders",
        "fe_profile_change_password",
        "fe_payment_paymob_process_pay",
        "fe_cart_show",
        "fe_cart_payment_method",
        "fe_cart_shipping_address",
        "fe_cart_order_summery",
        "fe_order_success_failure",
        "fe_career",
        "fe_blog_category",
        "fe_blog_tag",
        "fe_product_filter_search",
        "fe_contact_thanks",
    ];

    public function __construct(
        RouterInterface    $route,
        SitemapUrlsService $sitemapUrlsService
    )
    {
        $this->route = $route;
        $this->sitemapUrlsService = $sitemapUrlsService;
    }


    public function generate(): void
    {
        $routes = $this->route->getRouteCollection();
        $homeRoute = $routes->get('fe_home');
        $this->addRouteWithAllLanguagesToURLsForSitemap("fe_home", $homeRoute);

        foreach ($routes as $routeName => $route) {
            if (!$this->isValidRoute($routeName, $route)) {
                continue;
            }
            $this->addRouteWithAllLanguagesToURLsForSitemap($routeName, $route);
        }

        if (self::DEBUG) {
            if (count($this->errorRouteNames) > 0) {
                throw new \Exception(implode("\n", $this->errorRouteNames));
            }
        }
    }

    private function isValidRoute(string $routeName, Route $route): bool
    {

        if (in_array($routeName, self::EXCLUDE_ROUTE_NAMES)) {
            return false;
        }
        if (
            !str_contains($routeName, "fe_")
            or str_contains($route->getPath(), "/admin")
            or str_contains($routeName, "test")
            or str_contains($routeName, "ajax")
            or str_contains($routeName, "api")
            or str_contains($routeName, "fe_home")
            or str_contains($route->getPath(), "ajax")
        ) {
            return false;
        }
        if (str_contains($route->getPath(), "{")) {
            $defaultParams = array_keys($route->getDefaults());

            foreach ($defaultParams as &$val) {
                $val = "{" . $val . "}";
            }
            $virtualPath = str_replace($defaultParams, "", $route->getPath());

            if (str_contains($virtualPath, "{")) {
                return false;
            }
        }
        if (count($route->getMethods()) == 1 and strtolower($route->getMethods()[0]) == "post") {
            return false;
        }

        return true;
    }

    private function addRouteWithAllLanguagesToURLsForSitemap(string $routeName, Route $route): void
    {
        $locales = [];
        $localeParameterName = "_locale";

        if ($route->getRequirement($localeParameterName)) {
            $locales = explode("|", $route->getRequirement($localeParameterName));
            $locales = array_filter($locales, function ($var) {
                return ($var !== null && $var !== false && $var !== "");
            });

        }

        if (count($locales) > 0) {
            foreach ($locales as $locale) {
                $parameters = [
                    $localeParameterName => $locale,
                ];
                $this->addSingleRoute($routeName, $parameters);
            }
        } else {
            $this->addSingleRoute($routeName);
        }
    }


    private function addSingleRoute(string $routeName, array $parameters = []): void
    {

        $url = $this->sitemapUrlsService->generateUrl($routeName, $parameters);

        if (self::DEBUG) {
            $this->debugUrl($routeName, $url);
        }

        $this->sitemapUrlsService->addPrepareURLsForSitemap($url);
    }

    private function debugUrl(string $routeName, string $url): void
    {

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);


        if ($httpCode != 200) {
            $this->errorRouteNames[] = "This route name \"$routeName\" is return this code $httpCode";
        }
    }
}