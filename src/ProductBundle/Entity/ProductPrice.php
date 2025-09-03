<?php

namespace App\ProductBundle\Entity;

use App\CurrencyBundle\Entity\Currency;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @Gedmo\Loggable()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="product_price")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductPriceRepository")
 */
class ProductPrice implements DateTimeInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Product", inversedBy="prices", cascade={"remove"})
     */
    private ?Product $product = null;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="App\CurrencyBundle\Entity\Currency")
     */
    private ?Currency $currency = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="title", type="string", length=150, nullable=true)
     */
    private ?string $title = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="stock", type="integer", options={"default" = 0}, nullable=true)
     */
    private ?int $stock = 0;

    /**
     * @Gedmo\Versioned
     * @Assert\NotBlank()
     * @ORM\Column(name="unit_price", type="float")
     */
    private ?float $unitPrice = null;

    /**
     * @Gedmo\Versioned
     * @Assert\NotBlank()
     * @ORM\Column(name="promotional_price", type="float", nullable=true)
     */
    private ?float $promotionalPrice = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="weight", type="float", nullable=true)
     */
    private ?float $weight = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="length", type="float", nullable=true)
     */
    private ?float $length = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="width", type="float", nullable=true)
     */
    private ?float $width = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="height", type="float", nullable=true)
     */
    private ?float $height = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="promotional_expiry_date", type="date", nullable=true)
     */
    private ?\DateTime $promotionalExpiryDate = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="variant_option_ids", type="text", nullable=true)
     */
    private ?string $variantOptionIds = null;

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }

    public function getObj(): array
    {
        return [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
            "sellPrice" => $this->getSellPrice(),
            "promotionalPercentage" => $this->getPromotionalPercentage(),
            "stock" => $this->getStock(),
            "originalPrice" => $this->getUnitPrice(),
        ];
    }

    public function getPromotionalPercentage(): int
    {
        $promotionalExpiryDate = $this->getPromotionalExpiryDate();
        $currentDate = new \DateTime();
        if (
            !$promotionalExpiryDate instanceof \DateTimeInterface
            or $promotionalExpiryDate->format("Y-m-d") < $currentDate->format("Y-m-d")
        ) {
            return 0;
        }

        if ($this->getPromotionalPrice() > 0) {
            $discountPercentage = ($this->getPromotionalPrice() / $this->getUnitPrice()) * 100;
            return 100 - round($discountPercentage);
        }

        return 0;
    }

    public function getUnitPriceWithCommission(): float
    {
        $unitPrice = $this->getUnitPrice();
        $commissionPercentage = $this->getProduct()->getVendor()?->getCommissionPercentage() ?? 0;

        if ($commissionPercentage > 0) {
            $commission = ($unitPrice * $commissionPercentage) / 100;
            $unitPrice = $unitPrice + $commission;
        }
        return $unitPrice;
    }
    public function getSellPriceBeforeCommission(): ?float
    {
        $sellPrice = $this->getUnitPrice();

        $promotionalExpiryDate = $this->getPromotionalExpiryDate();
        if ($promotionalExpiryDate) {
            $currentDate = new \DateTime();
            if ($promotionalExpiryDate->format("Y-m-d") > $currentDate->format("Y-m-d")) {
                $sellPrice = $this->getPromotionalPrice();
            }
        }

        return $sellPrice;
    }

    public function getSellPrice(bool $withCommission = true): ?float
    {
        $sellPrice = $this->getUnitPrice();

        $promotionalExpiryDate = $this->getPromotionalExpiryDate();
        if ($promotionalExpiryDate) {
            $currentDate = new \DateTime();
            if ($promotionalExpiryDate->format("Y-m-d") > $currentDate->format("Y-m-d")) {
                $sellPrice = $this->getPromotionalPrice();
            }
        }

        if ($withCommission) {
            $commissionPercentage = $this->getProduct()->getVendor()?->getCommissionPercentage() ?? 0;

            if ($commissionPercentage > 0) {
                $commission = ($sellPrice * $commissionPercentage) / 100;
                $sellPrice = $sellPrice + $commission;
            }
        }

        return $sellPrice;
    }

    public function hasPromotion(): bool
    {
        if ($this->getPromotionalExpiryDate() != null) {
            $promotionalExpiryDate = $this->getPromotionalExpiryDate();
            $currentDate = new \DateTime();
            if ($promotionalExpiryDate->format("Y-m-d") > $currentDate->format("Y-m-d") and $this->getPromotionalPrice() > 0) {
                return true;
            }
        }

        return false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getPromotionalPrice(): ?float
    {
        return $this->promotionalPrice;
    }

    public function setPromotionalPrice(?float $promotionalPrice): self
    {
        $this->promotionalPrice = $promotionalPrice;

        return $this;
    }

    public function getPromotionalExpiryDate(): ?\DateTimeInterface
    {
        return $this->promotionalExpiryDate;
    }

    public function setPromotionalExpiryDate(?\DateTimeInterface $promotionalExpiryDate): self
    {
        $this->promotionalExpiryDate = $promotionalExpiryDate;

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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getVariantOptionIds(): ?string
    {
        return $this->variantOptionIds;
    }

    public function setVariantOptionIds(?string $variantOptionIds): self
    {
        $this->variantOptionIds = $variantOptionIds;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getLength(): ?float
    {
        return $this->length;
    }

    public function setLength(?float $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function getWidth(): ?float
    {
        return $this->width;
    }

    public function setWidth(?float $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(?float $height): static
    {
        $this->height = $height;

        return $this;
    }

}
