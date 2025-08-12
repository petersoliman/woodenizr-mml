<?php

namespace App\CMSBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class SiteSettingVarsExtension extends AbstractExtension
{

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getSiteSetting', [SiteSettingVarsRuntime::class, 'getSiteSetting']),
        ];
    }

    public function getName(): string
    {
        return 'siteSetting.extension';
    }

}
