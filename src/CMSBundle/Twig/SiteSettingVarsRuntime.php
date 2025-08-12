<?php

namespace App\CMSBundle\Twig;

use App\CMSBundle\Service\SiteSettingService;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class SiteSettingVarsRuntime implements RuntimeExtensionInterface
{

    private SiteSettingService $siteSettingService;

    public function __construct(
        SiteSettingService $siteSettingService
    )
    {
        $this->siteSettingService = $siteSettingService;
    }

    public function getSiteSetting(string $constantName): ?string
    {
        return $this->siteSettingService->getByConstantName($constantName);
    }

}
