<?php

namespace App\ReportBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Repository\OrderRepository;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Repository\ProductRepository;
use App\ReportBundle\Form\ProductSalesType;
use App\ReportBundle\Repository\ProductSalesRepository;
use PN\ServiceBundle\Lib\Paginator;
use PN\ServiceBundle\Utils\Date;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/product-sales")
 */
class ProductSalesController extends AbstractController
{
    /**
     * @Route("", name="report_product_index", methods={"GET"})
     */
    public function index(
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        ProductSalesRepository $productSalesRepository
    ): Response {
        $noOfProduct = $this->productCount($productRepository);
        $soldProductAndTotalSales = $productSalesRepository->getSoldProductAndTotalSales();
        $totalProductsInCart = $productSalesRepository->getTotalProductsInCart();
        $noOfProductFeatured = $this->productCount($productRepository, true);
        $noOfProductNotFeatured = $this->productCount($productRepository, false);
        $noOfProductPublish = $this->productCount($productRepository, null, true);
        $noOfProductNotPublish = $this->productCount($productRepository, null, false);
        $bestsellers = $productSalesRepository->bestsellers();
        $topProductsInCart = $productSalesRepository->topProductsInCart();

        return $this->render('report/admin/product/index.html.twig', [
            "startYear" => $this->getStartYear($orderRepository),
            "noOfProduct" => $noOfProduct,
            "soldProductAndTotalSales" => $soldProductAndTotalSales,
            "totalProductsInCart" => $totalProductsInCart,
            "noOfProductFeatured" => $noOfProductFeatured,
            "noOfProductNotFeatured" => $noOfProductNotFeatured,
            "noOfProductPublish" => $noOfProductPublish,
            "noOfProductNotPublish" => $noOfProductNotPublish,
            "bestsellers" => $bestsellers,
            "topProductsInCart" => $topProductsInCart,
        ]);
    }

    /**
     * @Route("/dashboard/order-statistics", name="report_product_dashboard_order_statistics_ajax", methods={"GET"})
     */
    public function indexOrderStatisticsSnippet(
        Request $request,
        ProductSalesRepository $productSalesRepository
    ): Response {
        $year = $request->query->get('year');
        $year = ($year == "") ? date('Y') : $year;
        $numberOfMonths = 12;
        if ($year == date('Y')) {
            $numberOfMonths = date("n");
        }

        $chartData = [];
        for ($i = 1; $i <= $numberOfMonths; $i++) {
            $count = $productSalesRepository->getTotalQtyAndTotalSales($i, $year);
            $monthName = Date::getMonthNameByNumber($i);
            $chartData[] = [
                "monthName" => $monthName,
                "value" => $count,
            ];
        }

        return $this->render('report/admin/product/dashboardOrderStatisticsSnippet.html.twig', [
            "chartData" => $chartData,
        ]);
    }

    /**
     * @Route("/product-items/{page}", requirements={"page": "\d+"}, name="report_product_order_items", methods={"GET"})
     */
    public function detailsItems(Request $request, ProductSalesRepository $productSalesRepository, $page = 1): Response
    {
        $filterForm = $this->createForm(ProductSalesType::class);
        $filterForm->handleRequest($request);
        $search = $this->collectProductOrderItemsParams($filterForm);

        $count = $productSalesRepository->productSalesFilter($search, true);
        $paginator = new Paginator($count, $page, 50);
        $entities = $productSalesRepository->productSalesFilter($search, false, $paginator->getLimitStart(),
            $paginator->getPageLimit());

        $totalQty = $productSalesRepository->productSalesFilter($search, totalQty: true);
        $totalSales = $productSalesRepository->productSalesFilter($search, totalSales: true);

        return $this->render('report/admin/product/orderItems.html.twig', [
            "search" => $search,
            "entities" => $entities,
            'paginator' => $paginator->getPagination(),
            "totalQty" => $totalQty,
            "totalSales" => $totalSales,
            "filter_form" => $filterForm->createView(),
        ]);
    }

