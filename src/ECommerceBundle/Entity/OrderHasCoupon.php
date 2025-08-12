<?php

namespace App\ECommerceBundle\Entity;

use App\ECommerceBundle\Model\CouponModel;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("order_has_coupon")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\OrderHasCouponRepository")
 */
class OrderHasCoupon extends CouponModel
{

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Order", inversedBy="orderHasCoupon")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Order $order = null;

    /**
     * @ORM\ManyToOne(targetEntity="Coupon", cascade={"persist"})
     */
    private ?Coupon $coupon = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="code", type="string", length=255)
     */
    private ?string $code = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): self
    {
        $this->coupon = $coupon;

        return $this;
    }


}
