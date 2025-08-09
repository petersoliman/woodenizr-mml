<?php

namespace App\ECommerceBundle\Model;

use App\ECommerceBundle\Enum\CouponTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 */
abstract class CouponModel
{

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="discount_type", type="string", enumType=CouponTypeEnum::class, length=255, nullable=false)
     */
    protected ?CouponTypeEnum $discountType = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="discount_value", type="float", nullable=false)
     */
    protected ?float $discountValue = null;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="start_date", type="date", nullable=true)
     */
    protected ?\DateTimeInterface $startDate = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="expiry_date", type="date", nullable=true)
     */
    protected ?\DateTimeInterface $expiryDate = null;

    /**
     * @ORM\Column(name="minimum_purchase_amount", type="float", nullable=true)
     */
    protected ?float $minimumPurchaseAmount = null;


    /**
     * @ORM\Column(name="limit_use_per_user", type="integer", nullable=true)
     */
    protected ?int $limitUsePerUser = null;

    /**
     * @ORM\Column(name="total_number_of_use", type="integer", nullable=true)
     */
    protected ?int $totalNumberOfUse = null;

    /**
     * low true ha3mel apply ll coupon discount 3ala el discount el mawgod 3ala el product
     *
     * @ORM\Column(name="add_discount_after_product_discount", type="boolean")
     */
    protected bool $addDiscountAfterProductDiscount = false;

    /**
     * @ORM\Column(name="free_payment_method_fee", type="boolean")
     */
    protected $freePaymentMethodFee = false;

    /**
     * low true ha3mel apply ll coupon 3ala awel order ll user
     *
     * @ORM\Column(name="first_order_only", type="boolean")
     */
    protected bool $firstOrderOnly = false;

    /**
     * @ORM\Column(name="active", type="boolean")
     */
    protected bool $active = true;

    /**
     * @ORM\Column(name="shipping", type="boolean")
     */
    protected bool $shipping = false;

    public function getDiscountTypeName(): ?string
    {
        return $this->getDiscountType()?->name();
    }

    public function getDiscountType(): ?CouponTypeEnum
    {
        return $this->discountType;
    }

    public function setDiscountType(?CouponTypeEnum $discountType): self
    {
        $this->discountType = $discountType;

        return $this;
    }


    public function getDiscountValue(): ?float
    {
        return $this->discountValue;
    }

    public function setDiscountValue(float $discountValue): self
    {
        $this->discountValue = $discountValue;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    public function getMinimumPurchaseAmount(): ?float
    {
        return $this->minimumPurchaseAmount;
    }

    public function setMinimumPurchaseAmount(?float $minimumPurchaseAmount): self
    {
        $this->minimumPurchaseAmount = $minimumPurchaseAmount;

        return $this;
    }

    public function getLimitUsePerUser(): ?int
    {
        return $this->limitUsePerUser;
    }

    public function setLimitUsePerUser(?int $limitUsePerUser): self
    {
        $this->limitUsePerUser = $limitUsePerUser;

        return $this;
    }

    public function getTotalNumberOfUse(): ?int
    {
        return $this->totalNumberOfUse;
    }

    public function setTotalNumberOfUse(?int $totalNumberOfUse): self
    {
        $this->totalNumberOfUse = $totalNumberOfUse;

        return $this;
    }

    public function isFreePaymentMethodFee(): ?bool
    {
        return $this->freePaymentMethodFee;
    }

    public function setFreePaymentMethodFee(bool $freePaymentMethodFee): self
    {
        $this->freePaymentMethodFee = $freePaymentMethodFee;

        return $this;
    }

    public function isAddDiscountAfterProductDiscount(): ?bool
    {
        return $this->addDiscountAfterProductDiscount;
    }

    public function setAddDiscountAfterProductDiscount(bool $addDiscountAfterProductDiscount): self
    {
        $this->addDiscountAfterProductDiscount = $addDiscountAfterProductDiscount;

        return $this;
    }

    public function isFirstOrderOnly(): ?bool
    {
        return $this->firstOrderOnly;
    }

    public function setFirstOrderOnly(bool $firstOrderOnly): self
    {
        $this->firstOrderOnly = $firstOrderOnly;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function isShipping(): ?bool
    {
        return $this->shipping;
    }

    public function setShipping(bool $shipping): self
    {
        $this->shipping = $shipping;

        return $this;
    }

}
