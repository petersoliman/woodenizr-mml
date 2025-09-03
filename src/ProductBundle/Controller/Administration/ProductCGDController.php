<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\ProductCGD;
use App\ProductBundle\Service\ProductCGDService;
use App\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ProductCGD Controller - Manages Category Generate Data approval workflow
 * Created by cursor on 2025-01-27 15:49:00 to handle admin approval of CGData products
 * 
 * @Route("/admin/product-cgd")
 */
class ProductCGDController extends AbstractController
{


    /**
     * Unique test page for ProductCGD to test checkboxes without conflicts
     * 
     * @Route("/unique-test", name="product_cgd_unique_test", methods={"GET"})
     */
    public function uniqueTest(Request $request): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        return $this->render('product/admin/productCGD/unique_test.html.twig');
    }

    /**
     * Debug page for ProductCGD to test template rendering
     * 
     * @Route("/debug", name="product_cgd_debug", methods={"GET"})
     */
    public function debug(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $page = max(1, (int) $request->query->get('page', 1));
        $status = $request->query->get('status', 'pending');
        $search = $request->query->get('search', '');

        // Debug: Check what the service returns
        try {
            if ($search) {
                $productCGDs = $productCGDService->search($search, $page);
            } else {
                $productCGDs = $productCGDService->getByStatus($status, $page);
            }

            $statistics = $productCGDService->getStatistics();
            
            //
            
        } catch (\Exception $e) {
            //
            $productCGDs = [];
            $statistics = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
        }

        return $this->render('product/admin/productCGD/debug.html.twig', [
            'product_cgds' => $productCGDs,
            'statistics' => $statistics,
            'current_status' => $status,
            'current_page' => $page,
            'search_query' => $search
        ]);
    }

    /**
     * Index page for ProductCGD management
     * 
     * @Route("/", name="product_cgd_index", methods={"GET"})
     */
    public function index(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $page = max(1, (int) $request->query->get('page', 1));
        $status = $request->query->get('status', 'pending');
        $search = $request->query->get('search', '');

        // Debug: Check what the service returns
        try {
            if ($search) {
                $productCGDs = $productCGDService->search($search, $page);
            } else {
                $productCGDs = $productCGDService->getByStatus($status, $page);
            }

            $statistics = $productCGDService->getStatistics();
            
            //
            
        } catch (\Exception $e) {
            //
            $productCGDs = [];
            $statistics = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
        }

        return $this->render('product/admin/productCGD/index.html.twig', [
            'product_cgds' => $productCGDs,
            'statistics' => $statistics,
            'current_status' => $status,
            'current_page' => $page,
            'search_query' => $search
        ]);
    }

    /**
     * Show individual ProductCGD entry
     * 
     * @Route("/{id}/show", name="product_cgd_show", methods={"GET"})
     */
    public function show(ProductCGD $productCGD): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        return $this->render('product/admin/productCGD/show.html.twig', [
            'product_cgd' => $productCGD
        ]);
    }



    /**
     * Approve individual ProductCGD entry
     * 
     * @Route("/approve/{id}", name="product_cgd_approve", methods={"POST"})
     */
    public function approve(Request $request, ProductCGD $productCGD, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $result = $productCGDService->approveProductCGD($productCGD, $this->getUser());
        
        if ($result['success']) {
            $this->addFlash('success', 'ProductCGD entry approved successfully');
        } else {
            $this->addFlash('error', 'Failed to approve ProductCGD entry: ' . $result['message']);
        }

        return $this->redirectToRoute('product_cgd_index');
    }

    /**
     * Reject individual ProductCGD entry
     * 
     * @Route("/reject/{id}", name="product_cgd_reject", methods={"POST"})
     */
    public function reject(Request $request, ProductCGD $productCGD, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $reason = $request->request->get('rejection_reason', 'No reason provided');
        $result = $productCGDService->rejectProductCGD($productCGD, $this->getUser(), $reason);
        
        if ($result['success']) {
            $this->addFlash('success', 'ProductCGD entry rejected successfully');
        } else {
            $this->addFlash('error', 'Failed to reject ProductCGD entry: ' . $result['message']);
        }

        return $this->redirectToRoute('product_cgd_index');
    }

    /**
     * Bulk approve multiple ProductCGD entries
     * 
     * @Route("/bulk-approve", name="product_cgd_bulk_approve", methods={"POST"})
     */
    public function bulkApprove(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $productCGDIds = $request->request->get('product_cgd_ids', []);
        
        if (empty($productCGDIds)) {
            $this->addFlash('error', 'No ProductCGD entries selected for approval');
            return $this->redirectToRoute('product_cgd_index');
        }

        $results = [];
        foreach ($productCGDIds as $id) {
            $productCGD = $this->em()->getRepository(ProductCGD::class)->find($id);
            if ($productCGD) {
                $result = $productCGDService->approveProductCGD($productCGD, $this->getUser());
                $results[] = $result;
            }
        }

        $approvedCount = count(array_filter($results, fn($r) => $r['success']));
        $this->addFlash('success', "Successfully approved {$approvedCount} ProductCGD entries");

        return $this->redirectToRoute('product_cgd_index');
    }

    /**
     * Bulk reject multiple ProductCGD entries
     * 
     * @Route("/bulk-reject", name="product_cgd_bulk_reject", methods={"POST"})
     */
    public function bulkReject(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $productCGDIds = $request->request->get('product_cgd_ids', []);
        $reason = $request->request->get('rejection_reason', 'Bulk rejection');
        
        if (empty($productCGDIds)) {
            $this->addFlash('error', 'No ProductCGD entries selected for rejection');
            return $this->redirectToRoute('product_cgd_index');
        }

        $results = [];
        foreach ($productCGDIds as $id) {
            $productCGD = $this->em()->getRepository(ProductCGD::class)->find($id);
            if ($productCGD) {
                $result = $productCGDService->rejectProductCGD($productCGD, $this->getUser(), $reason);
                $results[] = $result;
            }
        }

        $rejectedCount = count(array_filter($results, fn($r) => $r['success']));
        $this->addFlash('success', "Successfully rejected {$rejectedCount} ProductCGD entries");

        return $this->redirectToRoute('product_cgd_index');
    }

    /**
     * Convert all approved ProductCGD entries from temp table to actual products
     * Updated by cursor on 2025-08-31 23:59:00 to convert all approved entries without requiring IDs
     * 
     * @Route("/convert-approved", name="product_cgd_convert_approved", methods={"POST"})
     */
    public function convertApproved(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        // Convert ALL approved entries, not by selection
        $result = $productCGDService->convertAllApprovedToProducts();

        if ($result['converted'] > 0) {
            $this->addFlash('success', "Successfully converted {$result['converted']} approved ProductCGD entries to products");
        }
        
        if ($result['errors'] > 0) {
            $this->addFlash('warning', "{$result['errors']} entries had errors during conversion");
        }

        return $this->redirectToRoute('product_cgd_index');
    }

    /**
     * CGData (Category Generate Data) - Save products to temporary table for admin approval
     * Created by cursor on 2025-08-31 23:59:00 to centralize CGD save endpoint in ProductCGDController
     * Handles request with catId/BrndId OR category_id/brand_id and array of product data
     * Updated by cursor on 2025-09-01 00:10:00 to support both param names and JSON-decoded products
     * 
     * @Route("/cgdata/save-products", name="product_cgd_save_to_temp", methods={"POST"})
     * @Route("/cgdata/save-products", name="category_cgdata_save_products", methods={"POST"})
     */
    public function cgdataSaveToTempProducts(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        try {
            $products = $request->request->get('products', []);
            if (is_string($products)) {
                $decoded = json_decode($products, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $products = $decoded;
                }
            }

            // Support both new (catId/BrndId) and legacy (category_id/brand_id) param names
            $categoryId = (int)($request->request->get('catId') ?? $request->request->get('category_id'));
            $brandId = $request->request->get('BrndId') ?? $request->request->get('brand_id');

            if (!$categoryId || !$brandId) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category and Brand are required'
                ]);
            }

            $category = $this->em()->getRepository(\App\ProductBundle\Entity\Category::class)->find($categoryId);
            $brand = $this->em()->getRepository(\App\ProductBundle\Entity\Brand::class)->find($brandId);

            if (!$category) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category not found'
                ]);
            }

            // Brand is optional. If brandId provided but invalid, return error
            if ($brandId && !$brand) {
                return $this->json([
                    'success' => false,
                    'error' => 'Brand not found'
                ]);
            }

            $batchId = 'cgdata_' . time() . '_' . uniqid();

            $result = $productCGDService->saveCGDListToTemp(
                $products,
                $category,
                $brand,
                $this->getUser(),
                $batchId
            );

            if ($result['saved'] > 0) {
                $this->addFlash('success', 'CGData products saved to temporary table for admin approval. Batch: ' . $batchId);
                return $this->redirectToRoute('product_cgd_index');
            }

            $this->addFlash('error', 'No products were saved to temporary table.');
            return $this->redirectToRoute('product_cgd_index');
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get ProductCGD data for AJAX requests
     * 
     * @Route("/data/table", name="product_cgd_datatable", methods={"GET"})
     */
    public function datatable(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $page = max(1, (int) $request->query->get('page', 1));
        $status = $request->query->get('status', 'pending');
        $search = $request->query->get('search', '');

        if ($search) {
            $productCGDs = $productCGDService->search($search, $page);
        } else {
            $productCGDs = $productCGDService->getByStatus($status, $page);
        }

        $data = [];
        foreach ($productCGDs as $productCGD) {
            $data[] = [
                'id' => $productCGD->getId(),
                'name' => $productCGD->getName(),
                'sku' => $productCGD->getSku(),
                'category' => $productCGD->getCategory()->getTitle(),
                'brand' => $productCGD->getBrand() ? $productCGD->getBrand()->getTitle() : 'N/A',
                'status' => $productCGD->getStatusLabel(),
                'status_class' => $productCGD->getStatusBadgeClass(),
                'product_id' => $productCGD->getProductId(),
                'created_at' => $productCGD->getCreated()->format('Y-m-d H:i:s'),
                'created_by' => $productCGD->getCreatedBy() ? $productCGD->getCreatedBy()->getEmail() : 'System'
            ];
        }

        return $this->json([
            'data' => $data,
            'total' => count($data),
            'page' => $page
        ]);
    }

    /**
     * Approve all ProductCGD entries for a given status (default: pending)
     * Created by cursor on 2025-09-01 01:05:00 to support Approve All
     *
     * @Route("/approve-all", name="product_cgd_approve_all", methods={"POST"})
     */
    public function approveAll(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $status = $request->request->get('status', 'pending');
        $approvedEntries = $this->em()->getRepository(\App\ProductBundle\Entity\ProductCGD::class)->findBy(['status' => $status]);

        $count = 0;
        foreach ($approvedEntries as $entry) {
            $result = $productCGDService->approveProductCGD($entry, $this->getUser());
            if (!empty($result['success'])) {
                $count++;
            }
        }

        $this->addFlash('success', $count . ' ProductCGD entries approved');
        return $this->redirectToRoute('product_cgd_index', ['status' => $status]);
    }

    /**
     * Reject all ProductCGD entries for a given status (default: pending)
     * Created by cursor on 2025-09-01 01:05:00 to support Reject All
     *
     * @Route("/reject-all", name="product_cgd_reject_all", methods={"POST"})
     */
    public function rejectAll(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $status = $request->request->get('status', 'pending');
        $reason = $request->request->get('rejection_reason', 'Bulk rejection');
        $entries = $this->em()->getRepository(\App\ProductBundle\Entity\ProductCGD::class)->findBy(['status' => $status]);

        $count = 0;
        foreach ($entries as $entry) {
            $result = $productCGDService->rejectProductCGD($entry, $this->getUser(), $reason);
            if (!empty($result['success'])) {
                $count++;
            }
        }

        $this->addFlash('success', $count . ' ProductCGD entries rejected');
        return $this->redirectToRoute('product_cgd_index', ['status' => $status]);
    }

    /**
     * Physically delete rejected CGD entries (all or selected)
     * Created by cursor on 2025-09-01 00:45:30 per requirement
     * 
     * @Route("/delete-rejected", name="product_cgd_delete_rejected", methods={"POST"})
     */
    public function deleteRejected(Request $request, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $ids = $request->request->get('product_cgd_ids');
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $ids = $decoded;
            }
        }
        if (!is_array($ids) || count($ids) === 0) {
            $ids = null; // delete all rejected
        }

        $deleted = $productCGDService->deleteRejected($ids);
        $this->addFlash('success', $deleted . ' rejected CGD entries deleted');
        return $this->redirectToRoute('product_cgd_index');
    }

    /**
     * Convert a single approved ProductCGD entry to product
     * Created by cursor on 2025-09-01 01:24:00 to support per-row "Add to DB"
     *
     * @Route("/convert/{id}", name="product_cgd_convert_one", methods={"POST"})
     */
    public function convertOne(ProductCGD $productCGD, ProductCGDService $productCGDService): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        if (!$productCGD->isApproved()) {
            $this->addFlash('error', 'Entry is not approved yet.');
            return $this->redirectToRoute('product_cgd_index', ['status' => 'approved']);
        }

        $result = $productCGDService->convertApprovedToProducts([$productCGD->getId()]);

        if (($result['converted'] ?? 0) > 0) {
            $this->addFlash('success', 'Entry converted and added to DB');
        } else {
            $this->addFlash('error', 'Conversion failed');
        }

        return $this->redirectToRoute('product_cgd_index', ['status' => 'approved']);
    }

    /**
     * Move a ProductCGD entry back to pending status
     * Created by cursor on 2025-09-01 01:12:00 to support UI action "Back to Pending"
     *
     * @Route("/move-to-pending/{id}", name="product_cgd_move_pending", methods={"POST"})
     */
    public function moveToPending(ProductCGD $productCGD): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);

        $productCGD->setStatus('pending');
        $productCGD->setApprovedBy(null);
        $productCGD->setApprovedAt(null);
        $this->em()->persist($productCGD);
        $this->em()->flush();

        $this->addFlash('success', 'Entry moved back to pending');
        return $this->redirectToRoute('product_cgd_index', ['status' => 'pending']);
    }

    /**
     * Get available brands for CGData brand selection
     * Created by cursor on 2025-08-31 23:59:30 to centralize CGD endpoints
     * 
     * @Route("/cgdata/brands", name="product_cgd_brands", methods={"GET"})
     * @Route("/cgdata/brands", name="category_cgdata_brands", methods={"GET"})
     */
    public function cgdataGetBrands(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $brands = $this->em()->getRepository(\App\ProductBundle\Entity\Brand::class)->findBy([
                'deleted' => null
            ], ['title' => 'ASC']);

            $brandData = [];
            foreach ($brands as $brand) {
                $brandData[] = [
                    'id' => $brand->getId(),
                    'title' => $brand->getTitle()
                ];
            }

            return $this->json([
                'success' => true,
                'brands' => $brandData
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

}
