<?php

namespace App\ShippingBundle\Entity;

use App\ShippingBundle\Model\ShippingAddressModel;
use App\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;

/**
 * ShippingAddress
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("shipping_address")
 * @ORM\Entity(repositoryClass="App\ShippingBundle\Repository\ShippingAddressRepository")
 */
class ShippingAddress extends ShippingAddressModel implements DateTimeInterface
{

    use VirtualDeleteTrait;
    use DateTimeTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;


    /**
     * @ORM\ManyToOne(targetEntity="App\UserBundle\Entity\User")
     */
    private ?User $user = null;

    /**
     * @ORM\Column(name="`default`", type="boolean")
     */
    private bool $default = false;

    public function getObj(): array
    {
        return [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
            "mobileNumber" => $this->getMobileNumber(),
            "address" => $this->getFullAddress(),
            "zone" => $this->getZone()->getObj(),
            "default" => $this->getDefault(),
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDefault(): ?bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
    }
}
