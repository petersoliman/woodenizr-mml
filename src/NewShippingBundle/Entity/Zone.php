<?php

namespace App\NewShippingBundle\Entity;

use App\NewShippingBundle\Entity\Translation\ZoneTranslation;
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
 * @ORM\Table("zone")
 * @ORM\Entity(repositoryClass="App\NewShippingBundle\Repository\ZoneRepository")
 */
class Zone implements Translatable, DateTimeInterface
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
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=50)
     */
    private ?string $title = null;


    /**
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private ?int $tarteb = null;


    /**
     * @ORM\ManyToMany(targetEntity="ShippingZone", mappedBy="zones")
     */
    private Collection $shippingZones;

    /**
     * @ORM\OneToMany(targetEntity="App\NewShippingBundle\Entity\Translation\ZoneTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    public function __construct()
    {
        $this->shippingZones = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getObj(): array
    {
        return [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
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

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    /**
     * @return Collection<int, ShippingZone>
     */
    public function getShippingZones(): Collection
    {
        return $this->shippingZones;
    }

    public function addShippingZone(ShippingZone $shippingZone): static
    {
        if (!$this->shippingZones->contains($shippingZone)) {
            $this->shippingZones->add($shippingZone);
            $shippingZone->addZone($this);
        }

        return $this;
    }

    public function removeShippingZone(ShippingZone $shippingZone): static
    {
        if ($this->shippingZones->removeElement($shippingZone)) {
            $shippingZone->removeZone($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ZoneTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ZoneTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(ZoneTranslation $translation): static
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
