<?php

namespace App\ECommerceBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Entity\OrderComment;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Enum\ShippingStatusEnum;
use App\ECommerceBundle\Form\OrderCommentType;
use App\ECommerceBundle\Form\OrderType;
use App\ECommerceBundle\Repository\OrderHasProductPriceRepository;
use App\ECommerceBundle\Repository\OrderRepository;
use App\ECommerceBundle\Service\OrderLogService;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use App\OnlinePaymentBundle\Repository\PaymentMethodRepository;
use App\OnlinePaymentBundle\Repository\PaymentRepository;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Repository\ProductPriceRepository;
use App\ShippingBundle\Repository\CityRepository;
use App\UserBundle\Entity\User;
use JetBrains\PhpStorm\NoReturn;
use PN\Bundle\ProductBundle\Entity\Product;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Order controller.
 *
 * @Route("/order")
 */
class OrderController extends AbstractController
{

    /**
     * Lists all Order entities.
     *
     * @Route("/", name="order_index", methods={"GET"})
     */
    public function index(
        Request                 $request,
        PaymentMethodRepository $paymentMethodRepository,
        CityRepository          $cityRepository
    ): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $search = $this->collectSearchData($request);

        $citySearch = new \stdClass;
        $citySearch->deleted = 0;
        $zones = $cityRepository->filter($citySearch);
        $paymentMethods = $paymentMethodRepository->findByActive();

