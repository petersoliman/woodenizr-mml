<?php

namespace App\ProductBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_variant_translations")
 */
class ProductVariantTranslation extends TranslationEntity implements EditableTranslation
{
    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected ?string $title = null;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\ProductVariant", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    public function getId(): ?int
    {
        return $this->translatable->getId();
    }

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
