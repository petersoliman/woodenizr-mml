<?php

namespace App\SeoBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\SeoBundle\Entity\Translation\SeoTranslation as BaseSeoTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(name="seo_translations")
 */
class SeoTranslation extends BaseSeoTranslation {

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\SeoBundle\Entity\Seo", inversedBy="translations")
     * @ORM\JoinColumn(name="translatable_id", referencedColumnName="id")
     */
    protected $translatable;

}
