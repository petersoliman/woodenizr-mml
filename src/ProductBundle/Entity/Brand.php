<?php

namespace App\ProductBundle\Entity;

use App\ContentBundle\Entity\Post;
use App\ProductBundle\Entity\Translation\BrandTranslation;
use App\SeoBundle\Entity\Seo;
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
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("brand")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\BrandRepository")
 */
class Brand implements Translatable, DateTimeInterface
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
     * @ORM\Column(name="jp_id", type="string", length=45, nullable=true)
     */
    private ?string $jpId = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=45)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;

    /**
     * @ORM\Column(name="featured", type="boolean")
     */
    private bool $featured = true;

    /**
     * @ORM\Column(name="tarteb", type="smallint", nullable=true, options={"default":0}))
     */
    private ?int $tarteb = 0;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\Translation\BrandTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    public function getTitle(): ?string
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJpId(): ?string
    {
        return $this->jpId;
    }

    public function setJpId(string $jpId): static
    {
        $this->jpId = $jpId;

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function isPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): static
    {
        $this->publish = $publish;

        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function getTarteb(): ?int
    {
        return $this->tarteb;
    }

    public function setTarteb(?int $tarteb): static
    {
        $this->tarteb = $tarteb;

        return $this;
    }

    public function getSeo(): ?Seo
    {
        return $this->seo;
    }

    public function setSeo(?Seo $seo): static
    {
        $this->seo = $seo;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }

    /**
     * @return Collection<int, BrandTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(BrandTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(BrandTranslation $translation): static
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
