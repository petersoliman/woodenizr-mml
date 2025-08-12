<?php

namespace App\ECommerceBundle\Entity;

use App\NewShippingBundle\Entity\Zone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("cart_guest_data")
 * @ORM\Entity()
 */
class CartGuestData
{

    /**
     * @Assert\NotBlank()
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Cart", inversedBy="cartGuestData")
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Cart $cart = null;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="email", type="string", length=255)
     */
    private ?string $email = null;

    /**
     * @ORM\Column(name="address", type="text")
     */
    private ?string $address = null;

    /**
     * @ORM\Column(name="mobile_number", type="string", length=20)
     */
    private ?string $mobileNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\NewShippingBundle\Entity\Zone")
     */
    private ?Zone $zone = null;

    public function getFormattedFullAddress(bool $showPhoneNumber = true): ?string
    {
        $fullAddress = $this->getAddress();

        if ($this->getZone()) {
            $fullAddress .= ", " . $this->getZone()->getTitle();
        }
        if ($this->getMobileNumber() and $showPhoneNumber === true) {
            $fullAddress .= " - " . $this->getMobileNumber();
        }

        return $fullAddress;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

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

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): self
    {
        $this->cart = $cart;

        return $this;
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


}
