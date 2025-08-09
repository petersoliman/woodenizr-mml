<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Entity\ProductBulkGenerate;
use App\ProductBundle\Enum\ProductBulkGenerateTypeEnum;
use App\ProductBundle\Form\Filter\ProductBulkGenerateFilterType;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\Form\FormInterface;

class ProductBulkGenerateService
{
    private EntityManagerInterface $em;
    private UserService $userService;

    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        $this->em = $em;
        $this->userService = $userService;
    }

    /**
     * Collect search data from filter form
     */
    public function collectSearchData(FormInterface $filterForm): \stdClass
    {
        $search = new \stdClass();

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();

            if (isset($data['generatedFor']) && $data['generatedFor'] !== '') {
                $search->generatedFor = $data['generatedFor'];
            }

            if (isset($data['status']) && !empty($data['status'])) {
                $search->status = $data['status'];
            }

            if (isset($data['adminId']) && $data['adminId'] !== null) {
                $search->adminId = $data['adminId'];
            }

            if (isset($data['createdFrom']) && $data['createdFrom'] !== null) {
                $search->createdFrom = $data['createdFrom']->format('Y-m-d');
            }

            if (isset($data['createdTo']) && $data['createdTo'] !== null) {
                $search->createdTo = $data['createdTo']->format('Y-m-d');
            }

            if (isset($data['startDateFrom']) && $data['startDateFrom'] !== null) {
                $search->startDateFrom = $data['startDateFrom']->format('Y-m-d');
            }

            if (isset($data['startDateTo']) && $data['startDateTo'] !== null) {
                $search->startDateTo = $data['startDateTo']->format('Y-m-d');
            }
        }

        return $search;
    }

    /**
     * Create a new bulk generation job
     */
    public function createBulkGenerateJob(
        ProductBulkGenerateTypeEnum $type,
        int $totalRecommendations,
        ?User $admin = null,
        ?string $adminNote = null
    ): ProductBulkGenerate {
        $job = new ProductBulkGenerate();
        $job->setGeneratedForEnum($type);
        $job->setTotalRecommendations($totalRecommendations);
        $job->setStartDate(new \DateTime());
        $job->setStatus('pending');
        
        if ($admin) {
            $job->setAdmin($admin);
        } else {
            // Try to get current admin from session
            $currentUser = $this->userService->getUser();
            if ($currentUser instanceof User) {
                $job->setAdmin($currentUser);
            }
        }

        if ($adminNote) {
            $job->setAdminNote($adminNote);
        }

        $job->setCreator($this->userService->getUserName());
        $job->setModifiedBy($this->userService->getUserName());

        $this->em->persist($job);
        $this->em->flush();

        return $job;
    }

    /**
     * Start a bulk generation job
     */
    public function startJob(ProductBulkGenerate $job): void
    {
        $job->setStatus('running');
        $job->setStartDate(new \DateTime());
        $job->setModifiedBy($this->userService->getUserName());
        
        $this->em->flush();
    }

    /**
     * Complete a bulk generation job
     */
    public function completeJob(ProductBulkGenerate $job): void
    {
        $job->setStatus('completed');
        $job->setEndDate(new \DateTime());
        $job->setModifiedBy($this->userService->getUserName());
        
        $this->em->flush();
    }

    /**
     * Fail a bulk generation job
     */
    public function failJob(ProductBulkGenerate $job, ?string $error = null): void
    {
        $job->setStatus('failed');
        $job->setEndDate(new \DateTime());
        $job->setModifiedBy($this->userService->getUserName());
        
        if ($error) {
            $job->addError($error);
        }
        
        $this->em->flush();
    }

    /**
     * Update job progress
     */
    public function updateProgress(ProductBulkGenerate $job, int $processedCount, int $errorCount = 0): void
    {
        $job->setProcessedCount($processedCount);
        $job->setErrorCount($errorCount);
        $job->setModifiedBy($this->userService->getUserName());
        
        $this->em->flush();
    }

    /**
     * Add error to job
     */
    public function addJobError(ProductBulkGenerate $job, string $error): void
    {
        $job->addError($error);
        $job->setModifiedBy($this->userService->getUserName());
        
        $this->em->flush();
    }

    /**
     * Get job status summary
     */
    public function getJobStatusSummary(ProductBulkGenerate $job): array
    {
        return [
            'id' => $job->getId(),
            'type' => $job->getGeneratedForLabel(),
            'status' => $job->getStatus(),
            'progress' => [
                'total' => $job->getTotalRecommendations(),
                'processed' => $job->getProcessedCount(),
                'errors' => $job->getErrorCount(),
                'success_rate' => $job->getSuccessRate(),
                'error_rate' => $job->getErrorRate(),
            ],
            'timing' => [
                'start_date' => $job->getStartDate()?->format('Y-m-d H:i:s'),
                'end_date' => $job->getEndDate()?->format('Y-m-d H:i:s'),
                'duration_seconds' => $job->getDurationInSeconds(),
            ],
            'admin' => $job->getAdmin() ? [
                'id' => $job->getAdmin()->getId(),
                'name' => $job->getAdmin()->getFullName(),
                'email' => $job->getAdmin()->getEmail(),
            ] : null,
            'note' => $job->getAdminNote(),
        ];
    }

    /**
     * Validate job can be started
     */
    public function canStartJob(ProductBulkGenerate $job): array
    {
        $errors = [];

        if ($job->getStatus() !== 'pending') {
            $errors[] = 'Job must be in pending status to start';
        }

        if ($job->getTotalRecommendations() <= 0) {
            $errors[] = 'Total recommendations must be greater than 0';
        }

        if (!$job->getAdmin()) {
            $errors[] = 'Job must have an assigned admin';
        }

        return [
            'can_start' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate job can be completed
     */
    public function canCompleteJob(ProductBulkGenerate $job): array
    {
        $errors = [];

        if ($job->getStatus() !== 'running') {
            $errors[] = 'Job must be in running status to complete';
        }

        return [
            'can_complete' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get estimated completion time
     */
    public function getEstimatedCompletion(ProductBulkGenerate $job): ?\DateTime
    {
        if ($job->getStatus() !== 'running' || $job->getProcessedCount() === 0) {
            return null;
        }

        $startTime = $job->getStartDate();
        if (!$startTime) {
            return null;
        }

        $now = new \DateTime();
        $elapsedSeconds = $now->getTimestamp() - $startTime->getTimestamp();
        $processedCount = $job->getProcessedCount();
        $totalCount = $job->getTotalRecommendations();
        
        if ($processedCount >= $totalCount) {
            return $now; // Should be completed now
        }

        $secondsPerItem = $elapsedSeconds / $processedCount;
        $remainingItems = $totalCount - $processedCount;
        $estimatedRemainingSeconds = $remainingItems * $secondsPerItem;

        $estimatedCompletion = clone $now;
        $estimatedCompletion->add(new \DateInterval('PT' . (int)$estimatedRemainingSeconds . 'S'));

        return $estimatedCompletion;
    }

    /**
     * Format duration for display
     */
    public function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '--';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $secs);
        } else {
            return sprintf('%ds', $secs);
        }
    }
}




