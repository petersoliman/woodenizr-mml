<?php

namespace App\CMSBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\CMSBundle\Entity\SiteSetting;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SiteSetting|null find($id, $lockMode = null, $lockVersion = null)
 * @method SiteSetting|null findOneBy(array $criteria, array $orderBy = null)
 * @method SiteSetting[]    findAll()
 * @method SiteSetting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SiteSettingRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteSetting::class, "ss");
    }


}
