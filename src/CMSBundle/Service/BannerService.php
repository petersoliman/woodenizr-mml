<?php

namespace App\CMSBundle\Service;

use App\CMSBundle\Entity\Banner;
use App\CMSBundle\Enum\BannerPlacementEnum;
use App\CMSBundle\Repository\BannerRepository;
use Doctrine\ORM\EntityManagerInterface;

class BannerService
{

    private BannerRepository $bannerRepository;

    public function __construct(EntityManagerInterface $em, BannerRepository $bannerRepository)
    {
        $this->bannerRepository = $bannerRepository;
    }

    public function getBanners(BannerPlacementEnum $placement, $limit = 3): array
    {
        return $this->bannerRepository->getRandBanner($placement, $limit);
    }

    public function getOneBanner(BannerPlacementEnum $placement): ?Banner
    {
        $banners = $this->bannerRepository->getRandBanner($placement, 1);
        if (count($banners) > 0) {
            return $banners[0];
        }

        return null;
    }
}
