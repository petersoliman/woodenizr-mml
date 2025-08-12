<?php

namespace App\NewShippingBundle\Entity;

use App\CurrencyBundle\Entity\Currency;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("shipping_zone_price",
 *     uniqueConstraints={
 *       @UniqueConstraint(name="shipping_zone_unique", columns={"source_shipping_zone_id", "target_shipping_zone_id", "shipping_time_id"})
 *     }
 *  )
 * @ORM\Entity(repositoryClass="App\NewShippingBundle\Repository\ShippingZonePriceRepository")
 * @UniqueEntity({"sourceShippingZone", "targetShippingZone", "shippingTime"})
 */
class ShippingZonePrice implements DateTimeInterface
{
    use DateTimeTrait;


    const CALCULATOR_FIRST_KG_EXTRA_KG = 'first_kg_extra_kg';
    const CALCULATOR_WEIGHT_RATE = 'weight_rate';

    public static array $calculators = array(
        'First Kg and Extra kg' => self::CALCULATOR_FIRST_KG_EXTRA_KG,
        'Weight Rate' => self::CALCULATOR_WEIGHT_RATE,
    );

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="ShippingZone", inversedBy="sourceShippingZonePrices")
     */
    private ?ShippingZone $sourceShippingZone = null;

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="ShippingZone", inversedBy="targetShippingZonePrices")
     */
    private ?ShippingZone $targetShippingZone = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\NewShippingBundle\Entity\ShippingTime", inversedBy="shippingZonePrices")
     */
    private ?ShippingTime $shippingTime = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="calculator", type="string", length=45)
     */
    private ?string $calculator = null;

    /**
     * @ORM\Column(name="has_rates", type="boolean")
     */
    private bool $hasRates = false;

    /**
     * @ORM\Column(name="configuration", type="json")
     */
    private array $configuration = [];

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\CurrencyBundle\Entity\Currency")
     */
    private ?Currency $currency = null;

    /**
     * @ORM\ManyToOne(targetEntity="Courier")
     */
    private ?Courier $courier=null;
    /**
     * @ORM\OneToMany(targetEntity="ShippingZonePriceSpecificWeight", mappedBy="shippingZonePrice",cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"weight"="ASC"})
     */
    private Collection $specificWeights;

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $specificWeightsClone = new \Doctrine\Common\Collections\ArrayCollection();
            foreach ($this->getSpecificWeights() as $specificWeight) {
                $itemClone = clone $specificWeight;
                $itemClone->setShippingZonePrice($this);
                $specificWeightsClone->add($itemClone);
            }
            $this->specificWeights = $specificWeightsClone;
        }
    }

    public function __construct()
    {
        $this->specificWeights = new ArrayCollection();
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

    public function getCalculatorName(): string
    {
        return array_search($this->getCalculator(), self::$calculators);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalculator(): ?string
    {
        return $this->calculator;
    }

    public function setCalculator(string $calculator): self
    {
        $this->calculator = $calculator;

        return $this;
    }

    public function getSourceShippingZone(): ?ShippingZone
    {
        return $this->sourceShippingZone;
    }

    public function setSourceShippingZone(?ShippingZone $sourceShippingZone): self
    {
        $this->sourceShippingZone = $sourceShippingZone;

        return $this;
    }

    public function getTargetShippingZone(): ?ShippingZone
    {
        return $this->targetShippingZone;
    }

    public function setTargetShippingZone(?ShippingZone $targetShippingZone): self
    {
        $this->targetShippingZone = $targetShippingZone;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCourier(): ?Courier
    {
        return $this->courier;
    }

    public function setCourier(?Courier $courier): self
    {
        $this->courier = $courier;

        return $this;
    }


    /**
     * @return Collection|ShippingZonePriceSpecificWeight[]
     */
    public function getSpecificWeights(): Collection
    {
        return $this->specificWeights->filter(function ($specificWeight) {
            if ($specificWeight->getDeleted() == null) {
                return $specificWeight;
            }
        });
    }

    public function addSpecificWeight(ShippingZonePriceSpecificWeight $specificWeight): self
    {
        if (!$this->specificWeights->contains($specificWeight)) {
            $this->specificWeights[] = $specificWeight;
            $specificWeight->setShippingZonePrice($this);
        }

        return $this;
    }

    public function removeSpecificWeight(ShippingZonePriceSpecificWeight $specificWeight): self
    {
        if ($this->specificWeights->removeElement($specificWeight)) {
            // set the owning side to null (unless already changed)
            if ($specificWeight->getShippingZonePrice() === $this) {
                $specificWeight->setDeleted(new \DateTime());
            }
        }

        return $this;
    }

    public function getConfiguration(): ?array
    {
        if (!is_array($this->configuration)) {
            return [];
        }

        return $this->configuration;
    }

    public function setConfiguration(array $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getHasRates(): ?bool
    {
        return $this->hasRates;
    }

    public function setHasRates(bool $hasRates): self
    {
        $this->hasRates = $hasRates;

        return $this;
    }

    public function getShippingTime(): ?ShippingTime
    {
        return $this->shippingTime;
    }

    public function setShippingTime(?ShippingTime $shippingTime): self
    {
        $this->shippingTime = $shippingTime;

        return $this;
    }

    public function isHasRates(): ?bool
    {
        return $this->hasRates;
    }

}
