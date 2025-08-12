<?php

namespace App\ECommerceBundle\Entity;

use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Enum\ShippingStatusEnum;
use App\NewShippingBundle\Entity\ShippingTime;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use App\UserBundle\Entity\User;
use App\VendorBundle\Entity\Vendor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Interfaces\UUIDInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\UuidTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("`order`")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\OrderRepository")
 */
class Order implements UUIDInterface
{
    use DateTimeTrait,
        UuidTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="children")
     * */
    private ?Order $parent = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\UserBundle\Entity\User")
     * */
    private ?User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\OnlinePaymentBundle\Entity\PaymentMethod", cascade={"persist"})
     */
    private ?PaymentMethod $paymentMethod = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\NewShippingBundle\Entity\ShippingTime", cascade={"persist"})
     */
    private ?ShippingTime $shippingTime = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\VendorBundle\Entity\Vendor", cascade={"persist"})
     */
    private ?Vendor $vendor = null;

    /**
     * @ORM\Column(name="state", type="string", enumType=OrderStatusEnum::class, length=255, nullable=false)
     */
    private ?OrderStatusEnum $state = null;

    /**
     * @ORM\Column(name="shipping_state", type="string", enumType=ShippingStatusEnum::class, length=255, nullable=false)
     */
    private ?ShippingStatusEnum $shippingState = null;

    /**
     * @ORM\Column(name="payment_state", type="string", enumType=PaymentStatusEnum::class, length=255, nullable=false)
     */
    private ?PaymentStatusEnum $paymentState = null;

    /**
     * @ORM\Column(name="success_date", type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $successDate = null;

    /**
     * @ORM\Column(name="item_qty", type="integer", nullable=true)
     */
    private int $itemQty = 0;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="shipping_cost", type="float", nullable=false)
     */
    private float $shippingCost = 0;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="sub_total", type="float", nullable=false)
     */
    private float $subTotal = 0;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="total_price", type="float", nullable=false)
     */
    private float $totalPrice = 0;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="discount", type="float", nullable=true)
     */
    private float $discount = 0;

    /**
     * @ORM\Column(name="extra_fee", type="float", nullable=true)
     */
    private float $extraFee = 0;

    /**
     * @ORM\Column(name="cart_id", type="integer", length=10, nullable=true)
     */
    private ?int $cartId = null;
    /**
     * @ORM\Column(name="sent_confirmation_email", type="boolean")
     */
    private bool $sentConfirmationEmail = false;

    /**
     * @ORM\Column(name="waybill_number", type="string", length=45, nullable=true)
     */
    private ?string $waybillNumber;

    /**
     * @var array
     *
     * @ORM\Column(name="cart", type="json")
     */
    private array $cartObject = [];

    /**
     * @ORM\Column(name="cart_product_prices_hash", type="string", length=100)
     */
    private ?string $cartProductPricesHash = null;

    /**
     * @ORM\OneToMany(targetEntity="OrderComment", mappedBy="order")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private Collection $comments;

    /**
     * @ORM\OneToMany(targetEntity="OrderHasProductPrice", mappedBy="order", cascade={"persist"})
     */
    private Collection $orderHasProductPrices;

    /**
     * @ORM\OneToOne(targetEntity="OrderHasCoupon", mappedBy="order", cascade={"persist", "remove"})
     */
    private ?OrderHasCoupon $orderHasCoupon = null;

    /**
     * @ORM\OneToOne(targetEntity="OrderShippingAddress", mappedBy="order", cascade={"all"})
     */
    private ?OrderShippingAddress $orderShippingAddress = null;

    /**
     * @ORM\OneToOne(targetEntity="OrderGuestData", mappedBy="order", cascade={"persist", "remove"})
     */
    private ?OrderGuestData $orderGuestData = null;

    /**
     * @ORM\OneToMany(targetEntity="OrderLog", mappedBy="order", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private Collection $logs;

    /**
     * @ORM\OneToMany(targetEntity="Order", mappedBy="parent")
     */
    private Collection $children;


    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->orderHasProductPrices = new ArrayCollection();
        $this->logs = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updatedTimestamps(): void
    {
        $this->setModified(new \DateTime(date('Y-m-d H:i:s')));
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    public function getStateName(): ?string
    {
        return $this->getState()?->name();
    }

    public function getPaymentStateName(): ?string
    {
        return $this->getPaymentState()?->name();
    }

    public function getShippingStateName(): ?string
    {
        return $this->getShippingState()?->name();
    }

    public function getStateFEColor(): ?string
    {
        return $this->getState()?->color();
    }

    public function getStateBEColor(): ?string
    {
        return $this->getState()?->color();
    }

    public function getPaymentStateFEColor(): ?string
    {
        return $this->getPaymentState()?->color();
    }

    public function getPaymentStateBEColor(): ?string
    {
        return $this->getPaymentState()?->color();
    }

    public function getShippingStateFEColor(): ?string
    {
        return $this->getShippingState()?->color();
    }

    public function getShippingStateBEColor(): ?string
    {
        return $this->getShippingState()?->color();
    }

    public function getState(): ?OrderStatusEnum
    {
        return $this->state;
    }

    public function setState(?OrderStatusEnum $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getShippingState(): ?ShippingStatusEnum
    {
        return $this->shippingState;
    }

    public function setShippingState(?ShippingStatusEnum $shippingState): self
    {
        $this->shippingState = $shippingState;

        return $this;
    }

    public function getPaymentState(): ?PaymentStatusEnum
    {
        return $this->paymentState;
    }

    public function setPaymentState(?PaymentStatusEnum $paymentState): self
    {
        $this->paymentState = $paymentState;

        return $this;
    }

    public function getItemNo(): int
    {
        $itemNo = 0;

        foreach ($this->getOrderHasProductPrices() as $orderHasProductPrice) {
            $itemNo += $orderHasProductPrice->getQty();
        }

        return $itemNo;
    }

    public function getBuyerName(): ?string
    {
        if ($this->getOrderGuestData()) {
            return $this->getOrderGuestData()->getName();
        } elseif ($this->getUser()) {
            return $this->getUser()->getFullName();
        }

        return null;
    }

    public function getBuyerMobileNumber(): ?string
    {
        return $this->getOrderShippingAddress()->getMobileNumber();
    }

    public function getBuyerAddress(): ?string
    {
        return $this->getOrderShippingAddress()->getFormattedFullAddress(false);
    }

    public function getBuyerEmail(): ?string
    {
        if ($this->getOrderGuestData()) {
            return $this->getOrderGuestData()->getEmail();
        } elseif ($this->getUser()) {
            return $this->getUser()->getEmail();
        }

        return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSuccessDate(): ?\DateTimeInterface
    {
        return $this->successDate;
    }

    public function setSuccessDate(?\DateTimeInterface $successDate): self
    {
        $this->successDate = $successDate;

        return $this;
    }

    public function getItemQty(): ?int
    {
        return $this->itemQty;
    }

    public function setItemQty(?int $itemQty): self
    {
        $this->itemQty = $itemQty;

        return $this;
    }

    public function getShippingCost(): ?float
    {
        return $this->shippingCost;
    }

    public function setShippingCost(float $shippingCost): self
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    public function getSubTotal(): ?float
    {
        return $this->subTotal;
    }

    public function setSubTotal(float $subTotal): self
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

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

    public function getExtraFee(): ?float
    {
        return $this->extraFee;
    }

    public function setExtraFee(?float $extraFee): self
    {
        $this->extraFee = $extraFee;

        return $this;
    }

    public function getCartId(): ?int
    {
        return $this->cartId;
    }

    public function setCartId(?int $cartId): self
    {
        $this->cartId = $cartId;

        return $this;
    }

    public function getCartObject(): array
    {
        return $this->cartObject;
    }

    public function setCartObject(array $cartObject): self
    {
        $this->cartObject = $cartObject;

        return $this;
    }

    public function getCartProductPricesHash(): ?string
    {
        return $this->cartProductPricesHash;
    }

    public function setCartProductPricesHash(string $cartProductPricesHash): self
    {
        $this->cartProductPricesHash = $cartProductPricesHash;

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

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return Collection<int, OrderComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(OrderComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setOrder($this);
        }

        return $this;
    }

    public function removeComment(OrderComment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getOrder() === $this) {
                $comment->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OrderHasProductPrice>
     */
    public function getOrderHasProductPrices(): Collection
    {
        return $this->orderHasProductPrices;
    }

    public function addOrderHasProductPrice(OrderHasProductPrice $orderHasProductPrice): self
    {
        if (!$this->orderHasProductPrices->contains($orderHasProductPrice)) {
            $this->orderHasProductPrices->add($orderHasProductPrice);
            $orderHasProductPrice->setOrder($this);
        }

        return $this;
    }

    public function removeOrderHasProductPrice(OrderHasProductPrice $orderHasProductPrice): self
    {
        if ($this->orderHasProductPrices->removeElement($orderHasProductPrice)) {
            // set the owning side to null (unless already changed)
            if ($orderHasProductPrice->getOrder() === $this) {
                $orderHasProductPrice->setOrder(null);
            }
        }

        return $this;
    }

    public function getOrderHasCoupon(): ?OrderHasCoupon
    {
        return $this->orderHasCoupon;
    }

    public function setOrderHasCoupon(?OrderHasCoupon $orderHasCoupon): self
    {
        // unset the owning side of the relation if necessary
        if ($orderHasCoupon === null && $this->orderHasCoupon !== null) {
            $this->orderHasCoupon->setOrder(null);
        }

        // set the owning side of the relation if necessary
        if ($orderHasCoupon !== null && $orderHasCoupon->getOrder() !== $this) {
            $orderHasCoupon->setOrder($this);
        }

        $this->orderHasCoupon = $orderHasCoupon;

        return $this;
    }

    public function getOrderShippingAddress(): ?OrderShippingAddress
    {
        return $this->orderShippingAddress;
    }

    public function setOrderShippingAddress(?OrderShippingAddress $orderShippingAddress): self
    {
        // unset the owning side of the relation if necessary
        if ($orderShippingAddress === null && $this->orderShippingAddress !== null) {
            $this->orderShippingAddress->setOrder(null);
        }

        // set the owning side of the relation if necessary
        if ($orderShippingAddress !== null && $orderShippingAddress->getOrder() !== $this) {
            $orderShippingAddress->setOrder($this);
        }

        $this->orderShippingAddress = $orderShippingAddress;

        return $this;
    }

    public function getOrderGuestData(): ?OrderGuestData
    {
        return $this->orderGuestData;
    }

    public function setOrderGuestData(?OrderGuestData $orderGuestData): self
    {
        // unset the owning side of the relation if necessary
        if ($orderGuestData === null && $this->orderGuestData !== null) {
            $this->orderGuestData->setOrder(null);
        }

        // set the owning side of the relation if necessary
        if ($orderGuestData !== null && $orderGuestData->getOrder() !== $this) {
            $orderGuestData->setOrder($this);
        }

        $this->orderGuestData = $orderGuestData;

        return $this;
    }

    /**
     * @return Collection<int, OrderLog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(OrderLog $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setOrder($this);
        }

        return $this;
    }

    public function removeLog(OrderLog $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getOrder() === $this) {
                $log->setOrder(null);
            }
        }

        return $this;
    }

    public function isSentConfirmationEmail(): ?bool
    {
        return $this->sentConfirmationEmail;
    }

    public function setSentConfirmationEmail(bool $sentConfirmationEmail): static
    {
        $this->sentConfirmationEmail = $sentConfirmationEmail;

        return $this;
    }

    public function getWaybillNumber(): ?string
    {
        return $this->waybillNumber;
    }

    public function setWaybillNumber(?string $waybillNumber): static
    {
        $this->waybillNumber = $waybillNumber;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Order $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Order $child): static
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getShippingTime(): ?ShippingTime
    {
        return $this->shippingTime;
    }

    public function setShippingTime(?ShippingTime $shippingTime): static
    {
        $this->shippingTime = $shippingTime;

        return $this;
    }

    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    public function setVendor(?Vendor $vendor): static
    {
        $this->vendor = $vendor;

        return $this;
    }


}