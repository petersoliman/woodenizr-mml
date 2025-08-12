<?php

namespace App\CMSBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

;

/**
 * @ORM\Entity
 * @ORM\Table(name="dynamic_page_translations")
 */
class DynamicPageTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private ?string $title = null;

    /**
     * @var
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CMSBundle\Entity\DynamicPage", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }
}
