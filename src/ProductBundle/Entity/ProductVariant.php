<?php

namespace App\ProductBundle\Entity;

use App\ProductBundle\Entity\Translation\ProductVariantTranslation;
use App\ProductBundle\Enum\ProductVariantTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="product_variant")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductVariantRepository")
 */
class ProductVariant implements Translatable, DateTimeInterface
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
     * @ORM\ManyToOne(targetEntity="\App\ProductBundle\Entity\Product", fetch="EAGER")
     */
    private ?Product $product = null;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="title", type="string", length=255)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="type", enumType=ProductVariantTypeEnum::class, type="string", length=255)
     */
    private ?ProductVariantTypeEnum $type = ProductVariantTypeEnum::TEXT;

    /**
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private ?int $tarteb = null;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\ProductVariantOption", mappedBy="variant", cascade={"remove", "persist"})
     */
    private Collection $options;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\Translation\ProductVariantTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->options = new ArrayCollection();
    }

    public function getObj(): array
    {
        return [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
            "type" => $this->getType()?->value,
            "options" => [],
        ];
    }

    public function getTitle(): ?string
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();
    }

    public function getOptions(): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('deleted', null));

        return $this->options->matching($criteria);
    }

    public function getTypeName(): ?string
    {
        return $this->getType()?->name();
    }

    public function getType(): ?ProductVariantTypeEnum
    {
        return $this->type;
    }

    public function setType(?ProductVariantTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getTarteb(): ?int
    {
        return $this->tarteb;
    }

    public function setTarteb(?int $tarteb): self
    {
        $this->tarteb = $tarteb;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function addOption(ProductVariantOption $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setVariant($this);
        }

        return $this;
    }

    public function removeOption(ProductVariantOption $option): self
    {
        if ($this->options->removeElement($option)) {
            // set the owning side to null (unless already changed)
            if ($option->getVariant() === $this) {
                $option->setVariant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductVariantTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ProductVariantTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(ProductVariantTranslation $translation): self
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
