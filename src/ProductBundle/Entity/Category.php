<?php

namespace App\ProductBundle\Entity;

use App\ContentBundle\Entity\Post;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Translation\CategoryTranslation;
use App\SeoBundle\Entity\Seo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="category")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\CategoryRepository")
 */
class Category implements Translatable, DateTimeInterface
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
     * @ORM\OneToOne(targetEntity="App\MediaBundle\Entity\Image", cascade={"persist", "remove" })
     */
    private ?Image $image = null;

    /**
     * @ORM\OneToOne(targetEntity="App\ContentBundle\Entity\Post", cascade={"persist", "remove" })
     */
    private ?Post $post = null;

    /**
     * @ORM\OneToOne(targetEntity="\App\SeoBundle\Entity\Seo", cascade={"persist", "remove" })
     */
    private ?Seo $seo = null;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     */
    private ?Category $parent = null;


    /**
     * @ORM\ManyToOne(targetEntity="Category")
     */
    private ?Category $levelOne = null;

    /**
     * @ORM\Column(name="title", type="string", length=100)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="concatIds", type="text", nullable=true)
     */
    private ?string $concatIds = null;

    /**
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private ?int $tarteb = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $depth;

    /**
     * @ORM\Column(name="no_of_images", type="smallint", nullable=true, options={"default" : 0})
     */
    private int $noOfImages = 0;

    /**
     * @ORM\Column(name="no_of_products", type="smallint", nullable=true, options={"default" : 0})
     */
    private int $noOfProducts = 0;

    /**
     * @ORM\Column(name="no_of_publish_products", type="smallint", nullable=true, options={"default" : 0})
     */
    private int $noOfPublishProducts = 0;

    /**
     * @ORM\Column(name="parentConcatIds", type="text", nullable=true)
     */
    private ?string $parentConcatIds = null;

    /**
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;

    /**
     * @ORM\Column(name="featured", type="boolean")
     */
    private bool $featured = false;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\Translation\CategoryTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true, fetch="EAGER")
     */
    private Collection $translations;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private Collection $children;

    /**
     * @ORM\OneToMany(targetEntity="Attribute", mappedBy="category")
     */
    private Collection $attributes;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->attributes = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    public function hasChildren(): bool
    {
        $children = $this->getChildren();
        if (count($children) > 0) {
            return true;
        }

        return false;
    }

    public function getChildren($deleted = null)
    {
        $criteria = Criteria::create();
        if ($deleted == null) {
            $criteria->where(Criteria::expr()->eq('deleted', null));
        }

        return $this->children->matching($criteria);
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

    public function getConcatIds(): ?string
    {
        return $this->concatIds;
    }

    public function setConcatIds(?string $concatIds): self
    {
        $this->concatIds = $concatIds;

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

    public function getNoOfImages(): ?int
    {
        return $this->noOfImages;
    }

    public function setNoOfImages(?int $noOfImages): self
    {
        $this->noOfImages = $noOfImages;

        return $this;
    }

    public function getNoOfProducts(): ?int
    {
        return $this->noOfProducts;
    }

    public function setNoOfProducts(?int $noOfProducts): self
    {
        $this->noOfProducts = $noOfProducts;

        return $this;
    }

    public function getNoOfPublishProducts(): ?int
    {
        return $this->noOfPublishProducts;
    }

    public function setNoOfPublishProducts(?int $noOfPublishProducts): self
    {
        $this->noOfPublishProducts = $noOfPublishProducts;

        return $this;
    }

    public function getParentConcatIds(): ?string
    {
        return $this->parentConcatIds;
    }

    public function setParentConcatIds(?string $parentConcatIds): self
    {
        $this->parentConcatIds = $parentConcatIds;

        return $this;
    }

    public function getSeo(): ?Seo
    {
        return $this->seo;
    }

    public function setSeo(?Seo $seo): self
    {
        $this->seo = $seo;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getLevelOne(): ?self
    {
        return $this->levelOne;
    }

    public function setLevelOne(?self $levelOne): self
    {
        $this->levelOne = $levelOne;

        return $this;
    }

    public function addChild(Category $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Category $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection|CategoryTranslation[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(CategoryTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(CategoryTranslation $translation): self
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

        return $this;
    }

    public function getDepth(): ?int
    {
        return $this->depth;
    }

    public function setDepth(int $depth): self
    {
        $this->depth = $depth;

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
     * @return Collection|Attribute[]
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function addAttribute(Attribute $attribute): self
    {
        if (!$this->attributes->contains($attribute)) {
            $this->attributes[] = $attribute;
            $attribute->setCategory($this);
        }

        return $this;
    }

    public function removeAttribute(Attribute $attribute): self
    {
        if ($this->attributes->removeElement($attribute)) {
            // set the owning side to null (unless already changed)
            if ($attribute->getCategory() === $this) {
                $attribute->setCategory(null);
            }
        }

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function isPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }
    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): self
    {
        $this->featured = $featured;

        return $this;
    }

}
