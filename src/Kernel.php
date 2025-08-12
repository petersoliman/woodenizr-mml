<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    const globalAssetVersion = '0.0.24';

    public function boot(): void
    {
        parent::boot();
        date_default_timezone_set('Africa/Cairo');
    }
}
