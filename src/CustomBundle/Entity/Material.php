<?php

namespace App\CustomBundle\Entity;

use App\ContentBundle\Entity\Post;
use App\CustomBundle\Entity\Translation\MaterialTranslation;
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
 * @ORM\Table(name="material")
 * @ORM\Entity(repositoryClass="App\CustomBundle\Repository\MaterialRepository")
 */
class Material implements Translatable, DateTimeInterface
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
     * @ORM\ManyToOne(targetEntity="App\CustomBundle\Entity\MaterialCategory", inversedBy="materials", cascade={"remove"})
     */
    private ?MaterialCategory $category = null;
    /**
     * Description
     * Brief
     * Gallery
     *
     * @ORM\OneToOne(targetEntity="App\ContentBundle\Entity\Post", cascade={"persist", "remove" })
     */
    private ?Post $post = null;

    /**
     * @Assert\NotBlank
     * @ORM\Column(name="title", type="string", length=255)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private ?int $tarteb = null;

    /**
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;

    /**
     * @ORM\OneToMany(targetEntity="App\CustomBundle\Entity\Translation\MaterialTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true, fetch="LAZY")
     */
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
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

    public function isPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    public function getCategory(): ?MaterialCategory
    {
        return $this->category;
    }

    public function setCategory(?MaterialCategory $category): self
    {
        $this->category = $category;

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
     * @return Collection<int, MaterialTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(MaterialTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(MaterialTranslation $translation): self
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
