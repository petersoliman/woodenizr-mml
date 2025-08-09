<?php

namespace App\ECommerceBundle\Entity;

use App\ECommerceBundle\Entity\Order;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("order_guest_data")
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\OrderGuestDataRepository")
 */
class OrderGuestData
{

    /**
     * @Assert\NotBlank()
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Order", inversedBy="orderGuestData")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Order $order = null;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="email", type="string", length=255)
     */
    private ?string $email = null;

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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

}
