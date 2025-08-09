<?php

namespace App\CMSBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\Language;
use PN\LocaleBundle\Model\TranslationEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="blog_tag_translations")
 */
class BlogTagTranslation extends TranslationEntity implements EditableTranslation {

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected ?string $title = null;

    /**
     * @var 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CMSBundle\Entity\BlogTag", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected Language $language;

    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

}
