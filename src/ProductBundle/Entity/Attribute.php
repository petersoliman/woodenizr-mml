<?php

namespace App\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use App\ProductBundle\Entity\Translation\AttributeTranslation;
use App\ServiceBundle\Entity\Locale;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use PN\LocaleBundle\Model\Translatable;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="product_attribute")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\AttributeRepository")
 */
class Attribute implements Translatable, DateTimeInterface
{

    use VirtualDeleteTrait,
        DateTimeTrait,
        LocaleTrait;

    const TYPE_NUMBER = "number";
    const TYPE_TEXT = "text";
    const TYPE_DROPDOWN = "dropdown";
    const TYPE_CHECKBOX = "checkbox";

    public static $types = [
        'Number' => self::TYPE_NUMBER,
        'Text' => self::TYPE_TEXT,
        'Single Choice' => self::TYPE_DROPDOWN,
        'Multiple Choices' => self::TYPE_CHECKBOX,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="attributes",cascade={"persist"})
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=100)
     */
    private $title;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="string", length=45)
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private $tarteb;

    /**
     * @ORM\Column(name="search", type="boolean")
     */
    private $search = false;

    /**
     * @ORM\Column(name="mandatory", type="boolean")
     */
    private $mandatory = false;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\Translation\AttributeTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private $translations;

    /**
     * @ORM\OneToMany(targetEntity="SubAttribute", mappedBy="attribute", cascade={"persist"})
     */
    private $subAttributes;

    /**
     * @ORM\OneToMany(targetEntity="ProductHasAttribute", mappedBy="attribute", cascade={"persist"})
     */
    private $productHasAttributes;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->subAttributes = new ArrayCollection();
        $this->productHasAttributes = new ArrayCollection();
    }

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     */
    public function updatedTimestamps()
    {
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    public function getTypeName()
    {
        return array_search($this->getType(), self::$types);
    }


    /**
     * @return Collection|SubAttribute[]
     */
    public function getSubAttributes($deleted=null): Collection
    {
        $criteria = Criteria::create();
        if ($deleted == null) {
            $criteria->where(Criteria::expr()->eq('deleted', null));
        }

        return $this->subAttributes->matching($criteria);
    }

    public function getObj()
    {

        return [
            "id" => (int)$this->getId(),
            "title" => (string)$this->getTitle(),
        ];
    }

    public function __toString()
    {
        return $this->getTitle();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getSearch(): ?bool
    {
        return $this->search;
    }

    public function setSearch(bool $search): self
    {
        $this->search = $search;

        return $this;
    }

    public function getMandatory(): ?bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): self
    {
        $this->mandatory = $mandatory;

        return $this;
    }


    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection|AttributeTranslation[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(AttributeTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(AttributeTranslation $translation): self
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

        return $this;
    }


    public function addSubAttribute(SubAttribute $subAttribute): self
    {
        if (!$this->subAttributes->contains($subAttribute)) {
            $this->subAttributes[] = $subAttribute;
            $subAttribute->setAttribute($this);
        }

        return $this;
    }

    public function removeSubAttribute(SubAttribute $subAttribute): self
    {
        if ($this->subAttributes->removeElement($subAttribute)) {
            // set the owning side to null (unless already changed)
            if ($subAttribute->getAttribute() === $this) {
                $subAttribute->setAttribute(null);
            }
        }

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
            $productHasAttribute->setAttribute($this);
        }

        return $this;
    }

    public function removeProductHasAttribute(ProductHasAttribute $productHasAttribute): self
    {
        if ($this->productHasAttributes->removeElement($productHasAttribute)) {
            // set the owning side to null (unless already changed)
            if ($productHasAttribute->getAttribute() === $this) {
                $productHasAttribute->setAttribute(null);
            }
        }

        return $this;
    }

}
