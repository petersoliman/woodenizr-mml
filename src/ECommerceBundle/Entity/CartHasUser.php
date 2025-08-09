<?php

namespace App\ECommerceBundle\Entity;

use App\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CartHasUser
 * @ORM\Table("cart_has_user")
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\CartHasUserRepository")
 */
class CartHasUser
{

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Cart", inversedBy="cartHasUser", cascade={"persist"})
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Cart $cart = null;

    /**
     * @ORM\ManyToOne(targetEntity="\App\UserBundle\Entity\User")
     */
    private ?User $user = null;

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): self
    {
        $this->cart = $cart;

        return $this;
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


}
