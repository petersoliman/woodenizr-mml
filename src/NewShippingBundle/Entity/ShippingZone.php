<?php

namespace App\NewShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("shipping_zone")
 * @ORM\Entity(repositoryClass="App\NewShippingBundle\Repository\ShippingZoneRepository")
 */
class ShippingZone implements DateTimeInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait;

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
     * @ORM\OneToMany(targetEntity="ShippingZonePrice", mappedBy="sourceShippingZone")
     */
    private Collection $sourceShippingZonePrices;

    /**
     * @ORM\OneToMany(targetEntity="ShippingZonePrice", mappedBy="targetShippingZone")
     */
    private Collection $targetShippingZonePrices;

    /**
     * @ORM\ManyToMany(targetEntity="Zone", inversedBy="shippingZones")
     */
    private Collection $zones;

    public function __construct()
    {
        $this->zones = new ArrayCollection();
        $this->sourceShippingZonePrices = new ArrayCollection();
        $this->targetShippingZonePrices = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        $this->setModified(new \DateTime(date('Y-m-d H:i:s')));

        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection|Zone[]
     */
    public function getZones(): Collection
    {
        return $this->zones;
    }

    public function addZone(Zone $zone): self
    {
        if (!$this->zones->contains($zone)) {
            $this->zones[] = $zone;
        }

        return $this;
    }

    public function removeZone(Zone $zone): self
    {
        $this->zones->removeElement($zone);

        return $this;
    }

    /**
     * @return Collection<int, ShippingZonePrice>
     */
    public function getSourceShippingZonePrices(): Collection
    {
        return $this->sourceShippingZonePrices;
    }

    public function addSourceShippingZonePrice(ShippingZonePrice $sourceShippingZonePrice): static
    {
        if (!$this->sourceShippingZonePrices->contains($sourceShippingZonePrice)) {
            $this->sourceShippingZonePrices->add($sourceShippingZonePrice);
            $sourceShippingZonePrice->setSourceShippingZone($this);
        }

        return $this;
    }

    public function removeSourceShippingZonePrice(ShippingZonePrice $sourceShippingZonePrice): static
    {
        if ($this->sourceShippingZonePrices->removeElement($sourceShippingZonePrice)) {
            // set the owning side to null (unless already changed)
            if ($sourceShippingZonePrice->getSourceShippingZone() === $this) {
                $sourceShippingZonePrice->setSourceShippingZone(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ShippingZonePrice>
     */
    public function getTargetShippingZonePrices(): Collection
    {
        return $this->targetShippingZonePrices;
    }

    public function addTargetShippingZonePrice(ShippingZonePrice $targetShippingZonePrice): static
    {
        if (!$this->targetShippingZonePrices->contains($targetShippingZonePrice)) {
            $this->targetShippingZonePrices->add($targetShippingZonePrice);
            $targetShippingZonePrice->setTargetShippingZone($this);
        }

        return $this;
    }

    public function removeTargetShippingZonePrice(ShippingZonePrice $targetShippingZonePrice): static
    {
        if ($this->targetShippingZonePrices->removeElement($targetShippingZonePrice)) {
            // set the owning side to null (unless already changed)
            if ($targetShippingZonePrice->getTargetShippingZone() === $this) {
                $targetShippingZonePrice->setTargetShippingZone(null);
            }
        }

        return $this;
    }

}
