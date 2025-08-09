<?php

namespace App\SeoBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use PN\SeoBundle\Entity\Seo as BaseSeo;
use PN\SeoBundle\Model\SeoTrait;

/**
 * Seo
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("seo", uniqueConstraints={@UniqueConstraint(name="slug_unique", columns={"slug", "seo_base_route_id", "deleted"})})
 * @ORM\Entity(repositoryClass="App\SeoBundle\Repository\SeoRepository")
 */
class Seo extends BaseSeo
{

    use SeoTrait;

    /**
     * @ORM\OneToMany(targetEntity="App\SeoBundle\Entity\Translation\SeoTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true, fetch="LAZY")
     */
    protected Collection $translations;


}
