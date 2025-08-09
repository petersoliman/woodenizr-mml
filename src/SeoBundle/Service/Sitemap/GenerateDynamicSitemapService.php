<?php

namespace App\SeoBundle\Service\Sitemap;

use App\BaseBundle\SystemConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\ContainerParameterService;

class GenerateDynamicSitemapService
{
    private EntityManagerInterface $em;
    private SitemapUrlsService $sitemapUrlsService;
    private ContainerParameterService $containerParameter;


    public function __construct(
        EntityManagerInterface    $em,
        SitemapUrlsService        $sitemapUrlsService,
        ContainerParameterService $containerParameter
    )
    {
        $this->em = $em;
        $this->sitemapUrlsService = $sitemapUrlsService;
        $this->containerParameter = $containerParameter;
    }

    public function generate(): void
    {
        $this->generateProducts();
        $this->generateCategories();

        if (SystemConfiguration::ENABLE_COLLECTION) {
            $this->generateCollections();
        }

        if (SystemConfiguration::ENABLE_OCCASION) {
            $this->generateOccasions();
        }
        $this->generateBrands();
        $this->generateBlogs();// tested

        // $this->generateBlogCategories();
        // $this->generateBlogTags();
        // $this->generateProjects();// tested
    }

    private function generateProducts()
    {
        $routeName = "fe_product_show";

        $sql = "SELECT s.slug, p.modified FROM product_search ps "
            . "LEFT JOIN product p ON p.id=ps.product_id "
            . "LEFT JOIN seo s ON s.id=p.seo_id;";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();
        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }
        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, p.modified FROM product_search ps "
                . "LEFT JOIN product p ON p.id=ps.product_id "
                . "LEFT JOIN seo s ON s.id=p.seo_id "
                . "LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                . "LEFT JOIN `language` l ON l.id=st.language_id "
                . "WHERE l.locale=:locale";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }

    private function generateCollections()
    {
        $routeName = "fe_product_filter_collection";

        $sql = "SELECT s.slug, t.modified FROM collection t "
            . "LEFT JOIN seo s ON s.id=t.seo_id "
            . "WHERE t.publish=1 AND t.deleted IS NULL";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();

        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }
        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, t.modified FROM collection t "
                . "LEFT JOIN seo s ON s.id=t.seo_id "
                . "LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                . "LEFT JOIN `language` l ON l.id=st.language_id "
                . "WHERE l.locale=:locale AND t.publish=1 AND t.deleted IS NULL";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }

    private function generateOccasions()
    {
        $routeName = "fe_product_filter_occasion";

        $sql = "SELECT s.slug, t.modified FROM occasion t "
            . "LEFT JOIN seo s ON s.id=t.seo_id "
            . "WHERE t.publish=1 AND t.active =1 AND t.deleted IS NULL";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();

        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }
        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, t.modified FROM occasion t "
                . "LEFT JOIN seo s ON s.id=t.seo_id "
                . "LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                . "LEFT JOIN `language` l ON l.id=st.language_id "
                . "WHERE l.locale=:locale AND t.publish=1 AND t.active =1 AND t.deleted IS NULL";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }
    private function generateBrands()
    {
        $routeName = "fe_product_filter_brand";

        $sql = "SELECT s.slug, t.modified FROM brand t "
            . "LEFT JOIN seo s ON s.id=t.seo_id "
            . "WHERE t.publish=1 AND t.active =1 AND t.deleted IS NULL";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();

        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }
        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, t.modified FROM brand t "
                . "LEFT JOIN seo s ON s.id=t.seo_id "
                . "LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                . "LEFT JOIN `language` l ON l.id=st.language_id "
                . "WHERE l.locale=:locale AND t.publish=1 AND t.active =1 AND t.deleted IS NULL";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }

    private function generateBlogs(): void
    {
        $routeName = "fe_blog_show";
        $sql = "SELECT s.slug, s.last_modified FROM blog t "
            . "LEFT JOIN seo s ON s.id=t.seo_id "
            . "WHERE t.publish=1 AND t.deleted IS NULL";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();

        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['last_modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }
        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, s.last_modified FROM blog t "
                . "LEFT JOIN seo s ON s.id=t.seo_id "
                . "LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                . "LEFT JOIN `language` l ON l.id=st.language_id "
                . "WHERE l.locale=:locale AND t.publish=1 AND t.deleted IS NULL";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['last_modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }

    private function generateBlogCategories(): void
    {
        $routeName = "fe_blog_category";

        $sql = "SELECT s.slug, t.modified FROM blog_category t "
            . "LEFT JOIN seo s ON s.id=t.seo_id "
            . "WHERE  t.publish = 1 AND t.deleted IS NULL";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();

        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }

        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, s.last_modified FROM blog_category t "
                . "LEFT JOIN seo s ON s.id=t.seo_id "
                . "LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                . "LEFT JOIN `language` l ON l.id=st.language_id "
                . "WHERE l.locale=:locale AND t.publish=1 AND t.deleted IS NULL";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['last_modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }

    private function generateBlogTags(): void
    {
        $routeName = "fe_blog_tag";

        $sql = "SELECT s.slug, t.modified FROM blog_tag t "
            . "LEFT JOIN seo s ON s.id=t.seo_id "
            . "WHERE t.deleted IS NULL";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();

        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }

        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, s.last_modified FROM blog_tag t "
                . "LEFT JOIN seo s ON s.id=t.seo_id "
                . "LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                . "LEFT JOIN `language` l ON l.id=st.language_id "
                . "WHERE l.locale=:locale AND t.deleted IS NULL";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['last_modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }

    private function generateProjects(): void
    {
        $routeName = "fe_project_show";

        $sql = "SELECT s.slug, s.last_modified FROM project t "
            . "LEFT JOIN seo s ON s.id=t.seo_id "
            . "WHERE t.publish=1 AND t.deleted IS NULL";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();

        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['last_modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }
        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, s.last_modified FROM project t "
                . "LEFT JOIN seo s ON s.id=t.seo_id "
                . "LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                . "LEFT JOIN `language` l ON l.id=st.language_id "
                . "WHERE l.locale=:locale AND t.publish=1 AND t.deleted IS NULL";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['last_modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }

    private function generateCategories(): void
    {
        $routeName = "fe_category_index";

        $sql = "SELECT s.slug, c.modified FROM category c "
            ."LEFT JOIN seo s ON s.id=c.seo_id "
            ."WHERE c.publish=1 AND c.deleted IS NULL";
        $statement = $this->em->getConnection()->prepare($sql);
        $seos = $statement->executeQuery()->fetchAllAssociative();

        foreach ($seos as $seo) {
            $slug = $seo['slug'];
            $lastModified = new \DateTime($seo['modified']);
            $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                ["slug" => $slug]), $lastModified);
        }
        $locales = $this->getLocales();
        foreach ($locales as $locale) {
            $sqlTrans = "SELECT st.slug, c.modified FROM category c "
                ."LEFT JOIN seo s ON s.id=c.seo_id "
                ."LEFT JOIN seo_translations st ON s.id=st.translatable_id "
                ."LEFT JOIN `language` l ON l.id=st.language_id "
                ."WHERE l.locale=:locale AND c.publish=1 AND c.deleted IS NULL";
            $statementTrans = $this->em->getConnection()->prepare($sqlTrans);
            $statementTrans->bindValue("locale", $locale);
            $seosTrans = $statementTrans->executeQuery()->fetchAllAssociative();
            foreach ($seosTrans as $seo) {
                $slug = $seo['slug'];
                $lastModified = new \DateTime($seo['modified']);
                $this->sitemapUrlsService->addPrepareURLsForSitemap($this->sitemapUrlsService->generateUrl($routeName,
                    ["slug" => $slug, "_locale" => $locale]), $lastModified);
            }
        }
    }

    private function getLocales(): array
    {
        $locales = [];
        if ($this->containerParameter->has("app.locales")) {
            $locales = explode("|", $this->containerParameter->get("app.locales"));
        } elseif ($this->containerParameter->has("locale")) {
            $locales[] = $this->containerParameter->get("locale");
        } elseif ($this->containerParameter->has("default_locale")) {
            $locales[] = $this->containerParameter->get("default_locale");
        } else {
            $locales[] = ["en"];
        }

        if (($key = array_search("en", $locales)) !== false) {
            unset($locales[$key]);
        }

        return array_filter($locales, function ($var) {
            return ($var !== null && $var !== false && $var !== "");
        });
    }
}
