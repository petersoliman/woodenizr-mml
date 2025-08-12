<?php

namespace App\VendorBundle\Entity;

use App\NewShippingBundle\Entity\Zone;
use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;

/**
 * @ORM\Table("store_address")
 * @ORM\Entity(repositoryClass="App\VendorBundle\Repository\StoreAddressRepository")
 */
class StoreAddress implements DateTimeInterface
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
     * @ORM\ManyToOne(targetEntity="App\NewShippingBundle\Entity\Zone")
     */
    private ?Zone $zone = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\VendorBundle\Entity\Vendor", inversedBy="storeAddresses")
     */
    private ?Vendor $vendor = null;

    /**
     * @ORM\Column(name="title", type="string", length=45, nullable=true)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="street_name", type="string", length=500, nullable=true)
     */
    private ?string $streetName = null;

    /**
     * @ORM\Column(name="land_mark", type="string", length=255, nullable=true)
     */
    private ?string $landMark = null;

    /**
     * international vendor
     * @ORM\Column(name="town", type="string", length=255, nullable=true)
     */
    private ?string $town = null;

    /**
     * City in international vendor
     * @ORM\Column(name="area", type="string", length=50, nullable=true)
     */
    private ?string $area = null;

    /**
     * @ORM\Column(name="floor", type="string", length=100, nullable=true)
     */
    private ?string $floor = null;

    /**
     * @ORM\Column(name="apartment", type="string", length=5, nullable=true)
     */
    private ?string $apartment = null;

    /**
     * @ORM\Column(name="postal_code", type="string", length=10, nullable=true)
     */
    private ?string $postalCode = null;

    /**
     * @ORM\Column(name="mobile_number", type="string", length=20)
     */
    private ?string $mobileNumber = null;

    /**
     * @ORM\Column(name="full_address", type="text", nullable=true)
     */
    private ?string $fullAddress;

    public function __toString()
    {

        $title = null;
        if ($this->getTitle()) {
            $title = $this->getTitle() . " - ";
        }
        $title .= $this->getFullAddress();

        return $title;
    }


    public function getFullAddress(): ?string
    {
        if ($this->fullAddress != null) {
            return $this->fullAddress;
        }
        $fullAddress = null;
        if ($this->getApartment() != null) {
            $fullAddress .= $this->getApartment() . ' ';
        }
        if ($this->getStreetName() != null) {
            $fullAddress .= $this->getStreetName() . ', ';
        }
        if ($this->getTown() != null) {
            $fullAddress .= $this->getTown() . ', ';
        }
        if ($this->getArea() != null) {
            $fullAddress .= $this->getArea() . ', ';
        }
        if ($this->getZone() != null) {
            $fullAddress .= $this->getZone() . ', ';
        }
        if ($this->getPostalCode() != null) {
            $fullAddress .= "#" . $this->getPostalCode();
        }
        if ($fullAddress) {
            return $fullAddress;
        }

        return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    public function setStreetName(?string $streetName): static
    {
        $this->streetName = $streetName;

        return $this;
    }

    public function getLandMark(): ?string
    {
        return $this->landMark;
    }

    public function setLandMark(?string $landMark): static
    {
        $this->landMark = $landMark;

        return $this;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function setTown(?string $town): static
    {
        $this->town = $town;

        return $this;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(?string $area): static
    {
        $this->area = $area;

        return $this;
    }

    public function getFloor(): ?string
    {
        return $this->floor;
    }

    public function setFloor(?string $floor): static
    {
        $this->floor = $floor;

        return $this;
    }

    public function getApartment(): ?string
    {
        return $this->apartment;
    }

    public function setApartment(?string $apartment): static
    {
        $this->apartment = $apartment;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(string $mobileNumber): static
    {
        $this->mobileNumber = $mobileNumber;

        return $this;
    }

    public function setFullAddress(?string $fullAddress): static
    {
        $this->fullAddress = $fullAddress;

        return $this;
    }

    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    public function setVendor(?Vendor $vendor): static
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        $this->zone = $zone;

        return $this;
    }


}
