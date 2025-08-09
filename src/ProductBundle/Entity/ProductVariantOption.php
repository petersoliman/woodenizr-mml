<?php

namespace App\ProductBundle\Entity;

use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Translation\ProductVariantOptionTranslation;
use App\ProductBundle\Enum\ProductVariantTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="product_variant_option")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductVariantOptionRepository")
 */
class ProductVariantOption implements Translatable, DateTimeInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait,
        LocaleTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="\App\ProductBundle\Entity\ProductVariant", inversedBy="options")
     */
    private ?ProductVariant $variant = null;

    /**
     * @ORM\OneToOne(targetEntity="App\MediaBundle\Entity\Image", cascade={"persist", "remove" })
     */
    private ?Image $image = null;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="title", type="string", length=255)
     */
    private ?string $title = null;

    /**
     * ColorCode
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private ?string $value = null;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\Translation\ProductVariantOptionTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getObj(): array
    {
        $value = $this->getVariant()->getType() == ProductVariantTypeEnum::IMAGE ? $this->getImage()->getAssetPath() : $this->getValue();
        return [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
            "value" => $value,
        ];
    }

    public function getTitle(): ?string
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getVariant(): ?ProductVariant
    {
        return $this->variant;
    }

    public function setVariant(?ProductVariant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, ProductVariantOptionTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ProductVariantOptionTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(ProductVariantOptionTranslation $translation): self
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

        return $this;
    }


}
