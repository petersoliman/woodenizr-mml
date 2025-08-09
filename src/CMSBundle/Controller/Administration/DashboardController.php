<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Enum\ShippingStatusEnum;
use App\ECommerceBundle\Repository\OrderRepository;
use App\ECommerceBundle\Service\CartService;
use App\ECommerceBundle\Service\OrderService;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use App\UserBundle\Repository\UserRepository;
use PN\ServiceBundle\Utils\Date;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Dashboard controller.
 *
 * @Route("/")
 */
class DashboardController extends AbstractController
{

    /**
     * @Route("/", name="dashboard", methods={"GET"})
     */
    public function index(UserRepository $userRepository, OrderRepository $orderRepository): Response
    {
        $registeredUsers = $this->getNoOfUserRegisteredByYear($userRepository);

        $numberOfOrderOfAwaitingPaymentAndNotSuccess = $this->getNumberOfOrderOfAwaitingPaymentAndNotSuccess($orderRepository);
        $numberOfOrderPaidAndNotShipped = $this->getNumberOfOrderPaidAndNotShipped($orderRepository);
        $numberOfOrderWaitingToShipping = $this->getNumberOfOrderWaitingToShipping($orderRepository);

        return $this->render('cms/admin/dashboard/index.html.twig', [
            "registeredUsers" => $registeredUsers,
            "numberOfOrderOfAwaitingPaymentAndNotSuccess" => $numberOfOrderOfAwaitingPaymentAndNotSuccess,
            "numberOfOrderPaidAndNotShipped" => $numberOfOrderPaidAndNotShipped,
            "numberOfOrderWaitingToShipping" => $numberOfOrderWaitingToShipping,
        ]);
    }

    /**
     * @Route("/store-statistics", name="dashboard_store_statistics_ajax", methods={"GET"})
     */
    public function dashboardOrderStatisticsSnippet(Request $request, OrderRepository $orderRepository): Response
    {
        $type = $request->query->get('type');
        $startDate = $endDate = null;
        if ($type == "last30Days") {
            $startDate = date('d/m/Y', strtotime('today - 30 days'));
            $endDate = date('d/m/Y');
        }

        $numberOfOrders = $this->getNumberOfOrders($orderRepository, $startDate, $endDate);
        $averageOrderValue = $this->getAverageOrderValue($orderRepository, $startDate, $endDate);

        $totalSales = $this->getTotalSales($orderRepository, $startDate, $endDate);

        return $this->render('cms/admin/dashboard/orderStatisticsSnippet.html.twig', [
            "numberOfOrders" => $numberOfOrders,
            "averageOrderValue" => $averageOrderValue,
            "totalSales" => $totalSales,
        ]);
    }

    /**
     * @Route("/test", name="dashboard_test", methods={"GET"})
     */
    public function test(CartService $cartService, OrderService $orderService, UserRepository $userRepository): Response
    {
        /*$user = $userRepository->find(1);
        $cart = $cartService->getCart($user);*/
        return $this->render('cms/admin/dashboard/index.html.twig');
    }

    private function getNoOfUserRegisteredByYear(UserRepository $userRepository, $year = null): array
    {

        $year = ($year == null) ? date('Y') : $year;

        $numberOfMonths = 12;
        if ($year == date('Y')) {
            $numberOfMonths = date("n");
        }
        $data = [];
        for ($i = 1; $i <= $numberOfMonths; $i++) {
            $count = $userRepository->getNoOfUserRegisteredByMonthAndYear($i, $year);
            $monthName = Date::getMonthNameByNumber($i);
            $data[] = [
                "monthName" => $monthName,
                "value" => $count,
            ];
        }

        return $data;
    }

    private function getNumberOfOrders(OrderRepository $orderRepository, $startDate = null, $endDate = null): int
    {
        $search = new \stdClass;
        $search->from = $startDate;
        $search->to = $endDate;

        return $orderRepository->filter($search, true);
    }


    private function getNumberOfOrderOfAwaitingPaymentAndNotSuccess(OrderRepository $orderRepository): int
    {
        $search = new \stdClass;
        $search->state = [OrderStatusEnum::POSTPONE, OrderStatusEnum::NEW];
        $search->paymentState = PaymentStatusEnum::PENDING;

        return $orderRepository->filter($search, true);
    }

    private function getNumberOfOrderPaidAndNotShipped(OrderRepository $orderRepository): int
    {
        $search = new \stdClass;
        $search->shippingState = [ShippingStatusEnum::AWAITING_PROCESSING, ShippingStatusEnum::PROCESSING];
        $search->paymentState = PaymentStatusEnum::PAID;

        return $orderRepository->filter($search, true);
    }

    private function getNumberOfOrderWaitingToShipping(OrderRepository $orderRepository): int
    {
        $search = new \stdClass;
        $search->shippingState = [ShippingStatusEnum::PROCESSING];

        return $orderRepository->filter($search, true);
    }

    private function getAverageOrderValue(OrderRepository $orderRepository, $startDate, $endDate): float
    {
        $search = new \stdClass();
        $search->from = $startDate;
        $search->to = $endDate;

        return $orderRepository->getAverageOrderValue($search);
    }

    private function getTotalSales(OrderRepository $orderRepository, $startDate, $endDate): float
    {
        $search = new \stdClass();
        $search->from = $startDate;
        $search->to = $endDate;

        return $orderRepository->getTotalSuccessOrder($search);
    }
}
