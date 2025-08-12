<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductDetails;
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

/**
 * Product controller.
 *
 * @Route("")
 */
class ProductController extends AbstractController
{
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
     * @Route("/new/{category}", requirements={"category" = "\d+"}, name="product_new", methods={"GET", "POST"})
     */
    public function new(
        Request            $request,
        UploadImageService $uploadImageService,
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

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setThreeSixtyView($threeSixtyViewService->createNew());
            $this->em()->persist($product);
            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $product);
            if ($uploadImage === false) {
                return $this->redirectToRoute('product_edit', ['id' => $product->getId()]);
            }

            if ($uploadImage instanceof Image) {
                $product->setMainImage($uploadImage);
                $this->em()->persist($product);
                $this->em()->flush();
            }

            if ($request->request->get("action") == "saveAndNext") {
                return $this->redirectToRoute('post_set_images', ['id' => $product->getPost()->getId()]);
            }

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('product_edit', ["id" => $product->getId()]);
        }

        return $this->render('product/admin/product/new.html.twig', [
            'product' => $product,
            'currentCategory' => $product->getCategory(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="product_edit", methods={"GET", "POST"})
     */
    public function edit(
        Request            $request,
        UploadImageService $uploadImageService,
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

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $product);
            if ($uploadImage === false) {
                return $this->redirectToRoute('product_edit', ['id' => $product->getId()]);
            }

            if ($uploadImage instanceof Image) {
                $product->setMainImage($uploadImage);
                $this->em()->persist($product);
                $this->em()->flush();
            }

            if ($request->request->get("action") == "saveAndNext") {
                return $this->redirectToRoute('post_set_images', ['id' => $product->getPost()->getId()]);
            }

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('product_edit', ["id" => $product->getId()]);
        }

        return $this->render('product/admin/product/edit.html.twig', [
            'product' => $product,
            'currentCategory' => $product->getCategory(),
            'form' => $form->createView(),
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
     * @Route("/upload/csv", requirements={"id" = "\d+"}, name="product_upload_csv", methods={"GET", "POST"})
     */
    public function uploadCSV(Request $request, UserService $userService): Response
    {
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


        $search = new \stdClass();
        $search->hasError = false;
        $noOfProductInQueue = $this->em()->getRepository(ProductInsertCronJobData::class)->filter($search, true);

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

            while (($data = fgetcsv($handle)) !== false) {
                $row++;
                if ($row == 1) {
                    continue;
                }
                $successRows++;
                $rowAsObject = $this->convertCsvRowToObject($data);
                $productInsertCronJobData = $this->em()->getRepository(ProductInsertCronJobData::class)->findOneBy(["sku" => $rowAsObject->sku]);
                if (!$productInsertCronJobData) {
                    $productInsertCronJobData = new ProductInsertCronJobData();
                }

                $productInsertCronJobData->setSku($rowAsObject->sku);
                $productInsertCronJobData->setTitle($rowAsObject->title);
                $productInsertCronJobData->setDescription($rowAsObject->description);
                $productInsertCronJobData->setCategoryId($rowAsObject->categoryId);
                //                $productInsertCronJobData->setPrice($rowAsObject->price);
                $productInsertCronJobData->setMaterial($rowAsObject->material);
                $productInsertCronJobData->setWidth($rowAsObject->width);
                $productInsertCronJobData->setHeight($rowAsObject->height);
                $productInsertCronJobData->setDepth($rowAsObject->depth);
                $productInsertCronJobData->setCreator($userService->getUserName());
                $productInsertCronJobData->setImageUrl($rowAsObject->imageUrl);
                $this->em()->persist($productInsertCronJobData);
            }

            $this->em()->flush();
            if ($successRows > 0) {
                $this->addFlash('success', "$successRows product(s) added in queue");
            }

            return $this->redirectToRoute('product_upload_csv');
        }

        return $this->render("product/admin/product/uploadCSV.html.twig", [
            'noOfProductInQueue' => $noOfProductInQueue,
            'form' => $form->createView(),
        ]);
    }

    private function convertCsvRowToObject($data)
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
        $clearComma = function ($str) {
            $str = trim($str);

            return (float)str_replace(",", '', $str);
        };


        $product = new \stdClass;
        $product->sku = isset($data[0]) ? $clearEscapedCharacters($data[0]) : null;
        $product->title = isset($data[1]) ? $clearEscapedCharacters($data[1]) : null;
        $product->description = isset($data[2]) ? $convertToHtml($data[2]) : null;
        $product->imageUrl = isset($data[3]) ? trim($data[3]) : null;
        $product->categoryId = isset($data[4]) ? trim($data[4]) : null;
        //        $product->price = isset($data[5]) ? $clearComma($data[5]) : null;
        $product->material = isset($data[6]) ? trim($data[6]) : null;
        $product->width = (isset($data[7]) and $data[7] != "") ? $clearComma($data[7]) : null;
        $product->height = (isset($data[8]) and $data[8] != "") ? $clearComma($data[8]) : null;
        $product->depth = (isset($data[9]) and $data[9] != "") ? $clearComma($data[9]) : null;

        return $product;
    }

    private function validateCsv($handle): bool
    {
        $return = true;
        $rowNumber = 0;
        while (($data = fgetcsv($handle)) != false) {
            if ($rowNumber == 0) {
                $rowNumber++;
                continue;
            }
            $rowAsObject = $this->convertCsvRowToObject($data);

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
            //            if (Validate::not_null($rowAsObject->price) and !is_numeric($rowAsObject->price)) {
            //                $this->addFlash('error', 'Row Number '.$rowNumber.', price must be a number');
            //                $return = false;
            //
            //            }
            if (Validate::not_null($rowAsObject->width) and !is_numeric($rowAsObject->width) and !is_float($rowAsObject->width)) {
                $this->addFlash('error', 'Row Number ' . $rowNumber . ', width must be a number');
                $return = false;
            }
            if (Validate::not_null($rowAsObject->height) and !is_numeric($rowAsObject->height) and !is_float($rowAsObject->height)) {
                $this->addFlash('error', 'Row Number ' . $rowNumber . ', height must be a number');
                $return = false;
            }
            if (Validate::not_null($rowAsObject->depth) and !is_numeric($rowAsObject->depth) and !is_float($rowAsObject->depth)) {
                $this->addFlash('error', 'Row Number ' . $rowNumber . ', depth must be a number');
                $return = false;
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
                'Title',
                'Description',
                'Image URL',
                'Sub category ID',
                'Price',
                'Material',
                'Width',
                'Height',
                'Depth',
            ],
            [
                '67368123-TABLE',
                'Table',
                'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
                'https://google.com/image.png',
                '33',
                '1500',
                'Material',
                '2',
                '3',
                '60',
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
        header('Content-Disposition: attachment; filename="product-' . date("Y-m-d") . '.csv";');

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

}
