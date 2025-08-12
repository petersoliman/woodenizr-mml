<?php

namespace App\ProductBundle\DTO;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


class Cart
{

    private $cartItems;


    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
    }

    /**
     * @return Collection|CartItem[]
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItem $cartItem): self
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems[] = $cartItem;
        }

        return $this;
    }

    public function removeCartItem(CartItem $cartItem): self
    {
        $this->cartItems->removeElement($cartItem);

        return $this;
    }

    public function getGrandTotal(): float
    {
        $grandTotal = 0;
        foreach ($this->getCartItems() as $cartItem) {
            $grandTotal += $cartItem->getTotalPrice();
        }

        return $grandTotal;
    }

    public function getNoOfItems(): int
    {
        $noOfItems = 0;
        foreach ($this->getCartItems() as $cartItem) {
            $noOfItems += $cartItem->getQty();
        }

        return $noOfItems;
    }

    public function getCartItemByProductUuid($uuid) :?CartItem {
        foreach($this->getCartItems() as $cartItem) {
            if($cartItem->getProduct()->getUuid() == $uuid) {
                return $cartItem;
            }
        }

        return null;
    }

}
