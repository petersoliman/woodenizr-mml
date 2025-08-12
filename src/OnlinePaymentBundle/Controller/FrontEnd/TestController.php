<?php

namespace App\OnlinePaymentBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\OnlinePaymentBundle\Entity\Payment;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use App\OnlinePaymentBundle\Gateway\PaymobService;
use App\OnlinePaymentBundle\Repository\PaymentMethodRepository;
use App\OnlinePaymentBundle\Repository\PaymentRepository;
use App\OnlinePaymentBundle\Service\PaymentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/payment-paymob")
 */
class TestController extends AbstractController
{
    /**
     * @Route("/test", name="fe_test", methods={"GET","POST"})
     */
    public function test(
        PaymentService $paymentService,
        PaymobService $paymobService,
        PaymentMethodRepository $paymentMethodRepository
    ): Response {

        $payment = $paymentService->create(
            amount: 510,
            userFirstName: "Peter",
            userLastName: "Nassef",
            userEmail: "peter.nassef@gmail.com",
            userPhone: "01225616354",
            info: "Test Info",
            gateway: $paymobService->getProviderName(),
            paymentType: PaymentTypesEnum::ORDER,
            paymentObjectId: 10,
            paymentMethod: $paymentMethodRepository->findOneByActiveType(PaymentMethodEnum::CREDIT_CARD)
        );

        $this->em()->persist($payment);
        $this->em()->flush();
        $pay = $paymobService->pay($payment);

        $payment->setSentData($pay->getSentData());
        $this->em()->persist($payment);
        $this->em()->flush();
        if ($pay->isHTML()) {
            return new Response($pay->getHtml());
        }

        return $this->redirect($pay->getRedirectUrl());
    }


    /**
     * @Route("/test-transaction-processed", name="fe_test_transaction_processed", methods={"POST"})
     */
    public function testTransactionProcessed(
        Request $request,
        PaymentService $paymentService,
        PaymobService $paymobService,
        PaymentRepository $paymentRepository
    ) {
        $verifiedData = $paymobService->verify($request);
        if (!$verifiedData->getMerchantOrderId()) {
            return new Response("Done");
        }
        $payment = $paymentRepository->find($verifiedData->getMerchantOrderId());

        if ($payment instanceof Payment) {
            $payment->setReceivedData($verifiedData->getProcessData());
            $payment->setTxnMessage($verifiedData->getMessage());
            if (isset($verifiedData->getProcessData()["id"])) {
                $payment->setTransactionNo($verifiedData->getProcessData()["id"]);
            }
            if (isset($verifiedData->getProcessData()["data"]["txn_response_code"])) {
                $payment->setTxnResponseCode($verifiedData->getProcessData()["data"]["txn_response_code"]);
            }
            $payment->setSuccess($verifiedData->isSuccess());

            $this->em()->persist($payment);
            $this->em()->flush();
        }

        return new Response("Done");
    }

    /**
     * @Route("/test-refund", name="fe_test_transaction_refund", methods={"GET"})
     */
    public function testRefundProcessed(
        Request $request,
        PaymentService $paymentService,
        PaymobService $paymobService,
        PaymentRepository $paymentRepository
    ) {
        $payment = $paymentRepository->find(20);

        $refund = $paymobService->refund($payment);
        if ($payment instanceof Payment) {
            $payment->setRefunded($refund->isSuccess());
            $this->em()->persist($payment);
            $this->em()->flush();
        }

        return new Response("Done");
    }

    /**
     * @Route("/test-transaction-response", name="fe_test_transaction_response", methods={"GET"})
     */
    public function testTransactionResponse(
        Request $request,
        PaymobService $paymobService,
        PaymentRepository $paymentRepository
    ) {
        $verifiedData = $paymobService->verify($request);
        dump($verifiedData);
        $isPending = $request->get('pending');

        $payment = $paymentRepository->find($verifiedData->getMerchantOrderId());

        if ($payment instanceof Payment) {
            if ($isPending == "true") {
                return $this->redirectToRoute("fe_payment_paymob_waiting_transaction", ["uuid" => $payment->getUuid()]);
            }
        }

        // redirect to thank you or failure page
        return new Response("Done");
    }
}
