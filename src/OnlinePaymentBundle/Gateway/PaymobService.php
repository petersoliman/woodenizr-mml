<?php

namespace App\OnlinePaymentBundle\Gateway;

use App\OnlinePaymentBundle\Entity\Payment;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use App\OnlinePaymentBundle\Interfaces\PaymentGatewayInterface;
use App\OnlinePaymentBundle\Model\PaymentGatewayPayResponse;
use App\OnlinePaymentBundle\Model\PaymentGatewayRefundResponse;
use App\OnlinePaymentBundle\Model\PaymentGatewayVerifyResponse;
use PN\ServiceBundle\Utils\General;
use PN\ServiceBundle\Utils\Validate;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaymobService implements PaymentGatewayInterface
{
    const PROVIDER_NAME = "paymob";
    private ?string $paymentToken = null;

    private LoggerInterface $logger;
    private HttpClientInterface $client;

    private string $apiKey;
    private string $secretKey;
    private string $publicKey;
    private string $integrationId;
    private string $iframeId;
    private mixed $valUIntegrationId;
    private mixed $valUIframeId;
    private string $currency;


    public function __construct(LoggerInterface $logger, HttpClientInterface $client)
    {
        $this->logger = $logger;
        $this->client = $client;

        $this->apiKey = $_ENV["PAYMOB_API_KEY"];
        $this->secretKey = $_ENV["PAYMOB_SECRET_KEY"];
        $this->publicKey = $_ENV["PAYMOB_PUBLIC_KEY"];
        $this->integrationId = $_ENV["PAYMOB_INTEGRATION_ID"];
        $this->iframeId = $_ENV["PAYMOB_IFRAME_ID"];
        $this->valUIntegrationId = $_ENV["PAYMOB_VALU_INTEGRATION_ID"];
        $this->valUIframeId = $_ENV["PAYMOB_VALU_IFRAME_ID"];
        $this->currency = $_ENV["PAYMOB_CURRENCY"];
    }

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }


    public function pay(Payment $payment): PaymentGatewayPayResponse
    {
        $integrationId = $this->getPaymentIntegrationId($payment);
        $merchantOrderId = (new General)->generateRandomString(4) . "-" . $payment->getId();

        $userData = $payment->getUserData();
        $firstName = $lastName = $phone = $email = "N/A";
        if (array_key_exists("firstName", $userData)) {
            $firstName = $userData["firstName"];
        }
        if (array_key_exists("lastName", $userData)) {
            $lastName = $userData["lastName"];
        }
        if (array_key_exists("phone", $userData)) {
            $phone = $userData["phone"];
        }
        if (array_key_exists("email", $userData)) {
            $email = $userData["email"];
        }

        if (!Validate::not_null($firstName)) {
            $firstName = "N/A";
        }
        if (!Validate::not_null($lastName)) {
            $lastName = "N/A";
        }
        if (Validate::not_null($firstName) and $lastName == "N/A") {
            $lastName = $firstName;
        }

        $data = [
            "amount" => (int)$payment->getAmountCents(), //Total amount in cents/piasters
            "currency" => $this->currency,
            "payment_methods" => [(int)$integrationId],
            "special_reference" => $merchantOrderId,
            "items" => [
                [
                    "name" => "Item name",
                    "amount" => (int)$payment->getAmountCents(),
                    "description" => "Item description",
                    "quantity" => 1
                ]
            ],
            "billing_data" => [
                "apartment" => "10",
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => $phone,
                'email' => $email,
                "street" => "New Cairo",
                "building" => "8028",
                "city" => "Cairo",
                "country" => "EG",
                "floor" => "1",
                "state" => $merchantOrderId
            ]
        ];
        $url = 'https://accept.paymob.com/v1/intention/';
        $headers = [
            "Authorization: Token {$this->secretKey}",
            "Content-Type: application/json",
        ];

        $response = $this->makePOSTRequest($url, $data, $headers);
        if (!array_key_exists("client_secret", $response)) {
            $this->logger->critical("Invalid Payment Response #3 - PaymentId #" . $payment->getId());
            $this->logger->critical(json_encode($response));
        }


        $paymentGatewayPayResponse = new PaymentGatewayPayResponse();
        $paymentGatewayPayResponse->setPaymentId($response['id']);
        $paymentGatewayPayResponse->setSentData($data);
        $redirectUrl = "https://accept.paymob.com/unifiedcheckout/?publicKey={$this->publicKey}&clientSecret={$response['client_secret']}";
        $paymentGatewayPayResponse->setRedirectUrl($redirectUrl);

        return $paymentGatewayPayResponse;
    }

    private function getPaymentIntegrationId(Payment $payment): string
    {
        $integrationId = $this->integrationId;
        if ($payment->getPaymentMethod()->getType() == PaymentMethodEnum::ValU) {
            $integrationId = $this->valUIntegrationId;
        }
        return $integrationId;
    }

    //Todo  :test this fun
    public function verify(Request $request): PaymentGatewayVerifyResponse
    {
        $hmac = $request->query->get("hmac");
        if ($request->isMethod("POST")) {
            $requestBodyData = $this->convertBodyToRequest($request);
            $queries = $this->convertObjectToArray($requestBodyData->obj);
        } else {
            $queries = $request->query->all();
        }

        $filteredQueriesParams = [
            "amount_cents",
            "created_at",
            "currency",
            "error_occured",
            "has_parent_transaction",
            "id",
            "integration_id",
            "is_3d_secure",
            "is_auth",
            "is_capture",
            "is_refunded",
            "is_standalone_payment",
            "is_voided",
            "order",
            "owner",
            "pending",
            "source_data_pan",
            "source_data_sub_type",
            "source_data_type",
            "success",
        ];

        $filteredQueries = [];
        foreach ($filteredQueriesParams as $param) {
            if (array_key_exists($param, $queries) and !is_array($queries[$param])) {
                $value = $queries[$param];
                if ($value === false) {
                    $value = "false";
                } elseif ($value === true) {
                    $value = "true";
                }
                $filteredQueries[] = (string)$value;
            } elseif (str_contains($param, "source_data")) {
                $arrayKey = str_replace("source_data_", "", $param);
                $filteredQueries[] = (string)$queries["source_data"][$arrayKey];
            } elseif (str_contains($param, "order") and is_array($queries[$param])) {
                $filteredQueries[] = (string)$queries["order"]["id"];
            }
        }
        $stringConcatenated = implode("", $filteredQueries);
        $isVerified = hash_hmac("SHA512", $stringConcatenated, $_ENV["PAYMOB_HMAC"]) == $hmac;

        $paymentGatewayVerifyResponse = new PaymentGatewayVerifyResponse();
        $paymentGatewayVerifyResponse->setProcessData($queries);
        if (is_array($queries["order"])) {
            $paymentGatewayVerifyResponse->setPaymentId($queries["order"]["id"]);
            $paymentGatewayVerifyResponse->setMerchantOrderId($queries["order"]["merchant_order_id"]);
        } else {
            $paymentGatewayVerifyResponse->setPaymentId($queries["order"]);
            $paymentGatewayVerifyResponse->setMerchantOrderId($queries["merchant_order_id"]);
        }

        $merchantOrderId = $paymentGatewayVerifyResponse->getMerchantOrderId();
        if (str_contains($merchantOrderId, "-")) {
            $merchantOrderId = explode("-", $merchantOrderId);
            $paymentGatewayVerifyResponse->setMerchantOrderId($merchantOrderId[1]);
        }

        if (array_key_exists("data_message", $queries)) {
            $paymentGatewayVerifyResponse->setMessage($queries["data_message"]);
        } elseif (array_key_exists("data", $queries)) {
            $paymentGatewayVerifyResponse->setMessage($queries["data"]["message"]);
        }

        if ($isVerified) {
            if ($queries["success"] == "true") {
                $paymentGatewayVerifyResponse->setSuccess(true);
            }
        }

        return $paymentGatewayVerifyResponse;
    }

    //Todo  :test this fun
    public function refund(Payment $payment, float $amountCents = null): PaymentGatewayRefundResponse
    {
        $paymentGatewayRefundResponse = new PaymentGatewayRefundResponse();

        if (!$payment->getTransactionNo()) {
            $paymentGatewayRefundResponse->setMessage("Can't refund this payment");

            return $paymentGatewayRefundResponse;
        }
        if ($payment->isRefunded()) {
            $paymentGatewayRefundResponse->setMessage("This payment is already refunded");

            return $paymentGatewayRefundResponse;
        }

        $amountCents = $amountCents ?? $payment->getAmountCents();

        $url = "https://accept.paymob.com/api/acceptance/void_refund/refund?token={$this->getToken()}";
        $data = [
            "transaction_id" => $payment->getTransactionNo(),
            "amount_cents" => $amountCents,
        ];
        $paymentGatewayRefundResponse->setSentData($data);

        $response = $this->makePOSTRequest($url, $data, ["content-type" => "application/json"]);
        $paymentGatewayRefundResponse->setProcessData($response);

        if (!array_key_exists("is_refund", $response)) {
            $paymentGatewayRefundResponse->setMessage($response["message"]);

            $this->logger->critical(
                "Invalid Payment Refund Response",
                ["response" => json_encode($response), "sentData" => json_encode($data)]
            );
        }

        if (array_key_exists("is_refund", $response) and $response["is_refund"] === true) {
            $paymentGatewayRefundResponse->setSuccess(true);
        }

        return $paymentGatewayRefundResponse;
    }



    /**
     * Step 1: API Authentication Request "server-side" to get the token
     * @return string
     */
    private function getToken(): string
    {
        if ($this->paymentToken == null) {
            $url = "https://accept.paymobsolutions.com/api/auth/tokens";

            $data = [
                "api_key" => $this->apiKey,
            ];

            $response = $this->makePOSTRequest($url, $data, ["content-type" => "application/json"]);
            if (!array_key_exists("token", $response)) {
                $this->logger->critical("Invalid Payment Response #1", ["response" => json_encode($response)]);
            }

            return $this->paymentToken = $response["token"];
        } else {
            return $this->paymentToken;
        }
    }


    private function makePOSTRequest(string $url, array $data, array $headers = []): array
    {
        $message = null;
        try {
            $response = $this->client->request("POST", $url, [
                "headers" => $headers,
                "json" => $data,
            ]);

            return $response->toArray();
            //            return (object)json_decode($response, true);
        } catch (ClientException $e) {
            //            $message = $e->getMessage();
            $response = $e->getResponse()->toArray(false);
            if (array_key_exists("message", $response)) {
                $message = $response["message"];
            }
        }

        return [
            "message" => $message,
        ];
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

    private function convertObjectToArray($data): array
    {
        if (is_array($data) || is_object($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = (is_array($value) || is_object($value)) ? $this->convertObjectToArray($value) : $value;
            }

            return $result;
        }
        return $data;
    }


}
