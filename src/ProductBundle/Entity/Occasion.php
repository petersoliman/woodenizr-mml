<?php

namespace App\ProductBundle\Entity;

use App\ContentBundle\Entity\Post;
use App\ProductBundle\Entity\Translation\OccasionTranslation;
use App\SeoBundle\Entity\Seo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("occasion")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\OccasionRepository")
 */
class Occasion implements Translatable, DateTimeInterface
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
     * @ORM\OneToOne(targetEntity="\App\SeoBundle\Entity\Seo", cascade={"persist", "remove"})
     */
    private ?Seo $seo = null;

    /**
     * @ORM\OneToOne(targetEntity="\App\ContentBundle\Entity\Post", cascade={"persist", "remove"})
     */
    private ?Post $post = null;

    /**
     * @ORM\Column(name="title", type="string", length=45)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="active", type="boolean")
     */
    private bool $active = false;

    /**
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;

    /**
     * @ORM\Column(name="no_of_products", type="smallint", nullable=true, options={"default" : 0})
     */
    private int $noOfProducts = 0;

    /**
     * @ORM\Column(name="no_of_publish_products", type="smallint", nullable=true, options={"default" : 0})
     */
    private int $noOfPublishProducts = 0;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\Translation\OccasionTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\ProductHasOccasion", mappedBy="occasion", cascade={"persist"})
     */
    private Collection $productHasOccasions;

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->active = false;
            $this->seo = clone $this->seo;
            $this->post = clone $this->post;
            $translationsClone = new ArrayCollection();
            foreach ($this->getTranslations() as $translation) {
                $itemClone = clone $translation;
                $itemClone->setTranslatable($this);
                $translationsClone->add($itemClone);
            }
            $this->translations = $translationsClone;
        }
    }

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->productHasOccasions = new ArrayCollection();
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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

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

    public function getSeo(): ?Seo
    {
        return $this->seo;
    }

    public function setSeo(?Seo $seo): self
    {
        $this->seo = $seo;

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

    /**
     * @return Collection<int, OccasionTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(OccasionTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(OccasionTranslation $translation): self
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductHasOccasion>
     */
    public function getProductHasOccasions(): Collection
    {
        return $this->productHasOccasions;
    }

    public function addProductHasOccasion(ProductHasOccasion $productHasOccasion): self
    {
        if (!$this->productHasOccasions->contains($productHasOccasion)) {
            $this->productHasOccasions->add($productHasOccasion);
            $productHasOccasion->setOccasion($this);
        }

        return $this;
    }

    public function removeProductHasOccasion(ProductHasOccasion $productHasOccasion): self
    {
        if ($this->productHasOccasions->removeElement($productHasOccasion)) {
            // set the owning side to null (unless already changed)
            if ($productHasOccasion->getOccasion() === $this) {
                $productHasOccasion->setOccasion(null);
            }
        }

        return $this;
    }

}
