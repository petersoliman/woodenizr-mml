<?php

namespace App\ECommerceBundle\Entity;

use App\ECommerceBundle\Model\CouponModel;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Coupon
 * @ORM\Table("coupon")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\CouponRepository")
 * @UniqueEntity("code", message="This code is used before.")
 */
class Coupon extends CouponModel implements DateTimeInterface
{

    use VirtualDeleteTrait,
        DateTimeTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     */
    private ?string $code = null;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private ?string $comment = null;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private ?string $description = null;


    /**
     * @ORM\OneToMany(targetEntity="CouponHasProduct", mappedBy="coupon", cascade={"persist"})
     * */
    private Collection $couponHasProducts;


    /**
     * @ORM\PrePersist
     */
    public function updatedTimestamps()
    {
        $this->setModified(new \DateTime(date('Y-m-d H:i:s')));
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->couponHasProducts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, CouponHasProduct>
     */
    public function getCouponHasProducts(): Collection
    {
        return $this->couponHasProducts;
    }

    public function addCouponHasProduct(CouponHasProduct $couponHasProduct): self
    {
        if (!$this->couponHasProducts->contains($couponHasProduct)) {
            $this->couponHasProducts->add($couponHasProduct);
            $couponHasProduct->setCoupon($this);
        }

        return $this;
    }

    public function removeCouponHasProduct(CouponHasProduct $couponHasProduct): self
    {
        if ($this->couponHasProducts->removeElement($couponHasProduct)) {
            // set the owning side to null (unless already changed)
            if ($couponHasProduct->getCoupon() === $this) {
                $couponHasProduct->setCoupon(null);
            }
        }

        return $this;
    }

}
