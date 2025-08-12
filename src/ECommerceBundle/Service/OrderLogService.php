<?php

namespace App\ECommerceBundle\Service;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Entity\OrderLog;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Enum\ShippingStatusEnum;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UserService;

class OrderLogService
{
    private EntityManagerInterface $em;
    private UserService $userService;
    private OrderEmailService $orderEmailService;

    public function __construct(
        EntityManagerInterface $em,
        UserService            $userService,
        OrderEmailService      $orderEmailService
    )
    {
        $this->em = $em;
        $this->userService = $userService;
        $this->orderEmailService = $orderEmailService;
    }

    public function updateOrderStatus(Order $order, OrderStatusEnum $newState): void
    {
        $oldEntity = clone $order;
        $order->setState($newState);
        $this->em->persist($order);
        $this->createLog($order, $oldEntity);
    }

    public function updateShippingStatus(Order $order, ShippingStatusEnum $newState): void
    {
        $oldEntity = clone $order;
        $order->setShippingState($newState);
        $this->em->persist($order);
        $this->createLog($order, $oldEntity);
    }

    public function updatePaymentStatus(Order $order, PaymentStatusEnum $newState): void
    {
        $oldEntity = clone $order;
        $order->setPaymentState($newState);
        $this->em->persist($order);
        $this->createLog($order, $oldEntity);
    }

    public function addLogInDB(Order $order, string $text): void
    {
        $logEntity = new OrderLog();
        $logEntity->setOrder($order);
        $logEntity->setText($text);
        $logEntity->setCreator($this->userService->getUserName());
        $this->em->persist($logEntity);
        $this->em->flush();
    }

    public function createLog(Order $newEntity, Order $oldEntity): void
    {
        if ($newEntity->getState() != $oldEntity->getState()) {
            $stateText = $oldEntity->getStateName();
            $stateToText = $newEntity->getStateName();

            $message = "Change order state from " . $stateText . " to " . $stateToText;
            $this->addLogInDB($newEntity, $message);
            $this->orderEmailService->sendEmailWhenChangeStatus($newEntity, "Status", $stateToText);
        }
        if ($newEntity->getPaymentState() != $oldEntity->getPaymentState()) {
            $stateText = $oldEntity->getPaymentStateName();
            $stateToText = $newEntity->getPaymentStateName();

            $message = "Change payment state from " . $stateText . " to " . $stateToText;
            $this->addLogInDB($newEntity, $message);
            $this->orderEmailService->sendEmailWhenChangeStatus($newEntity, "Payment Status", $stateToText);
        }
        if ($newEntity->getWaybillNumber() != $oldEntity->getWaybillNumber()) {
            $message = match (true) {
                $oldEntity->getWaybillNumber() === null => 'Added waybill number "' . $newEntity->getWaybillNumber() . '"',
                $newEntity->getWaybillNumber() === null => 'Removed the waybill number "' . $oldEntity->getWaybillNumber() . '"',
                default => 'Changed the waybill number from "' . $oldEntity->getWaybillNumber() . '" to "' . $newEntity->getWaybillNumber() . '"'
            };

            $this->addLogInDB($newEntity, $message);
        }


    }
}