    /**
     * @Route("/single-product-statistics/{id}", name="report_product_single_product_statistics_ajax", methods={"GET"})
     */
    public function singleProductStatisticsPopup(
        Request $request,
        OrderRepository $orderRepository,
        ProductSalesRepository $productSalesRepository,
        Product $product
    ): Response {
        $year = $request->query->get('year');
        $year = ($year == "") ? date('Y') : $year;
        $numberOfMonths = 12;
        if ($year == date('Y')) {
            $numberOfMonths = date("n");
        }


        $chartData = [];
        for ($i = 1; $i <= $numberOfMonths; $i++) {
            $count = $this->getProductTotalQtyAndTotalSales($productSalesRepository, $product, $i, $year);
            $monthName = Date::getMonthNameByNumber($i);
            $chartData[] = [
                "monthName" => $monthName,
                "value" => $count,
            ];
        }

        $twigFile = 'report/admin/product/singleProductSales.html.twig';
        if ($request->query->has('content')) {
            $twigFile = 'report/admin/product/singleProductSalesContent.html.twig';
        }

        return $this->render($twigFile, [
            "chartData" => $chartData,
            "product" => $product,
            "startYear" => $this->getStartYear($orderRepository),
        ]);
    }

    /**
     * @Route("/product-items/csv", requirements={"id": "\d+", "page": "\d+"}, name="report_product_order_items_csv", methods={"GET"})
     */
    public function productOrderItemsCSV(Request $request, ProductSalesRepository $productSalesRepository): Response
    {
        $filterForm = $this->createForm(ProductSalesType::class);
        $filterForm->handleRequest($request);
        $search = $this->collectProductOrderItemsParams($filterForm);
        $entities = $productSalesRepository->productSalesFilter($search);

        $list = [];

        $list[] = [
            "#",
            "Product",
            "Total Qty",
            "Total Sales",
            "No. of orders",
            "Last Time Purchased",
        ];

        foreach ($entities as $entity) {

            $list[] = [
                $entity->getId(),
                $entity->getTitle(),
                $entity->totalQty,
                number_format($entity->totalSales)." EGP",
                $entity->totalNoOfOrders,
                $entity->lastTimePurchased->format(Date::DATE_FORMAT3),
            ];
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
        header('Content-Disposition: attachment; filename="product-sales-report-'.date("Y-m-d").'.csv";');

        fpassthru($f);

        exit;
    }

    private function productCount(
        ProductRepository $productRepository,
        ?bool $featured = null,
        ?bool $publish = null
    ): int {

        $searchProduct = new \stdClass();
        $searchProduct->deleted = 0;
        if ($featured !== null) {
            $searchProduct->featured = $featured;
        }
        if ($publish !== null) {
            $searchProduct->publish = $publish;
        }

        return $productRepository->filter($searchProduct, true);
    }

    private function getStartYear(OrderRepository $orderRepository): int
    {
        $startYear = date("Y");
        $firstOrder = $orderRepository->findOneBy([], ["id" => "ASC"]);
        if ($firstOrder instanceof Order) {
            $startYear = $firstOrder->getCreated()->format("Y");
        }

        return $startYear;
    }

    private function collectProductOrderItemsParams(FormInterface $form): \stdClass
    {
        $search = new \stdClass();
        $search->orderId = $form->get("orderId")->getData();
        $search->productName = $form->get("productName")->getData();
        $search->startDate = $form->get("startDate")->getData();
        $search->endDate = $form->get("endDate")->getData();
        $search->featured = $form->get("featured")->getData();
        $search->publish = $form->get("publish")->getData();
        $search->ordr = $form->get("sortBy")->getData();

        return $search;
    }

    private function getProductTotalQtyAndTotalSales(
        ProductSalesRepository $productSalesRepository,
        Product $product,
        $month,
        $year
    ): \stdClass {
        $search = new \stdClass();
        $search->productId = $product->getId();
        $search->month = $month;
        $search->year = $year;

        return $productSalesRepository->productSalesFilter($search, totalSales: true, totalQty: true);
    }
}
