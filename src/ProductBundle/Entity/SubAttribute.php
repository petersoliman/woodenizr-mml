<?php

namespace App\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\ProductBundle\Entity\Translation\SubAttributeTranslation;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use PN\LocaleBundle\Model\Translatable;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="product_sub_attribute")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\SubAttributeRepository")
 */
class SubAttribute implements Translatable,DateTimeInterface
{

    use VirtualDeleteTrait,
        DateTimeTrait,
        LocaleTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Attribute", inversedBy="subAttributes")
     */
    private $attribute;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=100)
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="ProductHasAttribute", mappedBy="subAttribute", cascade={"persist"})
     */
    private $productHasAttributes;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\Translation\SubAttributeTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private $translations;

    public function __construct()
    {
        $this->productHasAttributes = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    public function setAttribute(?Attribute $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return Collection|ProductHasAttribute[]
     */
    public function getProductHasAttributes(): Collection
    {
        return $this->productHasAttributes;
    }

    public function addProductHasAttribute(ProductHasAttribute $productHasAttribute): self
    {
        if (!$this->productHasAttributes->contains($productHasAttribute)) {
            $this->productHasAttributes[] = $productHasAttribute;
            $productHasAttribute->setSubAttribute($this);
        }

        return $this;
    }

    public function removeProductHasAttribute(ProductHasAttribute $productHasAttribute): self
    {
        if ($this->productHasAttributes->removeElement($productHasAttribute)) {
            // set the owning side to null (unless already changed)
            if ($productHasAttribute->getSubAttribute() === $this) {
                $productHasAttribute->setSubAttribute(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SubAttributeTranslation[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(SubAttributeTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(SubAttributeTranslation $translation): self
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
