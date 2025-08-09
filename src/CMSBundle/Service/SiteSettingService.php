<?php

namespace App\CMSBundle\Service;

use App\CMSBundle\Repository\SiteSettingRepository;
use PN\ServiceBundle\Service\ContainerParameterService;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SiteSettingService
{
    const CACHE_NAME = "site.setting.config";

    private string $environment;

    private SiteSettingRepository $siteSettingRepository;
    private ContainerParameterService $containerParameterService;

    public function __construct(KernelInterface $kernel, SiteSettingRepository $siteSettingRepository, ContainerParameterService $containerParameterService)
    {
        $this->environment = $kernel->getEnvironment();
        $this->siteSettingRepository = $siteSettingRepository;
        $this->containerParameterService = $containerParameterService;
    }

    public function getData()
    {
        $cache = new FilesystemAdapter(directory: $this->containerParameterService->get("kernel.cache_dir") . "/filesystemAdapter-cache");
        $siteSettingRepository = $this->siteSettingRepository;

        return $cache->get(self::CACHE_NAME, function (ItemInterface $item) use ($siteSettingRepository) {
            $item->expiresAfter(86400);// expire after 24 hrs
            $siteSettings = $siteSettingRepository->findAll();

            $data = [];
            foreach ($siteSettings as $setting) {
                $data[$setting->getConstantName()] = $setting->getValue();
            }

            return $data;
        });
    }

    public function getByConstantName($constantName): null|string|bool
    {
        $data = $this->getData();
        if (array_key_exists($constantName, $data)) {
            return $data[$constantName];
        }
        return null;
    }

    public function removeCache(): void
    {
        $cache = new FilesystemAdapter(directory: $this->containerParameterService->get("kernel.cache_dir") . "/filesystemAdapter-cache");
        $cache->delete(self::CACHE_NAME);
    }
}