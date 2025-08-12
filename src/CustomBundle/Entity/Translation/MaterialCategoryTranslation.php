<?php

namespace App\CustomBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\Language;
use PN\LocaleBundle\Model\TranslationEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="material_category_translations")
 */
class MaterialCategoryTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private ?string $title = null;

    /**
     * @var
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CustomBundle\Entity\MaterialCategory", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected Language $language;

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

}
