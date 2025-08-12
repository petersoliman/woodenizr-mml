<?php

namespace App\CMSBundle\Entity;

use App\CMSBundle\Entity\Translation\BlogTagTranslation;
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
 * BlogTag
 *
 * @ORM\Table(name="blog_tag")
 * @ORM\Entity(repositoryClass="App\CMSBundle\Repository\BlogTagRepository")
 */
class BlogTag implements Translatable, DateTimeInterface
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
     * @ORM\OneToOne(targetEntity="\App\SeoBundle\Entity\Seo", cascade={"persist", "remove" })
     */
    private ?Seo $seo = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=255)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private ?int $tarteb = null;

    /**
     * @ORM\OneToMany(targetEntity="App\CMSBundle\Entity\Translation\BlogTagTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true, fetch="LAZY")
     */
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getObj(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
        ];
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
     * @return Collection|BlogTagTranslation[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(BlogTagTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(BlogTagTranslation $translation): self
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
