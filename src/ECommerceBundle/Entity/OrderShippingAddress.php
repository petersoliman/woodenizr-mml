<?php

namespace App\ECommerceBundle\Entity;

use App\ShippingBundle\Entity\ShippingAddress;
use App\ShippingBundle\Model\ShippingAddressModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("order_shipping_address")
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\OrderShippingAddressRepository")
 */
class OrderShippingAddress extends ShippingAddressModel
{

    /**
     * @orm\Id
     * @ORM\OneToOne(targetEntity="Order", inversedBy="orderShippingAddress")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Order $order = null;


    /**
     * @ORM\ManyToOne(targetEntity="\App\ShippingBundle\Entity\ShippingAddress")
     */
    private ?ShippingAddress $shippingAddress = null;


    public function __toString()
    {
        return $this->getTitle();
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

    public function getShippingAddress(): ?ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?ShippingAddress $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

}
