<?php

namespace App\ThreeSixtyViewBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ThreeSixtyViewBundle\Entity\ThreeSixtyView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ThreeSixtyView>
 *
 * @method ThreeSixtyView|null find($id, $lockMode = null, $lockVersion = null)
 * @method ThreeSixtyView|null findOneBy(array $criteria, array $orderBy = null)
 * @method ThreeSixtyView[]    findAll()
 * @method ThreeSixtyView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThreeSixtyViewRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ThreeSixtyView::class,"tsv");
    }


}
