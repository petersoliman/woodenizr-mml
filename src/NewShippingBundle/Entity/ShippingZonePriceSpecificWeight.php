<?php

namespace App\NewShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("shipping_zone_price_specific_weight",
 *      uniqueConstraints={
 *          @UniqueConstraint(name="shipping_zone_price_specific_weight_unique", columns={"shipping_zone_price_id", "weight"})
 *      }
 *  )
 * @ORM\Entity(repositoryClass="App\NewShippingBundle\Repository\ShippingZonePriceSpecificWeightRepository")
 * @UniqueEntity({"shippingZonePrice", "weight"})
 */
class ShippingZonePriceSpecificWeight implements DateTimeInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait;


    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="ShippingZonePrice", inversedBy="specificWeights")
     */
    private ?ShippingZonePrice $shippingZonePrice = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="weight", type="float", nullable=false)
     */
    private $weight;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="rate", type="float")
     */
    private ?float $rate = null;

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

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getShippingZonePrice(): ?ShippingZonePrice
    {
        return $this->shippingZonePrice;
    }

    public function setShippingZonePrice(?ShippingZonePrice $shippingZonePrice): self
    {
        $this->shippingZonePrice = $shippingZonePrice;

        return $this;
    }


}
