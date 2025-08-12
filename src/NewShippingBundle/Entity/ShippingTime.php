<?php

namespace App\NewShippingBundle\Entity;

use App\NewShippingBundle\Entity\Translation\ShippingTimeTranslation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;

/**
 * @ORM\Table("shipping_time")
 * @ORM\Entity(repositoryClass="App\NewShippingBundle\Repository\ShippingTimeRepository")
 */
class ShippingTime implements Translatable
{
    use LocaleTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="name", type="string", length=45)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="number_of_delivery_days", type="integer", nullable=true, options={"default" : 0})
     */
    private int $noOfDeliveryDays = 0;

    /**
     * @ORM\Column(name="deleted", type="boolean")
     */
    private bool $deleted = false;

    /**
     * @ORM\OneToMany(targetEntity="App\NewShippingBundle\Entity\ShippingZonePrice", mappedBy="shippingTime")
     */
    private Collection $shippingZonePrices;

    /**
     * @ORM\OneToMany(targetEntity="App\NewShippingBundle\Entity\Translation\ShippingTimeTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    public function __construct()
    {
        $this->shippingZonePrices = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }


    function __toString()
    {
        return $this->getName();
    }

    public function getObj(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
        ];
    }

    public function getName(): string
    {
        return !$this->currentTranslation ? $this->name : $this->currentTranslation->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getNoOfDeliveryDays(): ?int
    {
        return $this->noOfDeliveryDays;
    }

    public function setNoOfDeliveryDays(?int $noOfDeliveryDays): static
    {
        $this->noOfDeliveryDays = $noOfDeliveryDays;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return Collection<int, ShippingZonePrice>
     */
    public function getShippingZonePrices(): Collection
    {
        return $this->shippingZonePrices;
    }

    public function addShippingZonePrice(ShippingZonePrice $shippingZonePrice): static
    {
        if (!$this->shippingZonePrices->contains($shippingZonePrice)) {
            $this->shippingZonePrices->add($shippingZonePrice);
            $shippingZonePrice->setShippingTime($this);
        }

        return $this;
    }

    public function removeShippingZonePrice(ShippingZonePrice $shippingZonePrice): static
    {
        if ($this->shippingZonePrices->removeElement($shippingZonePrice)) {
            // set the owning side to null (unless already changed)
            if ($shippingZonePrice->getShippingTime() === $this) {
                $shippingZonePrice->setShippingTime(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ShippingTimeTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ShippingTimeTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(ShippingTimeTranslation $translation): static
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
