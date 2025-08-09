<?php

namespace App\CurrencyBundle\Entity;

use App\CurrencyBundle\Entity\Translation\CurrencyTranslation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("currency")
 * @ORM\Entity(repositoryClass="App\CurrencyBundle\Repository\CurrencyRepository")
 * @UniqueEntity("code")
 */
class Currency implements DateTimeInterface, Translatable
{
    use VirtualDeleteTrait,
        DateTimeTrait,
        LocaleTrait;


    const EGP = 1;
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=50)
     */
    private ?string $title;

    /**
     * @Assert\Currency()
     * @Assert\NotBlank()
     * @ORM\Column(name="code", type="string", length=3, unique=true)
     */
    private ?string $code;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="symbol", type="string", length=255)
     */
    private ?string $symbol;

    /**
     * @ORM\Column(name="`default`", type="boolean")
     */
    private bool $default = false;

    /**
     * @ORM\OneToMany(targetEntity="App\CurrencyBundle\Entity\Translation\CurrencyTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();

    }

    public function getSymbol(): ?string
    {
        return !$this->currentTranslation ? $this->symbol : $this->currentTranslation->getSymbol();

    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }


    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return Collection|CurrencyTranslation[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(CurrencyTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(CurrencyTranslation $translation): self
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
