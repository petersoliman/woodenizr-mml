<?php

namespace App\ECommerceBundle\Entity;

use App\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * Coupon
 * @ORM\Table("coupon_has_product")
 * @ORM\Entity()
 */
class CouponHasProduct
{

    /**
     * @Assert\NotBlank()
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Coupon", inversedBy="couponHasProducts")
     */
    private ?Coupon $coupon = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Product", inversedBy="couponHasProducts")
     */
    private ?Product $product = null;

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): self
    {
        $this->coupon = $coupon;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }


}
