<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\ProductCGD;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Brand;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductDetails;
use App\ContentBundle\Entity\Post;
use App\SeoBundle\Entity\Seo;
use App\ProductBundle\Form\Filter\ProductFilterType;
use App\ProductBundle\Form\ProductType;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Repository\ProductSearchRepository;
use App\ProductBundle\Service\AttributeService;
use App\ProductBundle\Service\CategoryService;
use App\ProductBundle\Service\ProductService;
use App\SeoBundle\Repository\SeoRepository;
use App\ThreeSixtyViewBundle\Entity\ThreeSixtyView;
use App\ThreeSixtyViewBundle\Service\ThreeSixtyViewService;
use App\UserBundle\Entity\User;
use JetBrains\PhpStorm\NoReturn;
use PN\MediaBundle\Service\UploadDocumentService;
use PN\MediaBundle\Service\UploadImageService;
use PN\ServiceBundle\Lib\Paginator;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Date;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;

/**
 * Product controller.
 *
 * @Route("")
 */
class ProductController extends AbstractController
{
    /**
     * Redirect method to handle /admin/product to /admin/product/ redirect
     * Created by cursor on 2025-01-27 15:30:00 to fix routing issue for admin product page
     */
    public function redirectToIndex(): Response
    {
        return $this->redirectToRoute('product_index_all');
    }
    /**
     * @Route("/", name="product_index_all", methods={"GET"})
     */
    public function indexAll(
        Request         $request,
        CategoryService $categoryService,
        ProductService  $productService
    ): Response
    {
        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);
        
