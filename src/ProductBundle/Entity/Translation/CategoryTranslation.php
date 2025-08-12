<?php

namespace App\ProductBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

;

/**
 * @ORM\Entity
 * @ORM\Table(name="category_translations")
 */
class CategoryTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="title", type="string", length=100)
     */
    protected $title;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Category", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

}
