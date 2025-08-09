<?php

namespace App\ECommerceBundle\Entity;

use App\NewShippingBundle\Entity\Zone;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\ShippingBundle\Entity\ShippingAddress;
use App\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("cart")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\CartRepository")
 */
class Cart
{

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\OnlinePaymentBundle\Entity\PaymentMethod", cascade={"persist"})
     */
    private ?PaymentMethod $paymentMethod = null;

    /**
     * @ORM\ManyToOne(targetEntity="Coupon", cascade={"persist"})
     */
    private ?Coupon $coupon = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\ShippingBundle\Entity\ShippingAddress")
     */
    private ?ShippingAddress $shippingAddress = null;

    /**
     * @ORM\Column(name="created", type="datetime")
     */
    private ?\DateTimeInterface $created = null;

    /**
     * @ORM\OneToMany(targetEntity="CartHasProductPrice", mappedBy="cart", cascade={"persist", "remove"}, orphanRemoval=true)
     * */
    private Collection $cartHasProductPrices;

    /**
     * @ORM\OneToOne(targetEntity="CartHasUser", mappedBy="cart", cascade={"persist", "remove"})
     */
    private ?CartHasUser $cartHasUser = null;

    /**
     * @ORM\OneToOne(targetEntity="CartHasCookie", mappedBy="cart", cascade={"persist", "remove"})
     */
    private ?CartHasCookie $cartHasCookie = null;

    /**
     * @ORM\OneToOne(targetEntity="CartGuestData", mappedBy="cart", cascade={"persist", "remove"})
     */
    private ?CartGuestData $cartGuestData = null;


    private ?float $subTotal = null;
    private ?float $shippingFees = null;
    private ?float $discount = null;
    private ?float $extraFees = null;
    private ?float $grandTotal = null;

    public function getUser(): ?User
    {
        if ($this->getCartHasUser() === null) {
            return null;
        }
        if ($this->getCartHasUser()->getUser() instanceof User) {
            return $this->getCartHasUser()->getUser();
        }

        return null;
    }

    public function getBuyerName(): ?string
    {
        if ($this->getUser() instanceof User) {
            return $this->getUser()->getFullName();
        } elseif ($this->getCartGuestData() instanceof CartGuestData) {
            return $this->getCartGuestData()->getName();
        }
        return null;
    }

    public function getBuyerAddress(): ?string
    {
        if ($this->getUser() instanceof User and $this->getShippingAddress() instanceof ShippingAddress) {
            return $this->getShippingAddress()->getFormattedFullAddress(false);
        } elseif ($this->getCartGuestData() instanceof CartGuestData) {
            return $this->getCartGuestData()->getFormattedFullAddress(false);
        }
        return null;
    }

    public function getBuyerAddressZone(): ?Zone
    {
        if ($this->getUser() instanceof User and $this->getShippingAddress() instanceof ShippingAddress) {
            return $this->getShippingAddress()->getZone();
        } elseif ($this->getCartGuestData() instanceof CartGuestData) {
            return $this->getCartGuestData()->getZone();
        }
        return null;
    }

    public function getBuyerEmail(): ?string
    {
        if ($this->getUser() instanceof User) {
            return $this->getUser()->getEmail();
        } elseif ($this->getCartGuestData() instanceof CartGuestData) {
            return $this->getCartGuestData()->getEmail();
        }
        return null;
    }

    public function getBuyerMobileNumber(): ?string
    {
        if ($this->getUser() instanceof User and $this->getShippingAddress() instanceof ShippingAddress) {
            return $this->getShippingAddress()->getMobileNumber();
        } elseif ($this->getCartGuestData() instanceof CartGuestData) {
            return $this->getCartGuestData()->getMobileNumber();
        }
        return null;
    }

    public function getCookieHash(): ?string
    {
        if ($this->getCartHasCookie() === null) {
            return null;
        }
        if ($this->getCartHasCookie()->getCookie() instanceof User) {
            return $this->getCartHasCookie()->getCookie();
        }

        return null;
    }

    /**
     * @ORM\PrePersist()
     */
    public function updatedTimestamps(): void
    {
        $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
    }

    public function __construct()
    {
        $this->cartHasProductPrices = new ArrayCollection();
    }

    public function __clone(): void
    {
        $this->id = null;
    }

    public function getObj(): array
    {
        $couponObj = null;
        $userId = null;

        if ($this->getCartHasUser()) {
            $userId = ($this->getCartHasUser()->getUser()) ? $this->getCartHasUser()->getUser()->getId() : null;
        } elseif ($this->getCartHasCookie()) {
            $userId = $this->getCartHasCookie()->getCookie();
        }

        if ($this->getCoupon()) {
            $coupon = $this->getCoupon();
            $couponObj = [
                "code" => $coupon->getCode(),
                "description" => $coupon->getDescription(),
            ];
        }


        return [
            "id" => $this->getId(),
            "userId" => $userId,
            "noOfItems" => $this->getItemNo(),
            "coupon" => $couponObj, // object
            "shippingAddress" => ($this->getShippingAddress()) ? $this->getShippingAddress()->getObj() : null,
            "paymentMethod" => ($this->getPaymentMethod()) ? $this->getPaymentMethod()->getObj() : null,
            "created" => ($this->getCreated()) ? $this->getCreated()->format("Y-m-d H:i:s") : null,
        ];
    }

