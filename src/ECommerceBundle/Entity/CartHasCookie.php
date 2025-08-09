<?php

namespace App\ECommerceBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("cart_has_cookie")
 * @ORM\Entity()
 */
class CartHasCookie {

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Cart", inversedBy="cartHasCookie", cascade={"persist"})
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Cart $cart=null;

    /**
     * @ORM\Column(name="cookie", type="string", length=255)
     */
    private ?string $cookie=null;

    public function getCookie(): ?string
    {
        return $this->cookie;
    }

    public function setCookie(string $cookie): self
    {
        $this->cookie = $cookie;

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

}
