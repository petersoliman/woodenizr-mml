<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\ContentRecommendation;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Enum\ContentRecommendationStateEnum;
use App\ProductBundle\Form\ContentRecommendationType;
use App\ProductBundle\Form\Filter\ContentRecommendationFilterType;
use App\ProductBundle\Repository\ContentRecommendationRepository;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ContentRecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Lib\Paginator;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Content Recommendation controller.
 *
 * @Route("/admin/content-recommendation")
 */
class ContentRecommendationController extends AbstractController
{
    private UserService $userService;

    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        parent::__construct($em);
        $this->userService = $userService;
    }

    /**
     * Lists all ContentRecommendation entities.
     *
     * @Route("/{page}", requirements={"page" = "\d+"}, name="content_recommendation_index", methods={"GET"})
     */
    public function index(
        Request $request,
        ContentRecommendationRepository $contentRecommendationRepository,
        ContentRecommendationService $contentRecommendationService,
        $page = 1
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $filterForm = $this->createForm(ContentRecommendationFilterType::class);
        $filterForm->handleRequest($request);
        $search = $contentRecommendationService->collectSearchData($filterForm);

        $count = $contentRecommendationRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 25);
        $contentRecommendations = $contentRecommendationRepository->filter(
            $search,
            false,
            $paginator->getLimitStart(),
            $paginator->getPageLimit()
        );

        $statistics = $contentRecommendationRepository->getStatistics();

        return $this->render('product/admin/contentRecommendation/index.html.twig', [
            'search' => $search,
            'filter_form' => $filterForm->createView(),
            'content_recommendations' => $contentRecommendations,
            'paginator' => $paginator->getPagination(),
            'statistics' => $statistics,
        ]);
    }

    /**
     * Creates a new ContentRecommendation entity.
     *
     * @Route("/new", name="content_recommendation_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $contentRecommendation = new ContentRecommendation();
        $contentRecommendation->setState(3); // NEW state

        $form = $this->createForm(ContentRecommendationType::class, $contentRecommendation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle JSON data
            $recommendedJson = $form->get('recommendedJson')->getData();
            if (!empty($recommendedJson)) {
                $decodedJson = json_decode($recommendedJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $contentRecommendation->setRecommended($decodedJson);
                } else {
                    $this->addFlash('error', 'Invalid JSON format in recommended content.');
                    return $this->render('product/admin/contentRecommendation/new.html.twig', [
                        'content_recommendation' => $contentRecommendation,
                        'form' => $form->createView(),
                    ]);
                }
            }

            $contentRecommendation->setCreator($this->userService->getUserName());
            $contentRecommendation->setModifiedBy($this->userService->getUserName());

            $this->em()->persist($contentRecommendation);
            $this->em()->flush();

            $this->addFlash('success', 'Content recommendation created successfully.');

            return $this->redirectToRoute('content_recommendation_show', ['id' => $contentRecommendation->getId()]);
        }

        return $this->render('product/admin/contentRecommendation/new.html.twig', [
            'content_recommendation' => $contentRecommendation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Shows a ContentRecommendation entity.
     *
     * @Route("/{id}/show", name="content_recommendation_show", methods={"GET"})
     */
    public function show(ContentRecommendation $contentRecommendation): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($contentRecommendation->isDeleted()) {
            throw $this->createNotFoundException('Content recommendation not found.');
        }

        return $this->render('product/admin/contentRecommendation/show.html.twig', [
            'content_recommendation' => $contentRecommendation,
        ]);
    }

    /**
     * Displays a form to edit an existing ContentRecommendation entity.
     *
     * @Route("/{id}/edit", name="content_recommendation_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, ContentRecommendation $contentRecommendation): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($contentRecommendation->isDeleted()) {
            throw $this->createNotFoundException('Content recommendation not found.');
        }

        $form = $this->createForm(ContentRecommendationType::class, $contentRecommendation);
        
        // Set the JSON data for display
        if ($contentRecommendation->getRecommended()) {
            $form->get('recommendedJson')->setData(json_encode($contentRecommendation->getRecommended(), JSON_PRETTY_PRINT));
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle JSON data
            $recommendedJson = $form->get('recommendedJson')->getData();
            if (!empty($recommendedJson)) {
                $decodedJson = json_decode($recommendedJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $contentRecommendation->setRecommended($decodedJson);
                } else {
                    $this->addFlash('error', 'Invalid JSON format in recommended content.');
                    return $this->render('product/admin/contentRecommendation/edit.html.twig', [
                        'content_recommendation' => $contentRecommendation,
                        'form' => $form->createView(),
                    ]);
                }
            } else {
                $contentRecommendation->setRecommended(null);
            }

            $contentRecommendation->setModifiedBy($this->userService->getUserName());

            $this->em()->flush();

            $this->addFlash('success', 'Content recommendation updated successfully.');

            return $this->redirectToRoute('content_recommendation_show', ['id' => $contentRecommendation->getId()]);
        }

        return $this->render('product/admin/contentRecommendation/edit.html.twig', [
            'content_recommendation' => $contentRecommendation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a ContentRecommendation entity.
     *
     * @Route("/{id}/delete", name="content_recommendation_delete", methods={"POST"})
     */
    public function delete(Request $request, ContentRecommendation $contentRecommendation): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($contentRecommendation->isDeleted()) {
            throw $this->createNotFoundException('Content recommendation not found.');
        }

        if ($this->isCsrfTokenValid('delete' . $contentRecommendation->getId(), $request->request->get('_token'))) {
            $contentRecommendation->setDeleted(new \DateTime());
            $contentRecommendation->setDeletedBy($this->userService->getUserName());
            $this->em()->flush();

            $this->addFlash('success', 'Content recommendation deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('content_recommendation_index');
    }

    /**
     * Update state of ContentRecommendation entity.
     *
     * @Route("/{id}/update-state", name="content_recommendation_update_state", methods={"POST"})
     */
    public function updateState(Request $request, ContentRecommendation $contentRecommendation): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if ($contentRecommendation->isDeleted()) {
            return $this->json(['success' => false, 'message' => 'Content recommendation not found.'], 404);
        }

        $state = $request->request->get('state');
        if (!Validate::not_null($state) || !is_numeric($state)) {
            return $this->json(['success' => false, 'message' => 'Invalid state value.'], 400);
        }

        $stateInt = (int) $state;
        $validStates = array_map(fn($enum) => $enum->value, ContentRecommendationStateEnum::getAll());
        
        if (!in_array($stateInt, $validStates)) {
            return $this->json(['success' => false, 'message' => 'Invalid state value.'], 400);
        }

        $contentRecommendation->setState($stateInt);
        $contentRecommendation->setModifiedBy($this->userService->getUserName());
        $this->em()->flush();

        return $this->json([
            'success' => true,
            'message' => 'State updated successfully.',
            'state' => $contentRecommendation->getStateLabel(),
            'badgeClass' => $contentRecommendation->getStateBadgeClass()
        ]);
    }

    /**
     * Get statistics for dashboard.
     *
     * @Route("/statistics", name="content_recommendation_statistics", methods={"GET"})
     */
    public function statistics(ContentRecommendationRepository $contentRecommendationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $statistics = $contentRecommendationRepository->getStatistics();

        return $this->json($statistics);
    }

    /**
     * Generate content recommendations for all products using API
     *
     * This function loops through all products that have either a title (name) or SKU (model number),
     * makes API requests to generate recommendations, and saves the responses to the database.
     *
     * @Route("/generate-recommendations", name="content_recommendation_generate", methods={"POST"})
     */
    public function generateRecommendations(
        ProductRepository $productRepository,
        ContentRecommendationRepository $contentRecommendationRepository,
        Request $request
    ): JsonResponse {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        try {
            // Get API endpoint from request or use default (Product Studio Intelligence API)
            $apiEndpoint = $request->request->get('api_endpoint', 'http://localhost:3000/api/products/intelligence');
            $batchSize = (int) $request->request->get('batch_size', 50); // Process in batches
            $offset = (int) $request->request->get('offset', 0); // For pagination
            
            // Query products that have either title or SKU
            $queryBuilder = $productRepository->createQueryBuilder('p')
                ->where('p.deleted IS NULL')
                ->andWhere('p.publish = :publish')
                ->andWhere('(p.title IS NOT NULL AND p.title != :empty) OR (p.sku IS NOT NULL AND p.sku != :empty)')
                ->setParameter('publish', true)
                ->setParameter('empty', '')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->orderBy('p.id', 'ASC');

            $products = $queryBuilder->getQuery()->getResult();

            if (empty($products)) {
                return $this->json([
                    'success' => true,
                    'message' => 'No more products to process.',
                    'processed' => 0,
                    'total' => 0
                ]);
            }

            $processed = 0;
            $errors = [];
            $currentUser = $this->userService->getUser();

            foreach ($products as $product) {
                try {
                    // Check if recommendation already exists for this product
                    $existingRecommendation = $contentRecommendationRepository->findOneBy([
                        'product' => $product,
                        'deleted' => null
                    ]);

                    if ($existingRecommendation) {
                        continue; // Skip if recommendation already exists
                    }

                    // Prepare data for Product Studio Intelligence API request
                    // Using exact format from your example
                    $requestData = [
                        'name' => $product->getTitle() ?: 'Unknown Product',
                        'model_number' => $product->getSku() ?: 'N/A',
                        'brand' => $product->getBrand() ? $product->getBrand()->getTitle() : 'Unknown Brand',
                        'category' => $product->getCategory() ? $product->getCategory()->getTitle() : 'Uncategorized'
                    ];

                    // Make API request
                    $apiResponse = $this->makeApiRequest($apiEndpoint, $requestData);

                    // Create new ContentRecommendation entity regardless of API success (for testing)
                    $contentRecommendation = new ContentRecommendation();
                    $contentRecommendation->setProduct($product);
                    $contentRecommendation->setState(ContentRecommendationStateEnum::NEW->value); // State = 1 (NEW)
                    
                    if ($apiResponse['success']) {
                        // API succeeded - save real response
                        $contentRecommendation->setRecommended($apiResponse['data']); 
                        $contentRecommendation->setNotes('Auto-generated from API');
                    } else {
                        // API failed - save mock data for testing + error info
                        $mockData = [
                            'status' => 'mock_data',
                            'message' => 'API call failed - using test data',
                            'original_request' => $requestData,
                            'api_error' => $apiResponse['error'],
                            'mock_response' => [
                                'name' => $requestData['name'] . ' - AI Enhanced',
                                'seo_title' => 'SEO: ' . $requestData['name'],
                                'brief' => 'AI-generated brief for ' . $requestData['name'],
                                'description' => 'Enhanced description for ' . $requestData['name'] . ' with SEO optimization.',
                                'seo_keywords' => [$requestData['name'], $requestData['brand'], $requestData['category']],
                                'meta_description' => 'Premium ' . $requestData['name'] . ' from ' . $requestData['brand'],
                                'gallery_images' => ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'],
                                'specifications' => ['material' => 'Premium wood', 'finish' => 'Natural']
                            ]
                        ];
                        $contentRecommendation->setRecommended($mockData);
                        $contentRecommendation->setNotes('TEST: API failed - ' . $apiResponse['error']);
                        
                        // Still track the error for reporting
                        $errors[] = [
                            'product_id' => $product->getId(),
                            'title' => $product->getTitle(),
                            'error' => $apiResponse['error'],
                            'note' => 'Saved with mock data for testing'
                        ];
                    }
                    
                    // Set audit fields
                    if ($currentUser) {
                        $userName = 'Unknown User';
                        if (method_exists($currentUser, 'getFullName') && $currentUser->getFullName()) {
                            $userName = $currentUser->getFullName();
                        } elseif (method_exists($currentUser, 'getEmail') && $currentUser->getEmail()) {
                            $userName = $currentUser->getEmail();
                        } elseif (method_exists($currentUser, 'getUserIdentifier')) {
                            $userName = $currentUser->getUserIdentifier();
                        }
                        $contentRecommendation->setCreator($userName);
                        $contentRecommendation->setModifiedBy($userName);
                    } else {
                        $contentRecommendation->setCreator('System');
                        $contentRecommendation->setModifiedBy('System');
                    }
                    
                    // Created/modified timestamps will be set automatically by DateTimeTrait
                    $this->em->persist($contentRecommendation);
                    $processed++;

                } catch (\Exception $e) {
                    $errors[] = [
                        'product_id' => $product->getId(),
                        'title' => $product->getTitle(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Save all changes to database
            if ($processed > 0) {
                $this->em->flush();
            }

            // Get total count for progress tracking
            $totalCount = $productRepository->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.deleted IS NULL')
                ->andWhere('p.publish = :publish')
                ->andWhere('(p.title IS NOT NULL AND p.title != :empty) OR (p.sku IS NOT NULL AND p.sku != :empty)')
                ->setParameter('publish', true)
                ->setParameter('empty', '')
                ->getQuery()
                ->getSingleScalarResult();

            return $this->json([
                'success' => true,
                'message' => "Processed {$processed} products successfully.",
                'processed' => $processed,
                'errors' => $errors,
                'total' => (int) $totalCount,
                'next_offset' => $offset + $batchSize,
                'has_more' => ($offset + $batchSize) < $totalCount
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error generating recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make API request using cURL
     *
     * @param string $endpoint API endpoint URL
     * @param array $data Request data
     * @return array Response with success status and data/error
     */
    private function makeApiRequest(string $endpoint, array $data): array
    {
        try {
            // Initialize cURL
            $ch = curl_init();

            // Prepare JSON payload
            $jsonData = json_encode($data);

            // Set cURL options
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'User-Agent: Woodenizr-ContentRecommendation/1.0'
                ],
                CURLOPT_TIMEOUT => 30, // 30 seconds timeout
                CURLOPT_CONNECTTIMEOUT => 10, // 10 seconds connection timeout
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => false, // For development - should be true in production
                CURLOPT_SSL_VERIFYHOST => false  // For development - should be true in production
            ]);

            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            // Check for cURL errors
            if ($response === false || !empty($error)) {
                return [
                    'success' => false,
                    'error' => 'cURL Error: ' . $error
                ];
            }

            // Check HTTP status code
            if ($httpCode < 200 || $httpCode >= 300) {
                return [
                    'success' => false,
                    'error' => "HTTP Error: {$httpCode}"
                ];
            }

            // Decode JSON response
            $decodedResponse = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response: ' . json_last_error_msg()
                ];
            }

            return [
                'success' => true,
                'data' => $decodedResponse
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}
