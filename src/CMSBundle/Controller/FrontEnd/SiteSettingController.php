<?php

namespace App\CMSBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\SiteSetting;
use App\CMSBundle\Service\SiteSettingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * SiteSetting controller.
 *
 * @Route("/site-setting")
 */
class SiteSettingController extends AbstractController
{
    private array $ignoredUserAgents = [
        "Lighthouse",
        "GTmetrix",
    ];

    /**
     * Site Settings Head widget
     */
    public function siteSettingHead(Request $request, SiteSettingService $settingService): Response
    {
        $data = $settingService->getData();
        if ($this->isRequestFromAnalyticalTool($request)) {
            $data[SiteSetting::WEBSITE_HEAD_TAGS] = null;
            $data[SiteSetting::GOOGLE_TAG_MANAGER_ID] = null;
            $data[SiteSetting::FACEBOOK_CHAT_PAGE_ID] = null;
            $data[SiteSetting::FACEBOOK_PIXEL_ID] = null;
        }

        return $this->render('cms/frontEnd/siteSetting/siteSettingHead.html.twig', [
            "data" => $data
        ]);
    }

    /**
     * Site Settings Body widget
     */
    public function siteSettingBody(Request $request, SiteSettingService $settingService): Response
    {
        $data = $settingService->getData();
        if ($this->isRequestFromAnalyticalTool($request)) {
            $data[SiteSetting::WEBSITE_HEAD_TAGS] = null;
            $data[SiteSetting::GOOGLE_TAG_MANAGER_ID] = null;
            $data[SiteSetting::FACEBOOK_CHAT_PAGE_ID] = null;
            $data[SiteSetting::FACEBOOK_PIXEL_ID] = null;
        }

        return $this->render('cms/frontEnd/siteSetting/siteSettingBody.html.twig', [
            "data" => $data
        ]);
    }

    private function isRequestFromAnalyticalTool(Request $request): bool
    {
        $userAgent = $request->headers->get("User-Agent");
        if (!$userAgent) {
            return true;
        }
        foreach ($this->ignoredUserAgents as $ignoredUserAgent) {
            if (str_contains($userAgent, $ignoredUserAgent)) {
                return true;
            }
        }

        return false;
    }
}
