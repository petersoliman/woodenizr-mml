<?php

namespace App\CMSBundle\Entity;

use App\CMSBundle\Entity\Translation\ProjectTranslation;
use App\ContentBundle\Entity\Post;
use App\ProductBundle\Entity\Product;
use App\SeoBundle\Entity\Seo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;

/**
 * Project
 *
 * @ORM\Table(name="project")
 * @ORM\Entity(repositoryClass="App\CMSBundle\Repository\ProjectRepository")
 */
class Project implements Translatable, DateTimeInterface
{

    use VirtualDeleteTrait,
        LocaleTrait,
        DateTimeTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     */
    private ?string $title = null;

    /**
     * Description
     * Brief
     * Gallery
     *
     * @ORM\OneToOne(targetEntity="App\ContentBundle\Entity\Post", cascade={"persist", "remove" })
     */
    private ?Post $post = null;

    /**
     * @ORM\OneToOne(targetEntity="App\SeoBundle\Entity\Seo", cascade={"persist", "remove" })
     */
    private ?Seo $seo = null;

    /**
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private ?int $tarteb = null;

    /**
     * @ORM\Column(name="data", type="json")
     */
    private $data = [];

    /**
     * @ORM\Column(name="featured", type="boolean")
     */
    private bool $featured = false;

    /**
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;

    /**
     * @ORM\ManyToMany(targetEntity="App\ProductBundle\Entity\Product")
     */
    private Collection $relatedProducts;

    /**
     * @ORM\OneToMany(targetEntity="App\CMSBundle\Entity\Translation\ProjectTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    #[Pure] public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->relatedProducts = new ArrayCollection();
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

    public function getTarteb(): ?int
    {
        return $this->tarteb;
    }

    public function setTarteb(?int $tarteb): self
    {
        $this->tarteb = $tarteb;

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

    public function isPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

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

    public function getSeo(): ?Seo
    {
        return $this->seo;
    }

    public function setSeo(?Seo $seo): self
    {
        $this->seo = $seo;

        return $this;
    }

    /**
     * @return Collection<int, ProjectTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ProjectTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(ProjectTranslation $translation): self
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
     * @return Collection<int, Product>
     */
    public function getRelatedProducts(): Collection
    {
        return $this->relatedProducts;
    }

    public function addRelatedProduct(Product $relatedProduct): static
    {
        if (!$this->relatedProducts->contains($relatedProduct)) {
            $this->relatedProducts->add($relatedProduct);
        }

        return $this;
    }

    public function removeRelatedProduct(Product $relatedProduct): static
    {
        $this->relatedProducts->removeElement($relatedProduct);

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

}
