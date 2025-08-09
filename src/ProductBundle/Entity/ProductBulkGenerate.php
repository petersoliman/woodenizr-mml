<?php

namespace App\ProductBundle\Entity;

use App\ProductBundle\Enum\ProductBulkGenerateTypeEnum;
use App\ProductBundle\Repository\ProductBulkGenerateRepository;
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
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="product_bulk_generate")
 * @ORM\Entity(repositoryClass=ProductBulkGenerateRepository::class)
 * @Gedmo\Loggable
 */
class ProductBulkGenerate implements DateTimeInterface, UUIDInterface
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
     * @Gedmo\Versioned
     * @ORM\Column(name="generated_for", type="integer", nullable=false)
     */
    private int $generatedFor = 3; // Default to General

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    private ?\DateTimeInterface $startDate = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $endDate = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="admin_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private ?User $admin = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="total_recommendations", type="integer", nullable=false, options={"default": 0})
     */
    private int $totalRecommendations = 0;

    /**
     * @ORM\Column(name="admin_note", type="text", nullable=true)
     */
    private ?string $adminNote = null;

    /**
     * @ORM\Column(name="status", type="string", length=20, nullable=false, options={"default": "pending"})
     */
    private string $status = 'pending';

    /**
     * @ORM\Column(name="processed_count", type="integer", nullable=false, options={"default": 0})
     */
    private int $processedCount = 0;

    /**
     * @ORM\Column(name="error_count", type="integer", nullable=false, options={"default": 0})
     */
    private int $errorCount = 0;

    /**
     * @ORM\Column(name="error_log", type="json", nullable=true)
     */
    private ?array $errorLog = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGeneratedFor(): int
    {
        return $this->generatedFor;
    }

    public function setGeneratedFor(int $generatedFor): self
    {
        $this->generatedFor = $generatedFor;

        return $this;
    }

    public function getGeneratedForEnum(): ProductBulkGenerateTypeEnum
    {
        return ProductBulkGenerateTypeEnum::from($this->generatedFor);
    }

    public function setGeneratedForEnum(ProductBulkGenerateTypeEnum $generatedFor): self
    {
        $this->generatedFor = $generatedFor->value;

        return $this;
    }

    public function getGeneratedForLabel(): string
    {
        return $this->getGeneratedForEnum()->getLabel();
    }

    public function getGeneratedForDescription(): string
    {
        return $this->getGeneratedForEnum()->getDescription();
    }

    public function getGeneratedForBadgeClass(): string
    {
        return $this->getGeneratedForEnum()->getBadgeClass();
    }

    public function getGeneratedForIcon(): string
    {
        return $this->getGeneratedForEnum()->getIcon();
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getTotalRecommendations(): int
    {
        return $this->totalRecommendations;
    }

    public function setTotalRecommendations(int $totalRecommendations): self
    {
        $this->totalRecommendations = $totalRecommendations;

        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): self
    {
        $this->adminNote = $adminNote;

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

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function setProcessedCount(int $processedCount): self
    {
        $this->processedCount = $processedCount;

        return $this;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function setErrorCount(int $errorCount): self
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    public function getErrorLog(): ?array
    {
        return $this->errorLog;
    }

    public function setErrorLog(?array $errorLog): self
    {
        $this->errorLog = $errorLog;

        return $this;
    }

    public function addError(string $error): self
    {
        if ($this->errorLog === null) {
            $this->errorLog = [];
        }
        
        $this->errorLog[] = [
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'error' => $error
        ];
        
        $this->errorCount++;

        return $this;
    }

    public function getSuccessRate(): float
    {
        if ($this->totalRecommendations === 0) {
            return 0.0;
        }

        return round(($this->processedCount / $this->totalRecommendations) * 100, 2);
    }

    public function getErrorRate(): float
    {
        if ($this->totalRecommendations === 0) {
            return 0.0;
        }

        return round(($this->errorCount / $this->totalRecommendations) * 100, 2);
    }

    public function isDurationSet(): bool
    {
        return $this->startDate && $this->endDate;
    }

    public function getDuration(): ?\DateInterval
    {
        if (!$this->isDurationSet()) {
            return null;
        }

        return $this->startDate->diff($this->endDate);
    }

    public function getDurationInSeconds(): ?int
    {
        if (!$this->isDurationSet()) {
            return null;
        }

        return $this->endDate->getTimestamp() - $this->startDate->getTimestamp();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'completed' => 'label-success',
            'failed' => 'label-danger',
            'running' => 'label-warning',
            'pending' => 'label-info',
            default => 'label-default',
        };
    }

    public function __toString(): string
    {
        return sprintf(
            'Bulk Generate #%d (%s) - %s',
            $this->id ?? 0,
            $this->getGeneratedForLabel(),
            $this->status
        );
    }
}




