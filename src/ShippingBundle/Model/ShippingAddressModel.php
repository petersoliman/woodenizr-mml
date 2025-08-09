<?php

namespace App\ShippingBundle\Model;

use App\NewShippingBundle\Entity\Zone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 */
abstract class ShippingAddressModel
{
    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="\App\NewShippingBundle\Entity\Zone")
     */
    protected ?Zone $zone = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=45, nullable=true)
     */
    protected ?string $title = null;

    /**
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    protected ?string $note = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="mobile_number", type="string", length=50)
     */
    protected ?string $mobileNumber = null;

    /**
     * @ORM\Column(name="full_address", type="text", nullable=true)
     */
    protected ?string $fullAddress = null;

    public function __toString()
    {
        return $this->getTitle();
    }

    public function getObj(): array
    {
        return [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
            "fullAddress" => $this->getFullAddress(),
        ];
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    public function getFormattedFullAddress(bool $showPhoneNumber = true): ?string
    {
        $fullAddress = $this->fullAddress;


        if ($this->getZone()) {
            $fullAddress .= ", " . $this->getZone()->getTitle();
        }
        if ($this->getMobileNumber() and $showPhoneNumber === true) {
            $fullAddress .= " - " . $this->getMobileNumber();
        }

        return $fullAddress;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(string $mobileNumber): self
    {
        $this->mobileNumber = $mobileNumber;

        return $this;
    }

    public function getFullAddress(): ?string
    {
        return $this->fullAddress;
    }

    public function setFullAddress(?string $fullAddress): self
    {
        $this->fullAddress = $fullAddress;

        return $this;
    }
}
