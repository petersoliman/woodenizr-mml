<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\ProductBulkGenerate;
use App\ProductBundle\Enum\ProductBulkGenerateTypeEnum;
use App\ProductBundle\Form\ProductBulkGenerateType;
use App\ProductBundle\Form\Filter\ProductBulkGenerateFilterType;
use App\ProductBundle\Repository\ProductBulkGenerateRepository;
use App\ProductBundle\Service\ProductBulkGenerateService;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Lib\Paginator;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ProductBulkGenerate controller.
 *
 * @Route("/admin/product-bulk-generate")
 */
class ProductBulkGenerateController extends AbstractController
{
    private UserService $userService;

    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        parent::__construct($em);
        $this->userService = $userService;
    }

    /**
     * Lists all ProductBulkGenerate entities.
     *
     * @Route("/{page}", requirements={"page" = "\d+"}, name="product_bulk_generate_index", methods={"GET"})
     */
    public function index(
        Request $request,
        ProductBulkGenerateRepository $productBulkGenerateRepository,
        ProductBulkGenerateService $productBulkGenerateService,
        $page = 1
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $filterForm = $this->createForm(ProductBulkGenerateFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productBulkGenerateService->collectSearchData($filterForm);

        $count = $productBulkGenerateRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 25);
        $bulkGenerateJobs = $productBulkGenerateRepository->filter(
            $search,
            false,
            $paginator->getLimitStart(),
            $paginator->getPageLimit()
        );

        $statistics = $productBulkGenerateRepository->getStatistics();
        $performanceStats = $productBulkGenerateRepository->getPerformanceStats();

        return $this->render('product/admin/productBulkGenerate/index.html.twig', [
            'search' => $search,
            'filter_form' => $filterForm->createView(),
            'bulk_generate_jobs' => $bulkGenerateJobs,
            'paginator' => $paginator->getPagination(),
            'statistics' => $statistics,
            'performance_stats' => $performanceStats,
        ]);
    }

    /**
     * Creates a new ProductBulkGenerate entity.
     *
     * @Route("/new", name="product_bulk_generate_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $productBulkGenerate = new ProductBulkGenerate();
        // Start date will be calculated based on the selected time option
        // Status, processedCount, errorCount, and totalRecommendations are set to defaults in entity
        // No need to set them manually as they have proper default values
        
        // Set current admin from session (only if it's a proper User entity)
        $currentUser = $this->userService->getUser();
        if ($currentUser && $currentUser instanceof \App\UserBundle\Entity\User) {
            $productBulkGenerate->setAdmin($currentUser);
        }

        $form = $this->createForm(ProductBulkGenerateType::class, $productBulkGenerate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calculate start date based on selected time option
            $startDate = $this->calculateStartDate($productBulkGenerate->getStartTimeOption(), $productBulkGenerate->getCustomStartTime());
            $productBulkGenerate->setStartDate($startDate);
            
            // Ensure admin is set from current session user
            $currentUser = $this->userService->getUser();
            if ($currentUser && $currentUser instanceof \App\UserBundle\Entity\User) {
                $productBulkGenerate->setAdmin($currentUser);
            }
            
            $productBulkGenerate->setCreator($this->userService->getUserName());
            $productBulkGenerate->setModifiedBy($this->userService->getUserName());

            $this->em()->persist($productBulkGenerate);
            $this->em()->flush();

            // Check if user wants to start immediately
            if ($request->request->get('start_immediately')) {
                try {
                    // Start the job immediately
                    $service = $this->container->get('app.product_bulk_generate.service');
                    $service->startJob($productBulkGenerate);
                    
                    $this->addFlash('success', 'Product bulk generate job created and started successfully.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Job created successfully but failed to start: ' . $e->getMessage());
                }
            } else {
                $this->addFlash('success', 'Product bulk generate job created successfully.');
            }

            return $this->redirectToRoute('product_bulk_generate_show', ['id' => $productBulkGenerate->getId()]);
        }

        return $this->render('product/admin/productBulkGenerate/new.html.twig', [
            'product_bulk_generate' => $productBulkGenerate,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Calculate start date based on selected time option
     */
    private function calculateStartDate(string $timeOption, ?\DateTimeInterface $customTime): \DateTimeInterface
    {
        $now = new \DateTime();
        
        switch ($timeOption) {
            case 'now':
                return $now;
            case '5min':
                return (clone $now)->add(new \DateInterval('PT5M'));
            case '15min':
                return (clone $now)->add(new \DateInterval('PT15M'));
            case '30min':
                return (clone $now)->add(new \DateInterval('PT30M'));
            case '1hour':
                return (clone $now)->add(new \DateInterval('PT1H'));
            case '2hours':
                return (clone $now)->add(new \DateInterval('PT2H'));
            case '4hours':
                return (clone $now)->add(new \DateInterval('PT4H'));
            case '8hours':
                return (clone $now)->add(new \DateInterval('PT8H'));
            case 'tomorrow_9am':
                $tomorrow = (clone $now)->add(new \DateInterval('PT1D'));
                return $tomorrow->setTime(9, 0, 0);
            case 'tomorrow_2pm':
                $tomorrow = (clone $now)->add(new \DateInterval('PT1D'));
                return $tomorrow->setTime(14, 0, 0);
            case 'custom':
                return $customTime ?? $now;
            default:
                return $now;
        }
    }

    /**
     * Shows a ProductBulkGenerate entity.
     *
     * @Route("/{id}/show", name="product_bulk_generate_show", methods={"GET"})
     */
    public function show(ProductBulkGenerate $productBulkGenerate, ProductBulkGenerateService $service): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($productBulkGenerate->isDeleted()) {
            throw $this->createNotFoundException('Product bulk generate job not found.');
        }

        $jobSummary = $service->getJobStatusSummary($productBulkGenerate);
        $estimatedCompletion = $service->getEstimatedCompletion($productBulkGenerate);

        return $this->render('product/admin/productBulkGenerate/show.html.twig', [
            'product_bulk_generate' => $productBulkGenerate,
            'job_summary' => $jobSummary,
            'estimated_completion' => $estimatedCompletion,
            'service' => $service,
        ]);
    }

    /**
     * Displays a form to edit an existing ProductBulkGenerate entity.
     *
     * @Route("/{id}/edit", name="product_bulk_generate_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, ProductBulkGenerate $productBulkGenerate): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($productBulkGenerate->isDeleted()) {
            throw $this->createNotFoundException('Product bulk generate job not found.');
        }

        $form = $this->createForm(ProductBulkGenerateType::class, $productBulkGenerate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productBulkGenerate->setModifiedBy($this->userService->getUserName());

            $this->em()->flush();

            $this->addFlash('success', 'Product bulk generate job updated successfully.');

            return $this->redirectToRoute('product_bulk_generate_show', ['id' => $productBulkGenerate->getId()]);
        }

        return $this->render('product/admin/productBulkGenerate/edit.html.twig', [
            'product_bulk_generate' => $productBulkGenerate,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a ProductBulkGenerate entity.
     *
     * @Route("/{id}/delete", name="product_bulk_generate_delete", methods={"POST"})
     */
    public function delete(Request $request, ProductBulkGenerate $productBulkGenerate): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($productBulkGenerate->isDeleted()) {
            throw $this->createNotFoundException('Product bulk generate job not found.');
        }

        if ($this->isCsrfTokenValid('delete' . $productBulkGenerate->getId(), $request->request->get('_token'))) {
            $productBulkGenerate->setDeleted(new \DateTime());
            $productBulkGenerate->setDeletedBy($this->userService->getUserName());
            $this->em()->flush();

            $this->addFlash('success', 'Product bulk generate job deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('product_bulk_generate_index');
    }

    /**
     * Start a bulk generate job.
     *
     * @Route("/{id}/start", name="product_bulk_generate_start", methods={"POST"})
     */
    public function startJob(Request $request, ProductBulkGenerate $productBulkGenerate, ProductBulkGenerateService $service): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($productBulkGenerate->isDeleted()) {
            return $this->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        $validation = $service->canStartJob($productBulkGenerate);
        if (!$validation['can_start']) {
            return $this->json([
                'success' => false, 
                'message' => 'Cannot start job: ' . implode(', ', $validation['errors'])
            ], 400);
        }

        try {
            $service->startJob($productBulkGenerate);
            
            return $this->json([
                'success' => true,
                'message' => 'Job started successfully.',
                'status' => $productBulkGenerate->getStatus(),
                'statusBadgeClass' => $productBulkGenerate->getStatusBadgeClass()
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error starting job: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Complete a bulk generate job.
     *
     * @Route("/{id}/complete", name="product_bulk_generate_complete", methods={"POST"})
     */
    public function completeJob(Request $request, ProductBulkGenerate $productBulkGenerate, ProductBulkGenerateService $service): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($productBulkGenerate->isDeleted()) {
            return $this->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        $validation = $service->canCompleteJob($productBulkGenerate);
        if (!$validation['can_complete']) {
            return $this->json([
                'success' => false, 
                'message' => 'Cannot complete job: ' . implode(', ', $validation['errors'])
            ], 400);
        }

        try {
            $service->completeJob($productBulkGenerate);
            
            return $this->json([
                'success' => true,
                'message' => 'Job completed successfully.',
                'status' => $productBulkGenerate->getStatus(),
                'statusBadgeClass' => $productBulkGenerate->getStatusBadgeClass()
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error completing job: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fail a bulk generate job.
     *
     * @Route("/{id}/fail", name="product_bulk_generate_fail", methods={"POST"})
     */
    public function failJob(Request $request, ProductBulkGenerate $productBulkGenerate, ProductBulkGenerateService $service): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($productBulkGenerate->isDeleted()) {
            return $this->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        $reason = $request->request->get('reason', 'Manual failure');

        try {
            $service->failJob($productBulkGenerate, $reason);
            
            return $this->json([
                'success' => true,
                'message' => 'Job marked as failed.',
                'status' => $productBulkGenerate->getStatus(),
                'statusBadgeClass' => $productBulkGenerate->getStatusBadgeClass()
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error failing job: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get job progress.
     *
     * @Route("/{id}/progress", name="product_bulk_generate_progress", methods={"GET"})
     */
    public function getProgress(ProductBulkGenerate $productBulkGenerate, ProductBulkGenerateService $service): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($productBulkGenerate->isDeleted()) {
            return $this->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        $jobSummary = $service->getJobStatusSummary($productBulkGenerate);
        $estimatedCompletion = $service->getEstimatedCompletion($productBulkGenerate);

        return $this->json([
            'success' => true,
            'job' => $jobSummary,
            'estimated_completion' => $estimatedCompletion?->format('Y-m-d H:i:s'),
            'duration_formatted' => $service->formatDuration($productBulkGenerate->getDurationInSeconds())
        ]);
    }

    /**
     * Get statistics for dashboard.
     *
     * @Route("/statistics", name="product_bulk_generate_statistics", methods={"GET"})
     */
    public function statistics(ProductBulkGenerateRepository $productBulkGenerateRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $statistics = $productBulkGenerateRepository->getStatistics();
        $performanceStats = $productBulkGenerateRepository->getPerformanceStats();

        return $this->json([
            'statistics' => $statistics,
            'performance' => $performanceStats
        ]);
    }

    /**
     * Get latest jobs for dashboard widget.
     *
     * @Route("/latest", name="product_bulk_generate_latest", methods={"GET"})
     */
    public function latest(ProductBulkGenerateRepository $productBulkGenerateRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $latestJobs = $productBulkGenerateRepository->findLatest(10);

        $jobs = [];
        foreach ($latestJobs as $job) {
            $jobs[] = [
                'id' => $job->getId(),
                'type' => $job->getGeneratedForLabel(),
                'status' => $job->getStatus(),
                'total_recommendations' => $job->getTotalRecommendations(),
                'processed_count' => $job->getProcessedCount(),
                'success_rate' => $job->getSuccessRate(),
                'created' => $job->getCreated()->format('Y-m-d H:i:s'),
                'admin' => $job->getAdmin() ? $job->getAdmin()->getFullName() : null,
            ];
        }

        return $this->json($jobs);
    }
}