        // No category specified, show all products
        $category = null;
        $categoryParents = [];

        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);

        return $this->render('product/admin/product/index.html.twig', [
            "category" => $category,
            "search" => $search,
            "filter_form" => $filterForm->createView(),
            'categoryParents' => $categoryParents,
        ]);
    }

    /**
     * @Route("/{category}", requirements={"category" = "\d+"}, name="product_index", methods={"GET"})
     */
    public function index(
        Request         $request,
        CategoryService $categoryService,
        ProductService  $productService,
        Category        $category = null
    ): Response
    {


        $this->denyAccessUnlessGranted([User::ROLE_ADMIN]);
        $categoryParents = $categoryService->parentsByChild($category);

        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);

        return $this->render('product/admin/product/index.html.twig', [
            "category" => $category,
            "search" => $search,
            "filter_form" => $filterForm->createView(),
            'categoryParents' => $categoryParents,
        ]);
    }

    /**
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="product_edit", methods={"GET", "POST"})
     */
    public function edit(
        Request            $request,
        ThreeSixtyViewService $threeSixtyViewService,
        Product            $product
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$product->getThreeSixtyView() instanceof ThreeSixtyView) {
            $product->setThreeSixtyView($threeSixtyViewService->createNew());
            $this->em()->persist($product);
            $this->em()->flush();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($product);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('product_edit', ["id" => $product->getId()]);
        }

        return $this->render('product/admin/product/edit.html.twig', [
            'product' => $product,
            'currentCategory' => $product->getCategory(),
            'form' => $form->createView(),
        ]);
    }

    // ============================================================================
    // PRODUCT FORM MANAGEMENT FUNCTIONS
    // ============================================================================

    /**
     * New Product Form Display - Split from original new function
     * Updated by cursor on 2025-08-28 09:17:54 to separate form display from submission handling
     * 
     * @Route("/new-form/{category}", requirements={"category" = "\d+"}, name="product_new_form", methods={"GET"})
     */
    public function newForm(
        CurrencyRepository $currencyRepository,
        Category           $category = null
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Product();
        $product->setCurrency($currencyRepository->getDefaultCurrency());
        $product->setDetails(new ProductDetails());
        $product->setCategory($category);

        $form = $this->createForm(ProductType::class, $product);

        return $this->render('product/admin/product/new.html.twig', [
            'product' => $product,
            'currentCategory' => $product->getCategory(),
            'form' => $form->createView(),
            'use_split_form' => true, // Flag to indicate we're using the split approach
        ]);
    }

    /**
     * Displays a form to edit an existing product entity.
     *
     * @Route("/get-specs-form-ajax", requirements={"id" = "\d+"}, name="product_specs_form_ajax", methods={ "POST"})
     */
    public function getSpecsFormAjax(
        Request            $request,
        CategoryRepository $categoryRepository,
        ProductRepository  $productRepository,
        AttributeService   $attributeService
    ): Response
    {
        $productId = $request->request->get("productId");
        $categoryId = $request->request->get("categoryId");
        if (!Validate::not_null($categoryId)) {
            return $this->json(["error" => 0, "message" => "", "html" => null]);
        }
        $product = null;

        if (Validate::not_null($productId)) {
            $product = $productRepository->find($productId);
            if (!$product) {
                return $this->json(["error" => 1, "message" => "Product not found", "html" => null]);
            }
        }
        $category = $categoryRepository->find($categoryId);
        if (!$category) {
            return $this->json(["error" => 1, "message" => "Category not found", "html" => null]);
        }
        $specsForm = $attributeService->getSpecsForm($category, $product);
        $html = $this->renderView("product/admin/product/_form_specs.html.twig", [
            "currentCategory" => $category,
            "form" => $specsForm->createView(),
        ]);

        return $this->json(["error" => 0, "message" => "", "html" => $html]);
    }

    /**
     * @Route("/{id}", requirements={"id" = "\d+"}, name="product_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ProductService $productService, Product $product): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $productService->deleteProduct($product);

        return $this->redirectToRoute('product_index');
    }


    /**
     * Lists all product entities without category filter.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="product_datatable_all", methods={"GET"})
     */
    public function dataTableAll(
        Request           $request,
        ProductService    $productService,
        ProductRepository $productRepository
    ): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");

        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);
        if (Validate::not_null($srch['value'])) {
            $search->string = $srch['value'];
        }
        $ordr[0]['column'] = ($ordr[0]['column'] > 0) ? $ordr[0]['column'] - 1 : $ordr[0]['column'];
        $search->ordr = $ordr[0];
        // No category filter when showing all products

        $count = $productRepository->filter($search, true);
        $products = $productRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/product/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "products" => $products,
        ]);
    }

    /**
     * Lists all product entities.
     *
     * @Route("/data/table/{category}", requirements={"category" = "\d+"}, defaults={"_format": "json"}, name="product_datatable", methods={"GET"})
     */
    public function dataTable(
        Request           $request,
        ProductService    $productService,
        ProductRepository $productRepository,
        Category          $category = null
    ): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");

        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);
        if (Validate::not_null($srch['value'])) {
            $search->string = $srch['value'];
        }
        $ordr[0]['column'] = ($ordr[0]['column'] > 0) ? $ordr[0]['column'] - 1 : $ordr[0]['column'];
        $search->ordr = $ordr[0];
        if ($category != null) {
            $search->category = $category->getId();
        }

        $count = $productRepository->filter($search, true);
        $products = $productRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/product/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "products" => $products,
        ]);
    }

    /**
     * @Route("/clone/{id}", name="product_clone", methods={"GET", "POST"})
     */
    public function clone(
        Request               $request,
        UploadDocumentService $uploadDocumentService,
        UploadImageService    $uploadImageService,
        SeoRepository         $seoRepository,
        UserService           $userService,
        Product               $product
    ): Response
    {

        $newEntity = clone $product;
        $i = 0;
        do {
            if ($i == 0) {
                $slug = $newEntity->getSeo()->getSlug();
            } else {
                $slug = $newEntity->getSeo()->getSlug() . '-' . $i;
            }
            $slugIfExist = $seoRepository->findOneBy([
                'seoBaseRoute' => $newEntity->getSeo()->getSeoBaseRoute()->getId(),
                'slug' => $slug,
                'deleted' => false,
            ]);
            $i++;
        } while ($slugIfExist != null);

        $newEntity->getSeo()->setSlug($slug);
        $form = $this->createForm(ProductType::class, $newEntity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userName = $userService->getUserName();
            $newEntity->setCreator($userName);
            $newEntity->setModifiedBy($userName);
            $this->em()->persist($newEntity);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            $images = $product->getPost()->getImages();
            foreach ($images as $image) {
                $oldImagePath = $image->getAbsoluteExtension();
                $galleryTempImage = sys_get_temp_dir() . "/" . $image->getId();
                copy($oldImagePath, $galleryTempImage);
                $uploadImageService->uploadSingleImageByPath($newEntity->getPost(), $galleryTempImage, 1, $request,
                    $image->getImageType());
            }

            if ($newEntity->getPost()->getMainImage() instanceof Image) {
                $newEntity->setMainImage($newEntity->getPost()->getMainImage());
                $this->em()->persist($newEntity);
                $this->em()->flush();
            }


            /* $document = $product->getDetails()->getTearSheet();
             if ($document) {
                 $oldDocumentPath = $document->getAbsoluteExtension();
                 $documentTempImage = "/tmp/".$document->getId();
                 copy($oldDocumentPath, $documentTempImage);
                 $document = $uploadDocumentService->uploadSingleDocumentByPath($newEntity->getDetails(),
                     $documentTempImage, 101, $request, null, 'tearSheet');
                 $this->em()->persist($document);
                 $this->em()->flush();
             }*/

            return $this->redirectToRoute('product_edit', ['id' => $newEntity->getId()]);
        }

        return $this->render('product/admin/product/new.html.twig', [
            'product' => $newEntity,
            'currentCategory' => $newEntity->getCategory(),
            'form' => $form->createView(),
            "clone" => true,
        ]);
    }

    /**
     * search product ajax.
     *
     * @Route("/related/product/ajax", name="related_product_select_ajax", methods={"GET"})
     */
    public function searchSelect2(Request $request, ProductRepository $productRepository): Response
    {
        $page = ($request->query->has('page')) ? $request->get('page') : 1;
        $notId = ($request->query->has('notId')) ? $request->get('notId') : null;

        $search = new \stdClass;
        $search->admin = true;
        $search->deleted = 0;
        //        $search->publish = 1;
        $search->notId = $notId;
        $search->string = $request->get('q');

        $count = $productRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 10);
        $entities = $productRepository->filter($search, false, $paginator->getLimitStart(),
            $paginator->getPageLimit());

        $paginationFlag = false;
        if (isset($paginator->getPagination()['last']) and $paginator->getPagination()['last'] != $page) {
            $paginationFlag = true;
        }

        $returnArray = [
            'results' => [],
            'pagination' => $paginationFlag,
        ];

        foreach ($entities as $entity) {
            $title = $entity->getTitle();
            if (!$entity->isPublish()) {
                $title .= " (Unpublished)";
            }
            $returnArray['results'][] = [
                'id' => $entity->getId(),
                'text' => $title,
            ];
        }

        return $this->json($returnArray);
    }

    /**
     * search product ajax.
     *
     * @Route("/product-price/ajax", name="product_price_select_ajax", methods={"GET"})
     */
    public function productPriceSelect2(Request $request, ProductRepository $productRepository): Response
    {
        $page = ($request->query->has('page')) ? $request->get('page') : 1;
        $notId = ($request->query->has('notId')) ? $request->get('notId') : null;

        $search = new \stdClass;
        $search->admin = true;
        $search->deleted = 0;
        //        $search->publish = 1;
        $search->notId = $notId;
        $search->string = $request->get('q');

        $count = $productRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 10);
        $entities = $productRepository->filter($search, false, $paginator->getLimitStart(),
            $paginator->getPageLimit());

        $paginationFlag = false;
        if (isset($paginator->getPagination()['last']) and $paginator->getPagination()['last'] != $page) {
            $paginationFlag = true;
        }

        $returnArray = [
            'results' => [],
            'pagination' => $paginationFlag,
        ];

        foreach ($entities as $entity) {
            $title = $entity->getTitle();
            if (!$entity->isPublish()) {
                $title .= " (Unpublished)";
            }
            foreach ($entity->getPrices() as $price) {
                $returnArray['results'][] = [
                    'id' => $price->getId(),
                    'text' => $title . ($price->getTitle() ? ' - ' . $price->getTitle() : '') . (" (" . number_format($price->getSellPrice()) . " EGP)"),
                ];
            }
        }

        return $this->json($returnArray);
    }

    /**
     * @Route("/upload/csv", name="product_upload_csv", methods={"GET", "POST"})
     */
    public function uploadCSV(Request $request, UserService $userService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createFormBuilder()
            ->add('file', FileType::class, [
                "label" => "CSV file",
                "required" => true,
                "attr" => [
                    "class" => "form-control",
                    "accept" => ".csv",
                ],
            ])
            ->getForm();
        $form->handleRequest($request);


        $noOfProductInQueue = 0; // For now, we'll set this to 0 since we're not using a queue system

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $form->get('file')->getData();
            $mimeType = $file->getClientMimeType();
            //            $name = $file->getClientOriginalName();
            if (!in_array($mimeType, ['text/csv', 'application/vnd.ms-excel'])) {
                $this->addFlash('error', 'This file type is not acceptable');

                return $this->render("product/admin/product/uploadCSV.html.twig", [
                    'noOfProductInQueue' => $noOfProductInQueue,
                    'form' => $form->createView(),
                ]);
            }

            $handle = fopen($file->getPathname(), 'r');
            $row = 0;
            $successRows = 0;
            $errorRows = 0;
            $errors = [];
            $headers = [];

            // Read headers from first row
            if (($headerRow = fgetcsv($handle)) !== false) {
                $headers = array_map('trim', $headerRow);
                $row = 1;
                
                // Validate required headers
                $requiredHeaders = ['SKU', 'Product Name', 'Category ID'];
                $missingHeaders = array_diff($requiredHeaders, $headers);
                
                if (!empty($missingHeaders)) {
                    $this->addFlash('error', 'Missing required columns: ' . implode(', ', $missingHeaders));
                    return $this->render("product/admin/product/uploadCSV.html.twig", [
                        'noOfProductInQueue' => $noOfProductInQueue,
                        'form' => $form->createView(),
                    ]);
                }
            }

            while (($data = fgetcsv($handle)) !== false) {
                $row++;
                
                try {
                    $rowAsObject = $this->convertCsvRowToObject($data, $headers);
                    
                    // Validate required fields
                    if (empty($rowAsObject->sku) || empty($rowAsObject->title) || empty($rowAsObject->categoryId)) {
                        $errors[] = "Row $row: Missing required fields (SKU, Title, or Category ID)";
                        $errorRows++;
                        continue;
                    }
                    
                    // Check if product with this SKU already exists
                    $existingProduct = $this->em()->getRepository(Product::class)->findOneBy(['sku' => $rowAsObject->sku]);
                    if ($existingProduct) {
                        $errors[] = "Row $row: Product with SKU '{$rowAsObject->sku}' already exists";
                        $errorRows++;
                        continue;
                    }
                    
                    // Check if category exists
                    $category = $this->em()->getRepository(Category::class)->find($rowAsObject->categoryId);
                    if (!$category) {
                        $errors[] = "Row $row: Category with ID '{$rowAsObject->categoryId}' not found";
                        $errorRows++;
                        continue;
                    }
                    
                    // Check and find brand if provided
                    $brand = null;
                    if (!empty($rowAsObject->brandIdentifier)) {
                        // First try to find by ID (if numeric)
                        if (is_numeric($rowAsObject->brandIdentifier)) {
                            $brand = $this->em()->getRepository(Brand::class)->find($rowAsObject->brandIdentifier);
                        }
                        
                        // If not found by ID or not numeric, try to find by title/name
                        if (!$brand) {
                            $brand = $this->em()->getRepository(Brand::class)->findOneBy([
                                'title' => $rowAsObject->brandIdentifier,
                                'deleted' => null
                            ]);
                        }
                        
                        // If still not found, show warning but continue
                        if (!$brand) {
                            $errors[] = "Row $row: Brand '{$rowAsObject->brandIdentifier}' not found - product created without brand";
                        }
                    }
                    
                    // Create new product
                    $product = new Product();
                    $product->setSku($rowAsObject->sku);
                    $product->setTitle($rowAsObject->title);
                    $product->setCategory($category);
                    if ($brand) {
                        $product->setBrand($brand);
                    }
                    $product->setPublish(false); // Set as unpublished by default for review
                    
                    // Set default currency
                    $defaultCurrency = $this->em()->getRepository(\App\CurrencyBundle\Entity\Currency::class)->getDefaultCurrency();
                    if ($defaultCurrency) {
                        $product->setCurrency($defaultCurrency);
                    }
                    
                    // Create product details
                    $productDetails = new ProductDetails();
                    $product->setDetails($productDetails);
                    
                    // Create content post for description
                    $post = new \App\ContentBundle\Entity\Post();
                    $post->setContent(['description' => $rowAsObject->description ?? '']);
                    $product->setPost($post);
                    
                    // Note: Model number is captured but not stored separately
                    // You can extend the Product entity to add a modelNumber field if needed
                    // For now, it's available in the CSV data for reference
                    
                    $this->em()->persist($product);
                    $successRows++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row $row: " . $e->getMessage();
                    $errorRows++;
                }
            }
            
            fclose($handle);
            
            if ($successRows > 0) {
                $this->em()->flush();
                $this->addFlash('success', "$successRows product(s) successfully imported");
            }
            
            if ($errorRows > 0) {
                $this->addFlash('warning', "$errorRows row(s) had errors. Check the details below.");
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }

            return $this->redirectToRoute('product_upload_csv');
        }

        return $this->render("product/admin/product/uploadCSV.html.twig", [
            'noOfProductInQueue' => $noOfProductInQueue,
            'form' => $form->createView(),
        ]);
    }

    private function convertCsvRowToObject($data, $headers)
    {
        $convertToHtml = function ($str) {
            $str = nl2br($str);
            $str = trim($str);
            $search = ['&rsquo;', '&nbsp;', '&bull;', "\n", "\t", "\r", "\v", "\e"];

            return str_replace($search, '', $str);
        };
        $clearEscapedCharacters = function ($str) {
            $str = trim($str);
            $search = ['&rsquo;', '&nbsp;', '&bull;', "\n", "\t", "\r", "\v", "\e"];

            return str_replace($search, '', $str);
        };

        $product = new \stdClass;
        
        // Find column positions by header names (support multiple variations)
        $skuIndex = $this->findColumnIndex($headers, ['SKU', 'sku', 'Product SKU', 'Product Code']);
        $titleIndex = $this->findColumnIndex($headers, ['Product Name', 'Title', 'Name', 'Product Title']);
        $modelNumberIndex = $this->findColumnIndex($headers, ['Model Number', 'Model', 'Model No', 'Part Number']);
        $descriptionIndex = $this->findColumnIndex($headers, ['Description', 'Desc', 'Product Description', 'Details']);
        $categoryIdIndex = $this->findColumnIndex($headers, ['Category ID', 'Category', 'CategoryID', 'Cat ID']);
        $brandIndex = $this->findColumnIndex($headers, ['Brand Name/ID', 'Brand', 'Brand Name', 'Brand ID', 'Manufacturer']);
        
        // Extract values using found indices
        $product->sku = ($skuIndex !== false && isset($data[$skuIndex])) ? $clearEscapedCharacters($data[$skuIndex]) : null;
        $product->title = ($titleIndex !== false && isset($data[$titleIndex])) ? $clearEscapedCharacters($data[$titleIndex]) : null;
        $product->modelNumber = ($modelNumberIndex !== false && isset($data[$modelNumberIndex])) ? $clearEscapedCharacters($data[$modelNumberIndex]) : null;
        $product->description = ($descriptionIndex !== false && isset($data[$descriptionIndex])) ? $convertToHtml($data[$descriptionIndex]) : null;
        $product->categoryId = ($categoryIdIndex !== false && isset($data[$categoryIdIndex])) ? trim($data[$categoryIdIndex]) : null;
        $product->brandIdentifier = ($brandIndex !== false && isset($data[$brandIndex])) ? trim($data[$brandIndex]) : null;

        return $product;
    }

    private function validateCsv($handle): bool
    {
        $return = true;
        $rowNumber = 0;
        $headers = [];
        
        // Read headers from first row
        if (($headerRow = fgetcsv($handle)) !== false) {
            $headers = array_map('trim', $headerRow);
            $rowNumber = 1;
        }
        
        while (($data = fgetcsv($handle)) != false) {
            $rowNumber++;
            $rowAsObject = $this->convertCsvRowToObject($data, $headers);

            if (!Validate::not_null($rowAsObject->title)) {
                $this->addFlash('error', "Row Number $rowNumber does not contain a title");
                $return = false;
            }
            if (!Validate::not_null($rowAsObject->categoryId)) {
                $this->addFlash('error', "Row Number $rowNumber does not contain a Category ID");
                $return = false;
            } else {
                $checkIsLastChild = $this->em()->getRepository(Category::class)->findOneBy([
                    "id" => $rowAsObject->categoryId,
                    "deleted" => null,
                ]);
                if ($checkIsLastChild == null or $checkIsLastChild->getChildren()->count() > 0) {
                    $this->addFlash('error', 'Row Number ' . $rowNumber . ', the category is not correct');
                    $return = false;
                }
            }
            if (!Validate::not_null($rowAsObject->sku)) {
                $this->addFlash('error', "Row Number $rowNumber does not contain a sku");
                $return = false;
            } else {
                $checkSkuIsExist = $this->em()->getRepository(Product::class)->findOneBy(["sku" => $rowAsObject->sku]);
                if ($checkSkuIsExist != null) {
                    $this->addFlash('error', 'Row Number ' . $rowNumber . ', SKU is already exist "' . $rowAsObject->sku . '"');
                    $return = false;
                }
            }

            $rowNumber++;
        }

        if ($rowNumber <= 1) {
            $this->addFlash('error', "The uploaded file is empty");
            $return = false;
        } elseif ($rowNumber > 501) {
            $this->addFlash('error', "This file exceeds the given capacity, as it has more than 500 rows.");
            $return = false;
        }

        return $return;
    }

    /**
     * Lists all Category entities.
     *
     * @Route("/download/sample", requirements={"id" = "\d+"}, name="product_download_csv_sample", methods={"GET"})
     */
    #[NoReturn] public function downloadCSVSample(Request $request): Response
   
    {
        $list = [
            [
                'SKU',
                'Product Name',
                'Model Number',
                'Description',
                'Category ID',
                'Brand Name/ID',
            ],
            [
                '67368123-TABLE',
                'Wooden Dining Table',
                'WD-001',
                'Beautiful wooden dining table with elegant design.',
                '15',
                'IKEA',
            ],
            [
                'Category ID',
                'Brand Name/ID',
                'SKU',
                'Product Name',
                'Description',
                'Model Number',
            ],
            [
                '12',
                'Philips',
                'ABC-123-CHAIR',
                'Ergonomic Office Chair',
                'Comfortable office chair with lumbar support.',
                'EO-456',
            ],
        ];
        $f = fopen('php://memory', 'w');
        // loop over the input array
        foreach ($list as $fields) {
            fputcsv($f, $fields, ",");
        }
        fseek($f, 0);

        // tell the browser it's going to be a csv file
        header('Content-Type: application/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="products-example.csv";');

        fpassthru($f);

        exit;
    }

    /**
     * Deletes a Merchant entity.
     *
     * @Route("/mass-delete", name="product_mass_delete", methods={"POST"})
     */
    public function massDelete(
        Request           $request,
        ProductService    $productService,
        ProductRepository $productRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $ids = $request->request->get('ids');
        if (!is_array($ids)) {
            return $this->json(['error' => 1, "message" => "Please enter select"]);
        }

        $products = $productRepository->findBy(["id" => $ids]);
        foreach ($products as $product) {
            $productService->deleteProduct($product);
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/export-csv", name="product_export_csv", methods={"GET"})
     */
    public function exportCSV(
        Request                 $request,
        ProductService          $productService,
        ProductSearchRepository $productSearchRepository
    ): Response
    {
        $list[] = [
            "#",
            "SKU",
            "Title",
            "Price",
            "Category",
            "Created",
            "Published",
            "Featured",
            "Description",
        ];

        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);
        $products = $productSearchRepository->filter($search, false);
        foreach ($products as $product) {
            $list = array_merge($list, $this->exportCsvRow($product));
        }
        $f = fopen('php://memory', 'w');
        // loop over the input array
        foreach ($list as $fields) {
            fputcsv($f, $fields, ",");
        }
        fseek($f, 0);

        // tell the browser it's going to be a csv file
        header('Content-Type: application/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="products-import-template-flexible-' . date("Y-m-d") . '.csv";');

        fpassthru($f);

        exit;
    }


    private function exportCsvRow(Product $product)
    {
        $description = "";
        $categoryTitle = $product->getCategory()->getTitle();
        $category = $product->getCategory();
        while ($category->getParent() != null) {
            $category = $category->getParent();
            $categoryTitle = $category->getTitle() . " - " . $categoryTitle;
        }

        if (array_key_exists("description", $product->getPost()->getContent())) {
            $description = strip_tags($product->getPost()->getContent()['description']);
        }

        $return[] = [
            $product->getId(),
            $product->getSku() ? $product->getSku() : 'N/A',
            $product->getTitle(),
            $product->minPrice,
            $categoryTitle,
            $product->getCreated()->format(Date::DATE_FORMAT6),
            ($product->isPublish()) ? "Yes" : "No",
            ($product->isFeatured()) ? "Yes" : "No",
            $description,
        ];


        return $return;
    }

    /**
     * @Route("/prepare/bulk-update", name="product_prepare_to_bulk_update", methods={"GET"})
     */
    public function prepareProductToBulkUpdate(Request $request): Response
    {
        $productIds = $request->query->get("ids");
        if (!is_array($productIds) and Validate::not_null($productIds)) {
            $productIds = [$productIds];
        } else {
            if (!is_array($productIds)) {
                $productIds = [];
            }
        }

        $session = $request->getSession();
        $session->set('productSelected', $productIds);

        return $this->redirectToRoute("product_group_update_action");
    }

    /**
     * @Route("/fancy-tree/categories/{id}", defaults={"id" = null}, name="product_category_fancy_tree", methods={"GET"})
     */
    public function getCategoriesFancyTree(
        Request         $request,
        CategoryService $categoryService,
        Product         $product = null
    ): Response
    {
        $categoriesArr = $categoryService->getCategoryForFancyTree($product);

        return $this->json($categoriesArr);
    }

    /**
     * @Route("/seo/{id}", name="product_seo_manage", methods={"GET"})
     */
    public function seoAction(
        Product $product,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$product->getSeo()) {
            // Create SEO entity if it doesn't exist
            $seo = new Seo();
            $product->setSeo($seo);
            $this->em()->persist($seo);
            $this->em()->flush();
        }

        // Calculate SEO analysis data
        $seoAnalysis = $this->calculateSeoAnalysis($product);

        return $this->render('product/admin/product/seo.html.twig', [
            'product' => $product,
            'seo_score' => $seoAnalysis['score'],
            'seo_score_color' => $seoAnalysis['score_color'],
            'seo_title_status' => $seoAnalysis['title_status'],
            'meta_description_status' => $seoAnalysis['meta_description_status'],
            'focus_keyword_status' => $seoAnalysis['focus_keyword_status'],
            'slug_status' => $seoAnalysis['slug_status'],
            'description_status' => $seoAnalysis['description_status'],
            'description_word_count' => $seoAnalysis['description_word_count'],
            'images_status' => $seoAnalysis['images_status'],
            'social_images_status' => $seoAnalysis['social_images_status'],
        ]);
    }

    /**
     * @Route("/seo/{id}/update", name="product_seo_update", methods={"POST"})
     */
    public function seoUpdateAction(
        Product $product,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $seo = $product->getSeo();
        if (!$seo) {
            $seo = new Seo();
            $product->setSeo($seo);
        }

        // Update SEO fields from form data
        $seoData = $request->request->get('seo', []);
        
        if (isset($seoData['title'])) {
            $seo->setTitle($seoData['title']);
        }
        if (isset($seoData['slug'])) {
            $seo->setSlug($seoData['slug']);
        }
        if (isset($seoData['focusKeyword'])) {
            $seo->setFocusKeyword($seoData['focusKeyword']);
        }
        if (isset($seoData['metaKeyword'])) {
            $seo->setMetaKeyword($seoData['metaKeyword']);
        }
        if (isset($seoData['metaDescription'])) {
            $seo->setMetaDescription($seoData['metaDescription']);
        }
        if (isset($seoData['metaTags'])) {
            $seo->setMetaTags($seoData['metaTags']);
        }
        if (isset($seoData['canonicalUrl'])) {
            $seo->setCanonicalUrl($seoData['canonicalUrl']);
        }
        if (isset($seoData['robots'])) {
            $seo->setRobots($seoData['robots']);
        }

        // Handle translations
        if (isset($seoData['translations']['ar'])) {
            $arData = $seoData['translations']['ar'];
            if (isset($arData['title'])) {
                $seo->setArTitle($arData['title']);
            }
            if (isset($arData['slug'])) {
                $seo->setArSlug($arData['slug']);
            }
            if (isset($arData['focusKeyword'])) {
                $seo->setArFocusKeyword($arData['focusKeyword']);
            }
            if (isset($arData['metaKeyword'])) {
                $seo->setArMetaKeyword($arData['metaKeyword']);
            }
            if (isset($arData['metaDescription'])) {
                $seo->setArMetaDescription($arData['metaDescription']);
            }
            if (isset($arData['metaTags'])) {
                $seo->setArMetaTags($arData['metaTags']);
            }
        }

        // Handle social media settings
        if (isset($seoData['seoSocials'])) {
            $socialsData = $seoData['seoSocials'];
            foreach (['facebook', 'instagram', 'twitter', 'linkedin'] as $platform) {
                if (isset($socialsData[$platform])) {
                    $platformData = $socialsData[$platform];
                    if (isset($platformData['title'])) {
                        $seo->setSeoSocialTitle($platform, $platformData['title']);
                    }
                    if (isset($platformData['description'])) {
                        $seo->setSeoSocialDescription($platform, $platformData['description']);
                    }
                }
            }
        }

        $this->em()->persist($seo);
        $this->em()->flush();

        // Redirect back to SEO page with success message
        $this->addFlash('success', 'SEO settings updated successfully!');
        
        return $this->redirectToRoute('product_seo_manage', ['id' => $product->getId()]);
    }

    /**
     * Count words from HTML content, properly handling HTML entities
     */
    private function countWordsFromHtml(string $html): int
    {
        // Debug: Log the original HTML
        error_log("Original HTML: " . substr($html, 0, 200) . "...");
        
        // First, decode all HTML entities (including &nbsp;, &amp;, etc.)
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        error_log("After html_entity_decode: " . substr($decoded, 0, 200) . "...");
        
        // Then strip all HTML tags
        $cleanText = strip_tags($decoded);
        error_log("After strip_tags: " . substr($cleanText, 0, 200) . "...");
        
        // Replace multiple spaces with single space
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        error_log("After space normalization: " . substr($cleanText, 0, 200) . "...");
        
        // Trim whitespace
        $cleanText = trim($cleanText);
        error_log("After trim: " . substr($cleanText, 0, 200) . "...");
        
        // If empty after cleaning, return 0
        if (empty($cleanText)) {
            error_log("Text is empty after cleaning");
            return 0;
        }
        
        // Split by space and count non-empty words
        $words = explode(' ', $cleanText);
        error_log("Words array: " . print_r(array_slice($words, 0, 10), true));
        
        $wordCount = 0;
        
        foreach ($words as $word) {
            if (trim($word) !== '') {
                $wordCount++;
            }
        }
        
        error_log("Final word count: " . $wordCount);
        return $wordCount;
    }

    /**
     * Calculate SEO analysis for a product
     */
    private function calculateSeoAnalysis(Product $product): array
    {
        $score = 0;
        $maxScore = 100;
        $pointsPerItem = $maxScore / 7; // 7 main SEO factors
        
        $seo = $product->getSeo();
        $post = $product->getPost();
        
        // 1. SEO Title (15 points)
        $titleStatus = 'danger';
        if ($seo && $seo->getTitle()) {
            $titleLength = strlen($seo->getTitle());
            if ($titleLength >= 30 && $titleLength <= 60) {
                $score += $pointsPerItem;
                $titleStatus = 'success';
            } elseif ($titleLength > 0) {
                $score += $pointsPerItem * 0.5;
                $titleStatus = 'warning';
            }
        }
        
        // 2. Meta Description (15 points)
        $metaDescriptionStatus = 'danger';
        if ($seo && $seo->getMetaDescription()) {
            $descLength = strlen($seo->getMetaDescription());
            if ($descLength >= 120 && $descLength <= 160) {
                $score += $pointsPerItem;
                $metaDescriptionStatus = 'success';
            } elseif ($descLength > 0) {
                $score += $pointsPerItem * 0.5;
                $metaDescriptionStatus = 'warning';
            }
        }
        
        // 3. Focus Keyword (15 points)
        $focusKeywordStatus = 'danger';
        if ($seo && $seo->getFocusKeyword()) {
            $keyword = $seo->getFocusKeyword();
            $titleContains = $seo->getTitle() && stripos($seo->getTitle(), $keyword) !== false;
            $descContains = $seo->getMetaDescription() && stripos($seo->getMetaDescription(), $keyword) !== false;
            $urlContains = $seo->getSlug() && stripos($seo->getSlug(), $keyword) !== false;
            
            if ($titleContains && $descContains) {
                $score += $pointsPerItem;
                $focusKeywordStatus = 'success';
            } elseif ($titleContains || $descContains) {
                $score += $pointsPerItem * 0.7;
                $focusKeywordStatus = 'warning';
            } else {
                $score += $pointsPerItem * 0.3;
            }
        }
        
        // 4. URL Slug (15 points)
        $slugStatus = 'danger';
        if ($seo && $seo->getSlug()) {
            $slugLength = strlen($seo->getSlug());
            if ($slugLength >= 3 && $slugLength <= 50) {
                $score += $pointsPerItem;
                $slugStatus = 'success';
            } elseif ($slugLength > 0) {
                $score += $pointsPerItem * 0.5;
                $slugStatus = 'warning';
            }
        }
        
        // 5. Product Description (15 points)
        $descriptionStatus = 'danger';
        $descriptionWordCount = 0;
        if ($post) {
            $content = $post->getContent();
            if ($content && isset($content['description']) && !empty($content['description'])) {
                $description = $content['description'];
                // Custom word counting function that properly handles HTML entities
                $descriptionWordCount = $this->countWordsFromHtml($description);
                if ($descriptionWordCount >= 300) {
                    $score += $pointsPerItem;
                    $descriptionStatus = 'success';
                } elseif ($descriptionWordCount >= 100) {
                    $score += $pointsPerItem * 0.7;
                    $descriptionStatus = 'warning';
                } elseif ($descriptionWordCount > 0) {
                    $score += $pointsPerItem * 0.3;
                }
            }
        }
        
        // 6. Image Optimization (15 points)
        $imagesStatus = 'danger';
        if ($post && $post->getImages()->count() > 0) {
            $totalImages = $post->getImages()->count();
            $imagesWithAlt = 0;
            
            foreach ($post->getImages() as $image) {
                if ($image->getAlt()) {
                    $imagesWithAlt++;
                }
            }
            
            if ($imagesWithAlt == $totalImages && $totalImages >= 1) {
                $score += $pointsPerItem;
                $imagesStatus = 'success';
            } elseif ($imagesWithAlt > 0) {
                $score += $pointsPerItem * 0.7;
                $imagesStatus = 'warning';
            } else {
                $score += $pointsPerItem * 0.3;
            }
        }
        
        // 7. Social Media Images (10 points)
        $socialImagesStatus = 'danger';
        if ($post && $post->getImages()->count() > 0) {
            $socialImageCount = 0;
            foreach ($post->getImages() as $image) {
                if ($image->getImageType() && in_array($image->getImageType(), [4, 5, 6, 7, 8])) {
                    $socialImageCount++;
                }
            }
            
            if ($socialImageCount >= 2) {
                $score += $pointsPerItem * 0.67; // 10 points instead of 15
                $socialImagesStatus = 'success';
            } elseif ($socialImageCount >= 1) {
                $score += $pointsPerItem * 0.33;
                $socialImagesStatus = 'warning';
            }
        }
        
        // Round score to nearest integer
        $score = round($score);
        
        // Determine score color
        $scoreColor = 'danger';
        if ($score >= 80) {
            $scoreColor = 'success';
        } elseif ($score >= 60) {
            $scoreColor = 'warning';
        }
        
        return [
            'score' => $score,
            'score_color' => $scoreColor,
            'title_status' => $titleStatus,
            'meta_description_status' => $metaDescriptionStatus,
            'focus_keyword_status' => $focusKeywordStatus,
            'slug_status' => $slugStatus,
            'description_status' => $descriptionStatus,
            'description_word_count' => $descriptionWordCount,
            'images_status' => $imagesStatus,
            'social_images_status' => $socialImagesStatus,
        ];
    }

    private function uploadImage(
        Request            $request,
        UploadImageService $uploadImageService,
        FormInterface      $form,
        Product            $entity
    ): bool|string|\PN\MediaBundle\Entity\Image
    {
        $file = $form->get("image")->get("file")->getData();
        if (!$file instanceof UploadedFile) {
            return true;
        }

        return $uploadImageService->uploadSingleImage($entity->getPost(), $file, 1, $request);
    }

    /**
     * Find column index by trying multiple possible header names
     */
    private function findColumnIndex(array $headers, array $possibleNames): ?int
    {
        foreach ($possibleNames as $name) {
            $index = array_search($name, $headers);
            if ($index !== false) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Generate a URL-friendly slug from a string
     */
    private function generateSlug(string $title): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // If slug is empty, generate a fallback
        if (empty($slug)) {
            $slug = 'product-' . uniqid();
        }
        
        return $slug;
    }

    // ============================================================================
    // HELPER FUNCTIONS & UTILITIES (UPDATED/CREATED BY CURSOR)
    // ============================================================================



    /**
     * New Product Form Submission - Split from original new function
     * Updated by cursor on 2025-08-28 09:17:54 to handle form submission and database insert
     * Updated by cursor on 2025-08-28 09:25:45 to use saveProductToDatabase() for better separation of concerns
     * Updated by cursor on 2025-08-28 09:28:15 to use validateProductForm() for better validation separation
     * 
     * @Route("/new-submit/{category}", requirements={"category" = "\d+"}, name="product_new_submit", methods={"POST"})
     */
    public function newSubmit(
        Request            $request,
        ThreeSixtyViewService $threeSixtyViewService,
        CurrencyRepository $currencyRepository,
        Category           $category = null
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Product();
        $product->setCurrency($currencyRepository->getDefaultCurrency());
        $product->setDetails(new ProductDetails());
        $product->setCategory($category);

        // Handle form data manually
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validate the product using manual validation
            $validation = $this->validateProduct($product);

            if ($validation['isValid']) {
                // Save the product using the dedicated save function
                $savedProduct = $this->saveProductToDatabase($product, $threeSixtyViewService);

                $this->addFlash('success', 'Successfully saved');

                return $this->redirectToRoute('product_edit', ["id" => $savedProduct->getId()]);
            } else {
                // Add validation errors as flash messages
                foreach ($validation['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        // If form has errors, re-render the form with errors
        return $this->render('product/admin/product/new.html.twig', [
            'product' => $product,
            'currentCategory' => $product->getCategory(),
            'form' => $form->createView(),
            'use_split_form' => true,
        ]);
    }

    /**
     * Save Product to Database - Helper function for product persistence
     * Updated by cursor on 2025-08-28 09:22:30 to provide reusable product saving functionality
     * 
     * @param Product $product The product entity to save
     * @param ThreeSixtyViewService $threeSixtyViewService Service for 360° view creation
     * @return Product The saved product entity
     */
    public function saveProductToDatabase(Product $product, ThreeSixtyViewService $threeSixtyViewService): Product
    {
        // Ensure product has a 360° view
        if (!$product->getThreeSixtyView()) {
            $product->setThreeSixtyView($threeSixtyViewService->createNew());
        }

        // Persist the product to database
        $this->em()->persist($product);
        $this->em()->flush();

        return $product;
    }

    /**
     * Validate Product Manually - Helper function for manual product validation
     * Updated by cursor on 2025-08-28 09:28:15 to separate validation logic from submission handling
     * Updated by cursor on 2025-08-28 09:35:20 to perform manual validation without Symfony forms
     * 
     * @param Product $product The product entity to validate
     * @return array Returns validation result with 'isValid' boolean and 'errors' array
     */
    public function validateProduct(Product $product): array
    {
        $errors = [];

        // Validate product title
        if (!$product->getTitle() || trim($product->getTitle()) === '') {
            $errors[] = 'Product title is required';
        }

        // Validate category
        if (!$product->getCategory()) {
            $errors[] = 'Product category is required';
        }

        // Validate currency
        if (!$product->getCurrency()) {
            $errors[] = 'Product currency is required';
        }

        // Validate product details
        if (!$product->getDetails()) {
            $errors[] = 'Product details are required';
        }

        // Additional validation for product price if exists
        if ($product->getProductPrice() && $product->getProductPrice()->count() > 0) {
            foreach ($product->getProductPrice() as $price) {
                if ($price->getUnitPrice() < 0) {
                    $errors[] = 'Product price cannot be negative';
                }
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }

    // ============================================================================
    // GCData (Generate Core Data) API FUNCTIONS - CREATED BY CURSOR
    // ============================================================================

    /**
     * GCData (Generate Core Data) API endpoint to fetch product data from external API
     * Created by cursor on 2025-01-27 to add GCData functionality for fetching product data from external API
     * 
     * @Route("/gcdata/{id}", requirements={"id"="\d+"}, name="product_gcdata_fetch", methods={"POST"})
     * @param Product $product
     * @param Request $request
     * @return Response
     */
    public function gcdataFetch(Product $product, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            // Get product SKU and brand name
            $sku = $product->getSku();
            $brandName = $product->getBrand() ? $product->getBrand()->getTitle() : null;

            if (!$sku) {
                return $this->json([
                    'success' => false,
                    'error' => 'Product SKU is required'
                ]);
            }

            // Note: Brand is not actually used by the API, but we'll track it for user reference
            if (!$brandName) {
                $brandName = 'Not specified';
            }

            // Prepare API call to single product API
            $apiUrl = 'http://localhost:8013/single_product_api.php';
            
            // Per README: API expects a single 'url' param with full URL or SKU
            // Prefer URL from ProductCGD matched by productId (temp entry linked to this product)
            $lookup = $sku;
            try {
                $cgdByProduct = $this->em()->getRepository(ProductCGD::class)->findOneBy(['productId' => $product->getId()], ['created' => 'DESC']);
                if ($cgdByProduct && $cgdByProduct->getUrl() && filter_var($cgdByProduct->getUrl(), FILTER_VALIDATE_URL)) {
                    $lookup = $cgdByProduct->getUrl();
                } else {
                    // Fallback: lookup by SKU in CGD (most recent)
                    $cgdBySku = $this->em()->getRepository(ProductCGD::class)->findOneBy(['sku' => $sku], ['created' => 'DESC']);
                    if ($cgdBySku && $cgdBySku->getUrl() && filter_var($cgdBySku->getUrl(), FILTER_VALIDATE_URL)) {
                        $lookup = $cgdBySku->getUrl();
                    }
                }
            } catch (\Throwable $e) {
                // ignore and keep fallback
            }
            $payload = json_decode($request->getContent() ?? '', true);
            if (is_array($payload) && isset($payload['url']) && $payload['url'] && filter_var($payload['url'], FILTER_VALIDATE_URL)) {
                $lookup = trim((string) $payload['url']);
            }
            $postData = 'url=' . urlencode($lookup);

            // Make API call using cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return $this->json([
                    'success' => false,
                    'error' => 'API call failed: ' . $curlError
                ]);
            }

            if ($httpCode !== 200) {
                return $this->json([
                    'success' => false,
                    'error' => 'API returned error code: ' . $httpCode
                ]);
            }

            $apiData = json_decode($response, true);
            
            if (!$apiData) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid JSON response from API'
                ]);
            }

            // Check if API call was successful
            if (!isset($apiData['success']) || !$apiData['success']) {
                return $this->json([
                    'success' => false,
                    'error' => $apiData['error'] ?? 'API returned unsuccessful response',
                    'api_response' => $apiData
                ]);
            }

            // Extract product data from API response
            $productData = $apiData['product'] ?? null;
            if (!$productData) {
                return $this->json([
                    'success' => false,
                    'error' => 'No product data found in API response'
                ]);
            }

            // Extract product name and description from API response
            $productName = $productData['name'] ?? null;
            $productDescription = $productData['description'] ?? null;

            if (!$productName && !$productDescription) {
                return $this->json([
                    'success' => false,
                    'error' => 'No product name or description found in API response'
                ]);
            }

            // For now, just return the data without updating the product
            // User can choose to apply the changes via the frontend
            return $this->json([
                'success' => true,
                'data' => [
                    'current_title' => $product->getTitle(),
                    'suggested_title' => $productName,
                    'current_description' => $product->getPost() && $product->getPost()->getContent() 
                        ? ($product->getPost()->getContent()['description'] ?? '') 
                        : '',
                    'suggested_description' => $productDescription,
                    'sku' => $sku,
                    'brand' => $brandName,
                    // Additional API data
                    'api_images' => $productData['images'] ?? [],
                    'api_technical_specs' => $productData['technical_specs'] ?? [],
                    'api_price' => $productData['price'] ?? null,
                    'api_sku_confirmed' => $productData['sku'] ?? null
                ],
                'raw_api_response' => $apiData, // Include the raw API response for user review
                'api_request' => [
                    'url' => $apiUrl,
                    'method' => 'POST',
                    'payload' => $postData,
                    'http_code' => $httpCode
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GCData - Download selected images from API response and save to product post
     * Created by cursor on 2025-09-01 to allow selective image download
     *
     * @Route("/gcdata/{id}/images", requirements={"id"="\d+"}, name="product_gcdata_download_images", methods={"POST"})
     */
    public function gcdataDownloadImages(Product $product, Request $request, \PN\MediaBundle\Service\UploadImageService $uploadImageService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $payload = json_decode($request->getContent() ?? '', true);
            $images = is_array($payload) ? ($payload['images'] ?? []) : [];
            if (!is_array($images) || count($images) === 0) {
                return $this->json(['success' => false, 'error' => 'No images selected']);
            }

            // Ensure product has a Post
            $post = $product->getPost();
            if (!$post) {
                return $this->json(['success' => false, 'error' => 'Product has no post entity to attach images']);
            }

            $imageSettingId = 1; // Product post images setting
            $downloaded = 0;
            $attempted = count($images);
            foreach ($images as $index => $url) {
                if (!is_string($url) || trim($url) === '') {
                    continue;
                }
                $type = \PN\MediaBundle\Entity\Image::TYPE_MAIN;
                if ($index > 0) {
                    $type = \PN\MediaBundle\Entity\Image::TYPE_GALLERY;
                }
                try {
                    $result = $uploadImageService->uploadSingleImageByUrl($post, $url, $imageSettingId, null, $type);
                    if ($result instanceof \PN\MediaBundle\Entity\Image) {
                        $downloaded++;
                        // Explicitly set main image
                        if ($index === 0 && method_exists($product, 'setMainImage')) {
                            $product->setMainImage($result);
                            $this->em()->persist($product);
                        }
                    }
                } catch (\Throwable $e) {
                    // skip this url
                    continue;
                }
            }

            $this->em()->flush();

            $mainSet = false;
            if (method_exists($product, 'getMainImage')) {
                $mainSet = (bool) $product->getMainImage();
            }

            return $this->json([
                'success' => true,
                'downloaded' => $downloaded,
                'attempted' => $attempted,
                'main_set' => $mainSet
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update product with GCData (Generate Core Data) suggestions
     * Created by cursor on 2025-01-27 to apply GCData suggestions for updating product name and description
     * Updated by cursor on 2025-09-01: Return granular flags title_updated and description_updated
     * 
     * @Route("/gcdata/{id}/update", requirements={"id"="\d+"}, name="product_gcdata_update", methods={"POST"})
     * @param Product $product
     * @param Request $request
     * @return Response
     */
    public function gcdataUpdate(Product $product, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            
            $updateTitle = $data['update_title'] ?? false;
            $updateDescription = $data['update_description'] ?? false;
            $newTitle = $data['new_title'] ?? null;
            $newDescription = $data['new_description'] ?? null;

            $updated = false;
            $titleUpdated = false;
            $descriptionUpdated = false;

            if ($updateTitle && $newTitle) {
                $currentTitle = $product->getTitle();
                if ($currentTitle !== $newTitle) {
                    $product->setTitle($newTitle);
                    $updated = true;
                    $titleUpdated = true;
                }
            }

            if ($updateDescription && $newDescription) {
                if (!$product->getPost()) {
                    $post = new \App\ContentBundle\Entity\Post();
                    $product->setPost($post);
                    $this->em()->persist($post);
                }
                
                $content = $product->getPost()->getContent() ?: [];
                $currentDescription = $content['description'] ?? null;
                if ($currentDescription !== $newDescription) {
                    $content['description'] = $newDescription;
                    $product->getPost()->setContent($content);
                    $updated = true;
                    $descriptionUpdated = true;
                }
            }

            if ($updated) {
                $this->em()->persist($product);
                $this->em()->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'title_updated' => $titleUpdated,
                    'description_updated' => $descriptionUpdated
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => 'No updates were applied',
                    'title_updated' => false,
                    'description_updated' => false
                ]);
            }

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Force run GCData for all products with gcd_status = Ready
     * Created by: cursor
     * Date: 2025-09-01 00:15
     * Reason: Trigger background GCData batch and redirect to monitor page
     *
     * Updated by: cursor
     * Date: 2025-09-01 15:20
     * Reason: Seed general job only and redirect to jobless monitor
     * @Route("/gcdata/force-update-all", name="product_gcdata_force_update_all", methods={"POST"})
     */
    public function gcdataForceUpdateAll(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $jobId = 'gcdata_general';
        // Initialize general job file so the monitor page has something to read immediately
        $file = $this->getParameter('kernel.project_dir') . '/var/gcdata_jobs/' . $jobId . '.json';
        $dir = \dirname($file);
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $requestedBy = null;
        $user = $this->getUser();
        if ($user) {
            if (method_exists($user, 'getFullName') && $user->getFullName()) { $requestedBy = $user->getFullName(); }
            elseif (method_exists($user, 'getEmail') && $user->getEmail()) { $requestedBy = $user->getEmail(); }
            elseif (method_exists($user, 'getUsername') && $user->getUsername()) { $requestedBy = $user->getUsername(); }
        }
        @file_put_contents($file, json_encode([
            'status' => 'queued',
            'total' => 0,
            'processed' => 0,
            'errors' => 0,
            'batchId' => $jobId,
            'requestedBy' => $requestedBy,
            'requestedAt' => (new \DateTime())->format('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT));

        // Ensure there is work: mark all not Done as Ready
        try { $this->get(\App\ProductBundle\Service\ProductGCDBatchService::class)->markAllNotDoneAsReady(); } catch (\Throwable $e) { /* ignore */ }

        // Do NOT start the job here; user must click Force Cataloug Update on the monitor page

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('product_gcdata_monitor'),
            ]);
        }

        return $this->redirectToRoute('product_gcdata_monitor');
    }

    /**
     * Kickoff GCData job synchronously from web (monitor page triggers this in background)
     * Created by: cursor
     * Date: 2025-09-01 00:35
     *
     * Updated by: cursor
     * Date: 2025-09-01 15:20
     * Reason: Make run endpoint job-agnostic and use general job id
     * @Route("/gcdata/run", name="product_gcdata_run", methods={"POST"})
     */
    public function gcdataRun(\App\ProductBundle\Service\ProductGCDBatchService $batchService, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $jobId = 'gcdata_general';
        // Ensure there is work to do: mark all not Done as Ready
        try { $batchService->markAllNotDoneAsReady(); } catch (\Throwable $e) { /* ignore */ }

        // Run synchronously in this request so we are certain it executes; release session lock first
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        if ($request->hasSession()) {
            try { $request->getSession()->save(); } catch (\Throwable $e) { /* ignore */ }
        } else {
            @session_write_close();
        }

        // Optional limit from request body
        $limit = null;
        try {
            $payload = json_decode($request->getContent() ?? '', true);
            if (is_array($payload) && isset($payload['limit']) && is_numeric($payload['limit'])) {
                $limit = max(1, (int) $payload['limit']);
            }
        } catch (\Throwable $e) { /* ignore */ }

        try {
            $batchService->runAllReady($jobId, $limit);
            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Monitor page for GCData batch job
     * Created by: cursor
     * Date: 2025-09-01 00:15
     * Reason: Show live progress of the background job
     * Updated by: cursor
     * Date: 2025-09-01 15:25
     * Reason: Remove jobId usage; single general monitor
     *
     * @Route("/gcdata/monitor", name="product_gcdata_monitor", methods={"GET"})
     */
    public function gcdataMonitor(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        // Ensure general file exists with a seed
        $base = $this->getParameter('kernel.project_dir') . '/var/gcdata_jobs';
        if (!is_dir($base)) { @mkdir($base, 0777, true); }
        $generalFile = $base . '/gcdata_general.json';
        if (!is_file($generalFile)) {
            $ready = 0;
            try {
                $ready = (int) $this->em()->createQuery('SELECT COUNT(p.id) FROM App\\ProductBundle\\Entity\\Product p WHERE p.gcdStatus = :st')
                    ->setParameter('st', 'Ready')
                    ->getSingleScalarResult();
            } catch (\Throwable $e) { /* ignore */ }
            $seed = [
                'status' => 'pending',
                'total' => $ready,
                'processed' => 0,
                'updatedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ];
            @file_put_contents($generalFile, json_encode($seed, JSON_PRETTY_PRINT));
        }
        return $this->render('product/admin/product/gcdata_monitor.html.twig');
    }

    /**
     * GCData monitor home - trailing slash compatibility
     * Updated by: cursor
     * Date: 2025-09-01 15:25
     * Reason: Redirect to jobless monitor
     *
     * @Route("/gcdata/monitor/", name="product_gcdata_monitor_root", methods={"GET"})
     */
    public function gcdataMonitorRoot(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->redirectToRoute('product_gcdata_monitor');
    }

    /**
     * GCData general stats: counts of Ready and Generating products
     *
     * @Route("/gcdata/stats", name="product_gcdata_stats", methods={"GET"})
     */
    public function gcdataStats(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        try {
            $ready = (int) $this->em()->createQuery('SELECT COUNT(p.id) FROM App\\ProductBundle\\Entity\\Product p WHERE p.gcdStatus = :st')
                ->setParameter('st', 'Ready')
                ->getSingleScalarResult();
            $generating = (int) $this->em()->createQuery('SELECT COUNT(p.id) FROM App\\ProductBundle\\Entity\\Product p WHERE p.gcdStatus = :st')
                ->setParameter('st', 'Generating')
                ->getSingleScalarResult();
            return $this->json(['success' => true, 'ready' => $ready, 'generating' => $generating]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Poll endpoint to get GCData job progress
     * Created by: cursor
     * Date: 2025-09-01 00:15
     * Updated by: cursor
     * Date: 2025-09-01 15:25
     * Reason: Job-agnostic progress endpoint
     *
     * @Route("/gcdata/monitor/progress", name="product_gcdata_monitor_progress", methods={"GET"})
     */
    public function gcdataMonitorProgress(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $file = $this->getParameter('kernel.project_dir') . '/var/gcdata_jobs/gcdata_general.json';
        if (!is_file($file)) {
            return $this->json(['success' => false, 'error' => 'Job not found']);
        }
        $json = @file_get_contents($file);
        $data = json_decode($json ?: 'null', true);
        if (!$data) { $data = ['status' => 'pending']; }
        return $this->json(['success' => true, 'data' => $data]);
    }

    /**
     * Ready products count for GCData batch (gcd_status = Ready)
     *
     * @Route("/gcdata/ready-count", name="product_gcdata_ready_count", methods={"GET"})
     */
    public function gcdataReadyCount(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        try {
            $count = (int) $this->em()->createQuery('SELECT COUNT(p.id) FROM App\\ProductBundle\\Entity\\Product p WHERE p.gcdStatus = :st')
                ->setParameter('st', 'Ready')
                ->getSingleScalarResult();
            return $this->json(['success' => true, 'count' => $count]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Terminate an in-progress GCData batch job
     * Updated by: cursor
     * Date: 2025-09-01 15:25
     * Reason: Job-agnostic terminate endpoint
     *
     * @Route("/gcdata/monitor/terminate", name="product_gcdata_monitor_terminate", methods={"POST"})
     */
    public function gcdataMonitorTerminate(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $file = $this->getParameter('kernel.project_dir') . '/var/gcdata_jobs/gcdata_general.json';
        if (!is_file($file)) {
            return $this->json(['success' => false, 'error' => 'Job not found']);
        }
        $json = @file_get_contents($file);
        $data = json_decode($json ?: 'null', true) ?: [];
        $data['terminate'] = true;
        @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        return $this->json(['success' => true]);
    }

    /**
     * Fetch manufacturer URLs for products and save them into ProductCGD.url
     * Created by: cursor
     * Date: 2025-09-01 16:10
     * Reason: Button beside Force Update to populate manufacturer URLs
     *
     * @Route("/gcdata/fetch-urls", name="product_gcdata_fetch_urls", methods={"POST"})
     */
    public function gcdataFetchUrls(\App\ProductBundle\Service\ProductGCDBatchService $batchService, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        // Optional limit from request
        $limit = null;
        try {
            $payload = json_decode($request->getContent() ?? '', true);
            if (is_array($payload) && isset($payload['limit']) && is_numeric($payload['limit'])) {
                $limit = max(1, (int) $payload['limit']);
            }
        } catch (\Throwable $e) { /* ignore */ }

        $result = $batchService->fetchAndSaveManufacturerUrls($limit);
        $this->addFlash('success', sprintf('Manufacturer URLs - processed: %d, updated: %d, skipped: %d, errors: %d', $result['processed'], $result['updated'], $result['skipped'], $result['errors']));
        return $this->redirectToRoute('product_index');
    }

}
