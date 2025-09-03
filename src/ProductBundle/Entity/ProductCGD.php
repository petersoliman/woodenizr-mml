<?php

namespace App\ProductBundle\Entity;

use App\ProductBundle\Entity\Brand;
use App\ProductBundle\Entity\Category;
use App\UserBundle\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Interfaces\UUIDInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\UuidTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;

/**
 * ProductCGD Entity - Temporary storage for Category Generate Data products awaiting admin approval
 * Created by cursor on 2025-01-27 15:45:00 to implement approval workflow for CGData products
 * 
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="product_cgd")
 * @ORM\Entity(repositoryClass=App\ProductBundle\Repository\ProductCGDRepository::class)
 * @Gedmo\Loggable
 */
class ProductCGD implements DateTimeInterface, UUIDInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait,
        UuidTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private Category $category;

    /**
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Brand")
     * @ORM\JoinColumn(name="brand_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private ?Brand $brand = null;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(name="images", type="json", nullable=true)
     */
    private ?array $images = null;

    /**
     * @ORM\Column(name="metadata", type="json", nullable=true)
     */
    private ?array $metadata = null;

    /**
     * @ORM\Column(name="sku", type="string", length=255, nullable=true)
     */
    private ?string $sku = null;

    /**
     * @ORM\Column(name="price", type="decimal", precision=10, scale=2, nullable=true)
     */
    private ?string $price = null;

    /**
     * @ORM\Column(name="url", type="string", length=500, nullable=true)
     */
    private ?string $url = null;

    /**
     * @ORM\Column(name="technical_specs", type="json", nullable=true)
     */
    private ?array $technicalSpecs = null;

    /**
     * @ORM\Column(name="status", type="string", length=20, nullable=false, options={"default": "pending"})
     */
    private string $status = 'pending';

    /**
     * @ORM\ManyToOne(targetEntity="App\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private ?User $createdBy = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="approved_by", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private ?User $approvedBy = null;

    /**
     * @ORM\Column(name="approved_at", type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $approvedAt = null;

    /**
     * @ORM\Column(name="rejection_reason", type="text", nullable=true)
     */
    private ?string $rejectionReason = null;

    /**
     * @ORM\Column(name="batch_id", type="string", length=255, nullable=true)
     */
    private ?string $batchId = null;

    /**
     * @ORM\Column(name="external_data", type="json", nullable=true)
     */
    private ?array $externalData = null;

    /**
     * @ORM\Column(name="product_id", type="integer", nullable=true)
     */
    private ?int $productId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->category ? $this->category->getId() : null;
    }

    public function getBrandId(): ?int
    {
        return $this->brand ? $this->brand->getId() : null;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): self
    {
        $this->images = $images;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getTechnicalSpecs(): ?array
    {
        return $this->technicalSpecs;
    }

    public function setTechnicalSpecs(?array $technicalSpecs): self
    {
        $this->technicalSpecs = $technicalSpecs;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): self
    {
        $this->approvedBy = $approvedBy;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeInterface $approvedAt): self
    {
        $this->approvedAt = $approvedAt;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    public function setBatchId(?string $batchId): self
    {
        $this->batchId = $batchId;
        return $this;
    }

    public function getExternalData(): ?array
    {
        return $this->externalData;
    }

    public function setExternalData(?array $externalData): self
    {
        $this->externalData = $externalData;
        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    // Status helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'processing' => 'Processing',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'label-warning',
            'approved' => 'label-success',
            'rejected' => 'label-danger',
            'processing' => 'label-info',
            default => 'label-default'
        };
    }
}
