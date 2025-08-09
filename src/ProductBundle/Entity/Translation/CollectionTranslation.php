<?php

namespace App\ProductBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use App\ProductBundle\Entity\Collection;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\TranslationEntity;
use PN\LocaleBundle\Model\EditableTranslation;;

/**
 * @ORM\Entity
 * @ORM\Table(name="collection_translations")
 */
class CollectionTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="title", type="string", length=100)
     */
    private $title;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Collection", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    public function setTitle(?string $title):static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle():?string
    {
        return $this->title;
    }

}
