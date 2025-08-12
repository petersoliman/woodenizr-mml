<?php

namespace App\OnlinePaymentBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Event\ChangeOrderStatusAfterOnlinePaymentEvent;
use App\OnlinePaymentBundle\Entity\Payment;
use App\OnlinePaymentBundle\Gateway\PaymobService;
use App\OnlinePaymentBundle\Interfaces\PaymentGatewayInterface;
use App\OnlinePaymentBundle\Model\PaymentGatewayVerifyResponse;
use App\OnlinePaymentBundle\Repository\PaymentRepository;
use App\OnlinePaymentBundle\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/payment")
 */
class PaymobController extends AbstractController
{
    private PaymentGatewayInterface $paymentGatewayService;

    public function __construct(EntityManagerInterface $em, PaymobService $paymentGatewayService)
    {
        parent::__construct($em);
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * @Route("/pay/{uuid}", name="fe_payment_paymob_pay", methods={"GET"})
     */
    public function payAction(
        PaymentRepository $paymentRepository,
                          $uuid
    ): Response
    {
        $payment = $paymentRepository->findOneBy(["uuid" => $uuid, "transactionNo" => null]);
        if (!$payment instanceof Payment) {
            throw $this->createNotFoundException();
        }
        $pay = $this->paymentGatewayService->pay($payment);

        $payment->setSentData($pay->getSentData());
        $this->em()->persist($payment);
        $this->em()->flush();
        if ($pay->isHTML()) {
            return new Response($pay->getHtml());
        }

        return $this->redirect($pay->getRedirectUrl());
    }

    /**
     * @Route("/process-callback", name="paymob_process_pay_callback", methods={"POST"})
     */
    public function processCallbackAction(
        Request                  $request,
        EventDispatcherInterface $eventDispatcher,
        PaymentRepository        $paymentRepository
    ): Response
    {
        if ($request->query->get('hmac') == null) {
            throw $this->createAccessDeniedException();
        }
        $requestBodyData = $this->convertBodyToRequest($request);
        if ($requestBodyData->type == "TOKEN") {
            return new Response('Thank you');
        }
        $verifyResponse = $this->paymentGatewayService->verify($request);
        if (!$verifyResponse->getMerchantOrderId()) {
            return new Response("Invalid merchant_order_id");
        }

        $payment = $paymentRepository->find($verifyResponse->getMerchantOrderId());
        if (!$payment instanceof Payment) {
            return new Response("Done with not action");
        }

        $this->saveTransactionData($payment, $verifyResponse);

        $event = new ChangeOrderStatusAfterOnlinePaymentEvent($payment);
        $eventDispatcher->dispatch($event, ChangeOrderStatusAfterOnlinePaymentEvent::NAME);

        return new Response('Thank you');
    }

    /**
     * @Route("/response-callback", name="fe_payment_paymob_process_pay", methods={"GET"})
     */
    public function responseCallback(
        Request           $request,
        PaymentService    $paymentService,
        PaymentRepository $paymentRepository
    ): Response
    {
        if ($request->query->get('hmac') == null) {
            throw $this->createAccessDeniedException();
        }

        $verifyResponse = $this->paymentGatewayService->verify($request);
        if (!$verifyResponse->getMerchantOrderId()) {
            return new Response("Thanks");
        }

        $payment = $paymentRepository->find($verifyResponse->getMerchantOrderId());
        if (!$payment instanceof Payment) {
            throw $this->createAccessDeniedException();
        }
        $isPending = $request->get('pending');

        if ($isPending == "true") {
            return $this->redirectToRoute("fe_payment_paymob_waiting_transaction", ["uuid" => $payment->getUuid()]);
        }

        return $paymentService->redirectAfterCallback($payment);
    }

    /**
     *
     * @Route("/waiting-transaction/{uuid}", name="fe_payment_paymob_waiting_transaction", methods={"GET"})
     */
    public function waitingProcessCallbackRequest(PaymentService $paymentService, Payment $payment): Response
    {
        if ($payment->getTxnResponseCode() === null) {
            return $this->render('onlinePayment/frontEnd/payMob/waitingTransaction.html.twig', [
                "payment" => $payment,
            ]);
        }

        return $paymentService->redirectAfterCallback($payment);
    }

    private function saveTransactionData(Payment $payment, PaymentGatewayVerifyResponse $verifyResponse): void
    {
        $payment->setReceivedData($verifyResponse->getProcessData());
        $payment->setTxnMessage($verifyResponse->getMessage());
        if (isset($verifyResponse->getProcessData()["id"])) {
            $payment->setTransactionNo($verifyResponse->getProcessData()["id"]);
        }
        if (isset($verifyResponse->getProcessData()["data"]["txn_response_code"])) {
            $payment->setTxnResponseCode($verifyResponse->getProcessData()["data"]["txn_response_code"]);
        }
        $payment->setSuccess($verifyResponse->isSuccess());

        $this->em()->persist($payment);
        $this->em()->flush();

    }

    private function convertBodyToRequest(Request $request)
    {
        $content = $request->getContent();
        if (!Validate::isJson($content)) {
            $returnArray = array("error" => 1, "message" => "The request body not content json format");
            $return = json_encode($returnArray, true);
            exit($return);
        }

        return json_decode($content);
    }
}