        return $this->render('eCommerce/admin/order/index.html.twig', [
            "search" => $search,
            "zones" => $zones,
            "paymentMethods" => $paymentMethods,
        ]);
    }


    /**
     * @Route("/{id}/show", name="order_show", methods={"GET", "POST"})
     */
    public function show(
        Request           $request,
        OrderLogService   $orderLogService,
        PaymentRepository $paymentRepository,
        Order             $order
    ): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $oldEntity = clone $order;
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($order);
            $this->em()->flush($order);

            $orderLogService->createLog($order, $oldEntity);

            $this->addFlash('success', "Updated Successfully");

            return $this->redirectToRoute("order_show", ["id" => $order->getId()]);
        }

        $payments = $paymentRepository->findBy(
            ["objectId" => $order->getId(), "type" => PaymentTypesEnum::ORDER],
            ["id" => "DESC"]
        );

        $commentForm = $this->createForm(OrderCommentType::class, new OrderComment(), [
            "action" => $this->generateUrl("order_comment_new", ["id" => $order->getId()]),
        ]);

        $boughtTogether = [];
        if ($order->getParent() !== null) {
            $boughtTogether[] = $order->getParent()->getId();
            foreach ($order->getParent()->getChildren() as $child) {
                if($child->getId() == $order->getId()) {
                    continue;
                }
                $boughtTogether[] = $child->getId();
            }
        } else {
            foreach ($order->getChildren() as $child) {
                $boughtTogether[] = $child->getId();
            }
        }

        return $this->render('eCommerce/admin/order/show.html.twig', [
            'order' => $order,
            'payments' => $payments,
            'form' => $form->createView(),
            'comment_form' => $commentForm->createView(),
            'boughtTogether' => $boughtTogether
        ]);
    }

    /**
     * @Route("/product-price/delete/{id}/{productPrice}", name="order_product_price_delete", methods={"DELETE"})
     */
    public function productPriceDelete(
        OrderHasProductPriceRepository $orderHasProductPriceRepository,
        OrderLogService                $orderLogService,
        Order                          $order,
        ProductPrice                   $productPrice,
    ): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $orderHasProductPrice = $orderHasProductPriceRepository->findOneBy([
            "order" => $order,
            "productPrice" => $productPrice,
        ]);
        if (!$orderHasProductPrice) {
            throw $this->createNotFoundException();
        }

        if ($order->getOrderHasProductPrices()->count() <= 1) {
            $this->addFlash('error', 'The order must contain one product at least');

            return $this->redirectToRoute("order_show", ["id" => $order->getId()]);
        }

        $qty = $orderHasProductPrice->getQty();
        $unitPrice = $orderHasProductPrice->getUnitPrice();
        $totalPrice = $orderHasProductPrice->getTotalPrice();


        $newStock = $productPrice->getStock() + $qty;
        $productPrice->setStock($newStock);
        $this->em()->persist($productPrice);


        $message = "Remove Product <strong>" . $orderHasProductPrice->getProductPrice()->getProduct()->getTitle() . "</strong> with quantity " . $qty . " ,  unit price " . $unitPrice . " and  total price " . $totalPrice;
        $orderLogService->addLogInDB($order, $message);


        $order->setSubTotal($order->getSubTotal() - $totalPrice);
        $order->setTotalPrice($order->getTotalPrice() - $totalPrice);
        $this->em()->persist($order);

        $this->em()->remove($orderHasProductPrice);
        $this->em()->flush();
        $this->addFlash('success', 'The product deleted successfully');

        return $this->redirectToRoute("order_show", ["id" => $order->getId()]);
    }

    /**
     * @Route("/product-price/replace/{id}/{productPrice}", name="order_product_price_replace", methods={"POST"})
     */
    public function replaceProduct(
        Request                        $request,
        OrderHasProductPriceRepository $orderHasProductPriceRepository,
        ProductPriceRepository         $productPriceRepository,
        OrderLogService                $orderLogService,
        Order                          $order,
        ProductPrice                   $productPrice,
    ): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $orderHasProductPrice = $orderHasProductPriceRepository->findOneBy([
            "order" => $order,
            "productPrice" => $productPrice,
        ]);
        if (!$orderHasProductPrice) {
            throw $this->createNotFoundException();
        }

        $productPriceId = $request->request->get("productPriceId");
        if (!$productPriceId) {
            $this->addFlash("error", "Please select the product");
            return $this->redirectToRoute("order_show", ["id" => $order->getId()]);
        }
        $newProductPrice = $productPriceRepository->find($productPriceId);
        if (!$newProductPrice instanceof ProductPrice) {
            $this->addFlash("error", "Please select the product");
            return $this->redirectToRoute("order_show", ["id" => $order->getId()]);

        }
        $oldProduct = $productPrice->getProduct();
        $newProduct = $newProductPrice->getProduct();


        $orderHasProductPrice->setProductPrice($newProductPrice);
        $this->em()->persist($orderHasProductPrice);
        $this->em()->flush();

        $message = "Replace Product <strong>" . $oldProduct->getTitle() . " ( " . $oldProduct->getVendor()->getTitle() . " )</strong> with <strong>" . $newProduct->getTitle() . " (" . $newProduct->getVendor()->getTitle() . ")</strong>";
        $orderLogService->addLogInDB($order, $message);


        $this->em()->flush();
        $this->addFlash('success', 'The product replaced successfully');

        return $this->redirectToRoute("order_show", ["id" => $order->getId()]);
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="order_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, OrderRepository $orderRepository): Response
    {

        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");


        $search = $this->collectSearchData($request);
        if (Validate::not_null($srch['value'])) {
            $search->string = $srch['value'];
        }
        $search->ordr = $ordr[0];


        $count = $orderRepository->filter($search, true);
        $orders = $orderRepository->filter($search, false, $start, $length);

        return $this->render("eCommerce/admin/order/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "orders" => $orders,
        ]);
    }

    /**
     * @Route("/{id}", requirements={"id": "\d+"}, name="order_comment_new", methods={"POST"})
     */
    public function newComment(Request $request, UserService $userService, Order $order): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $orderComment = new OrderComment();
        $commentForm = $this->createForm(OrderCommentType::class, $orderComment, [
            "action" => $this->generateUrl("order_comment_new", ["id" => $order->getId()]),
        ]);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $orderComment->setOrder($order);
            $userName = $userService->getUserName();
            $orderComment->setCreator($userName);
            $this->em()->persist($orderComment);
            $this->em()->flush();

            $this->addFlash('success', "Your comment added successfully");
        }

        return $this->redirect($this->generateUrl("order_show", ["id" => $order->getId()]) . "#comments-section");
    }

    /**
     * @Route("/print", name="order_print", methods={"GET"})
     */
    public function print(Request $request, OrderRepository $orderRepository): Response
    {
        $orderIds = $request->query->get("ids");
        if (!is_array($orderIds) and Validate::not_null($orderIds)) {
            $orderIds = [$orderIds];
        } else {
            if (!is_array($orderIds)) {
                $orderIds = [];
            }
        }
        $orders = [];
        if (count($orderIds)) {
            $orders = $orderRepository->findBy(["id" => $orderIds]);
        }

        return $this->render("eCommerce/admin/order/print.html.twig", [
            "orders" => $orders,
        ]);
    }

    /**
     * @Route("/export-csv", name="order_export_csv", methods={"GET"})
     */
    #[NoReturn] public function exportCSV(Request $request, OrderRepository $orderRepository): void
    {
        $orderIds = $request->query->get("ids");
        if (!is_array($orderIds) and Validate::not_null($orderIds)) {
            $orderIds = [$orderIds];
        } else {
            if (!is_array($orderIds)) {
                $orderIds = [];
            }
        }

        $list[] = [
            "#",
            "Name",
            "Email",
            "Phone Number",
            "Payment Method",
            "Product Name",
            "Qty",
            "Total price",
        ];
        $orders = $orderRepository->findBy(["id" => $orderIds]);
        foreach ($orders as $order) {
            $list = array_merge($list, $this->exportCsvRow($order));
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
        header('Content-Disposition: attachment; filename="orders-' . date("Y-m-d") . '.csv";');

        fpassthru($f);

        exit;
    }

    private function exportCsvRow(Order $order): array
    {
        $result = [];

        foreach ($order->getOrderHasProductPrices() as $orderHasProductPrice) {
            $productPrice = $orderHasProductPrice->getProductPrice();
            $product = $productPrice->getProduct();
            $productName = $product->getTitle();
            if ($productPrice->getTitle() != null) {
                $productName .= " - " . $productPrice->getTitle();
            }

            $result[] = [
                $order->getId(),
                $order->getBuyerName(),
                $order->getBuyerEmail(),
                $order->getBuyerMobileNumber(),
                $order->getPaymentMethod()->getTitle(),
                $productName,
                $orderHasProductPrice->getQty(),
                $orderHasProductPrice->getTotalPrice(),
            ];
        }

        return $result;
    }

    /**
     * @Route("/update-order-status", defaults={"_format": "json"}, name="order_update_order_status_ajax", methods={"POST"})
     */
    public function updateOrderStatusAjax(
        Request         $request,
        OrderRepository $orderRepository,
        OrderLogService $orderLogService
    ): Response
    {

        $orderId = $request->request->getInt('orderId');
        $orderIds = $request->request->all('orderIds');
        $newState = $request->request->get('state');

        if ((!Validate::not_null($orderId) and !Validate::not_null($orderIds)) or !Validate::not_null($newState)) {
            return $this->json(['error' => 1, "message" => "Please enter orderId and state"]);
        }
        $newState = OrderStatusEnum::tryFrom((string)$newState);
        if (!$newState) {
            return $this->json(['error' => 1, "message" => "Invalid State"]);
        }

        if (Validate::not_null($orderId)) {
            $orderIds = [$orderId];
        }

        $orders = $orderRepository->findBy(["id" => $orderIds]);
        foreach ($orders as $order) {
            $orderLogService->updateOrderStatus($order, $newState);
        }

        $html = null;
        if (isset($order)) {
            $html = $this->renderView("eCommerce/admin/order/orderStatusDropdown.html.twig", ['order' => $order]);
        }

        return $this->json(['error' => 0, "message" => "Updated Successfully", "html" => $html]);
    }

    /**
     * @Route("/update-payment-status", defaults={"_format": "json"}, name="order_update_payment_status_ajax", methods={"POST"})
     */
    public function updatePaymentStatusAjax(
        Request         $request,
        OrderRepository $orderRepository,
        OrderLogService $orderLogService
    ): Response
    {
        $orderId = $request->request->getInt('orderId');
        $orderIds = $request->request->all('orderIds');
        $newState = $request->request->get('state');

        if ((!Validate::not_null($orderId) and !Validate::not_null($orderIds)) or !Validate::not_null($newState)) {
            return $this->json(['error' => 1, "message" => "Please enter orderId and state"]);
        }
        $newState = PaymentStatusEnum::tryFrom((string)$newState);
        if (!$newState) {
            return $this->json(['error' => 1, "message" => "Invalid State"]);
        }

        if (Validate::not_null($orderId)) {
            $orderIds = [$orderId];
        }

        $orders = $orderRepository->findBy(["id" => $orderIds]);
        foreach ($orders as $order) {
            $orderLogService->updatePaymentStatus($order, $newState);
        }

        $html = null;
        if (isset($order)) {
            $html = $this->renderView("eCommerce/admin/order/paymentStatusDropdown.html.twig", ['order' => $order]);
        }

        return $this->json(['error' => 0, "message" => "Updated Successfully", "html" => $html]);
    }

    /**
     * @Route("/update-shipping-status", defaults={"_format": "json"}, name="order_update_shipping_status_ajax", methods={"POST"})
     */
    public function updateShippingStatusAjax(
        Request         $request,
        OrderRepository $orderRepository,
        OrderLogService $orderLogService
    ): Response
    {
        $orderId = $request->request->getInt('orderId');
        $orderIds = $request->request->all('orderIds');
        $newState = $request->request->get('state');

        if ((!Validate::not_null($orderId) and !Validate::not_null($orderIds)) or !Validate::not_null($newState)) {
            return $this->json(['error' => 1, "message" => "Please enter orderId and state"]);
        }

        $newState = ShippingStatusEnum::tryFrom((string)$newState);
        if (!$newState) {
            return $this->json(['error' => 1, "message" => "Invalid State"]);
        }

        if (Validate::not_null($orderId)) {
            $orderIds = [$orderId];
        }

        $orders = $orderRepository->findBy(["id" => $orderIds]);
        foreach ($orders as $order) {
            $orderLogService->updateShippingStatus($order, $newState);
        }

        $html = null;
        if (isset($order)) {
            $html = $this->renderView("eCommerce/admin/order/shippingStatusDropdown.html.twig", ['order' => $order]);
        }

        return $this->json(['error' => 0, "message" => "Updated Successfully", "html" => $html]);
    }


    private function collectSearchData(Request $request): \stdClass
    {
        $order = new \stdClass;
        $order->string = $request->query->get('str');
        $order->minPrice = $request->query->get('minPrice');
        $order->maxPrice = $request->query->get('maxPrice');
        $order->state = $request->query->get('state');
        $order->shippingState = $request->query->get('shipping_state');
        $order->paymentState = $request->query->get('payment_state');
        $order->paymentMethod = $request->query->get('payment_method');
        $order->from = $request->query->get('from');
        $order->to = $request->query->get('to');
        $order->user = $request->query->get('user');
        $order->zone = $request->query->get('zone');
        $order->hasSearch = false;
        foreach ($order as $value) {
            if (Validate::not_null($value)) {
                $order->hasSearch = true;
                break;
            }
        }

        return $order;
    }
}