    public function getProductPricesHash(): ?string
    {
        $productPricesArray = [];
        if ($this->getCartHasProductPrices()->count() == 0) {
            return null;
        }
        foreach ($this->getCartHasProductPrices() as $cartHasProductPrice) {
            $productPrice = $cartHasProductPrice->getProductPrice();
            $productPricesArray[] = $productPrice->getId();
        }
        sort($productPricesArray); // sort ascending
        $productPricesConcatenate = implode("-", $productPricesArray);

        return md5($productPricesConcatenate);
    }

    public function getItemNo(): int
    {
        $itemNo = 0;

        foreach ($this->getCartHasProductPrices() as $cartHasProductPrice) {
            $itemNo += $cartHasProductPrice->getQty();
        }

        return $itemNo;
    }

    public function emptyCartHasProductPrices(): self
    {
        $this->cartHasProductPrices->clear();

        return $this;
    }


    public function getSubTotal(): ?float
    {
        return $this->subTotal;
    }

    public function setSubTotal(?float $subTotal): self
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    public function getShippingFees(): ?float
    {
        return $this->shippingFees;
    }

    public function setShippingFees(?float $shippingFees): self
    {
        $this->shippingFees = $shippingFees;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(?float $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    public function getExtraFees(): ?float
    {
        return $this->extraFees;
    }

    public function setExtraFees(?float $extraFees): self
    {
        $this->extraFees = $extraFees;

        return $this;
    }

    public function getGrandTotal(): ?float
    {
        return $this->grandTotal;
    }

    /**
     * @param float|null $grandTotal
     * @return Cart
     */
    public function setGrandTotal(?float $grandTotal): self
    {
        $this->grandTotal = $grandTotal;

        return $this;
    }


    /**
     * @return Collection|CartHasProductPrice[]
     */
    public function getCartHasProductPrices(): Collection
    {
        return $this->cartHasProductPrices;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

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

    public function getShippingAddress(): ?ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?ShippingAddress $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function addCartHasProductPrice(CartHasProductPrice $cartHasProductPrice): self
    {
        if (!$this->cartHasProductPrices->contains($cartHasProductPrice)) {
            $this->cartHasProductPrices->add($cartHasProductPrice);
            $cartHasProductPrice->setCart($this);
        }

        return $this;
    }

    public function removeCartHasProductPrice(CartHasProductPrice $cartHasProductPrice): self
    {
        if ($this->cartHasProductPrices->removeElement($cartHasProductPrice)) {
            // set the owning side to null (unless already changed)
            if ($cartHasProductPrice->getCart() === $this) {
                $cartHasProductPrice->setCart(null);
            }
        }

        return $this;
    }

    public function getCartHasUser(): ?CartHasUser
    {
        return $this->cartHasUser;
    }

    public function setCartHasUser(?CartHasUser $cartHasUser): self
    {
        // unset the owning side of the relation if necessary
        if ($cartHasUser === null && $this->cartHasUser !== null) {
            $this->cartHasUser->setCart(null);
        }

        // set the owning side of the relation if necessary
        if ($cartHasUser !== null && $cartHasUser->getCart() !== $this) {
            $cartHasUser->setCart($this);
        }

        $this->cartHasUser = $cartHasUser;

        return $this;
    }

    public function getCartHasCookie(): ?CartHasCookie
    {
        return $this->cartHasCookie;
    }

    public function setCartHasCookie(?CartHasCookie $cartHasCookie): self
    {
        // unset the owning side of the relation if necessary
        if ($cartHasCookie === null && $this->cartHasCookie !== null) {
            $this->cartHasCookie->setCart(null);
        }

        // set the owning side of the relation if necessary
        if ($cartHasCookie !== null && $cartHasCookie->getCart() !== $this) {
            $cartHasCookie->setCart($this);
        }

        $this->cartHasCookie = $cartHasCookie;

        return $this;
    }

    public function getCartGuestData(): ?CartGuestData
    {
        return $this->cartGuestData;
    }

    public function setCartGuestData(?CartGuestData $cartGuestData): self
    {
        // unset the owning side of the relation if necessary
        if ($cartGuestData === null && $this->cartGuestData !== null) {
            $this->cartGuestData->setCart(null);
        }

        // set the owning side of the relation if necessary
        if ($cartGuestData !== null && $cartGuestData->getCart() !== $this) {
            $cartGuestData->setCart($this);
        }

        $this->cartGuestData = $cartGuestData;

        return $this;
    }


}
