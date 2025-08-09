<?php

namespace App\CustomBundle\Entity;

use App\CustomBundle\Entity\Translation\MaterialCategoryTranslation;
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
 * @ORM\Table(name="material_category")
 * @ORM\Entity(repositoryClass="App\CustomBundle\Repository\MaterialCategoryRepository")
 */
class MaterialCategory implements Translatable, DateTimeInterface
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
     * @Assert\NotNull
     * @ORM\OneToMany(targetEntity="App\CustomBundle\Entity\Material", mappedBy="category", cascade={"persist"})
     */
    private Collection $materials;

    /**
     * @ORM\OneToMany(targetEntity="App\CustomBundle\Entity\Translation\MaterialCategoryTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true, fetch="LAZY")
     */
    private Collection $translations;

    public function __construct()
    {
        $this->materials = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();
    }

    /**
     * @return Collection|Material[]
     */
    public function getMaterials(?bool $publish = null): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('deleted', null));
        if ($publish !== null) {
            $criteria->where(Criteria::expr()->eq('publish', $publish));
        }
        return $this->materials->matching($criteria);
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

    public function addMaterial(Material $material): self
    {
        if (!$this->materials->contains($material)) {
            $this->materials->add($material);
            $material->setCategory($this);
        }

        return $this;
    }

    public function removeMaterial(Material $material): self
    {
        if ($this->materials->removeElement($material)) {
            // set the owning side to null (unless already changed)
            if ($material->getCategory() === $this) {
                $material->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MaterialCategoryTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(MaterialCategoryTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(MaterialCategoryTranslation $translation): self
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
