<?php

namespace App\ProductBundle\Entity;

use App\ProductBundle\Repository\BulkJobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BulkJobRepository::class)]
#[ORM\Table(name: 'bulk_jobs')]
class BulkJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $jobId = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column]
    private ?int $totalProducts = null;

    #[ORM\Column]
    private ?int $processedCount = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $results = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function setJobId(string $jobId): static
    {
        $this->jobId = $jobId;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getTotalProducts(): ?int
    {
        return $this->totalProducts;
    }

    public function setTotalProducts(int $totalProducts): static
    {
        $this->totalProducts = $totalProducts;
        return $this;
    }

    public function getProcessedCount(): ?int
    {
        return $this->processedCount;
    }

    public function setProcessedCount(int $processedCount): static
    {
        $this->processedCount = $processedCount;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): static
    {
        $this->results = $results;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getProgress(): int
    {
        if ($this->totalProducts === 0) {
            return 0;
        }
        return round(($this->processedCount / $this->totalProducts) * 100);
    }

    public function getDuration(): ?int
    {
        if (!$this->startedAt) {
            return null;
        }
        
        $endTime = $this->completedAt ?? new \DateTimeImmutable();
        return $endTime->getTimestamp() - $this->startedAt->getTimestamp();
    }

    public function getFormattedDuration(): ?string
    {
        $duration = $this->getDuration();
        if ($duration === null) {
            return null;
        }
        
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }
} 