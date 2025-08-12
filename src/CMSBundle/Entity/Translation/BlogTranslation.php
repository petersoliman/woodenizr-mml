<?php

namespace App\CMSBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\Language;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="blog_translations")
 */
class BlogTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="subtitle", type="string", length=255, nullable=true)
     */
    private ?string $subtitle = null;

    /**
     * @var
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CMSBundle\Entity\Blog", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected Language $language;

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getSubtitle()
    {
        return $this->subtitle;
    }

}
