<?php

namespace App\BaseBundle\Service;

use Psr\Log\LoggerInterface;

class CequensSMSService
{
    private $logger;
    private $baseUrl = "https://apis.cequens.com/";
    private $apiKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0b2tlbiI6ImUyYzIyYTdkZGRiOTg4N2Y1OTgwN2E1NWNiZTAwZDdjNDk1MGY1OTI2YTAxMmJkYzE4NzY0ODVjN2M5NWQ2NWNlZTMxYmRkYWJkZWZhOTFhYWM4MTIzZGFlYmNiMDY0ZTdmYjVhN2I5YmM5ZWZjMGY2NTE1YTc1NGQ0ZDI1ODVkNWNhM2JiYzVlZDhkNWU3NzQyM2VlYmE0YzQ3ZTZhZmQiLCJpYXQiOjE2MjY0Mzk1MDksImV4cCI6MzIwNDMxOTUwOX0.u6moBJ_PQ3suHSXjXjNYVEus-QaWTjn96f2jkbmktI4";
    private $username = "peter.nassef@perfectneeds.com";
    private $senderID = "Cequens";

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function send($mobileNumber, $message)
    {
        $url = $this->baseUrl."sms/v1/messages";
        $data = [
            "messageType" => "text",
            "acknowledgement" => 0,
            "flashing" => 0,
            "senderName" => $this->senderID,
            "messageText" => $message,
            "recipients" => $this->reformatMobileNumber($mobileNumber),
        ];
        $header = [
            "Authorization: Bearer ".$this->apiKey,
            "Accept: application/json",
            "Content-Type: application/json",
        ];

        $response = $this->post($url, $data, $header);
        if (isset($response->error)) {
            $this->logger->critical("Cequens send() ERORR: ".json_encode($response));
        };

        return $response;
    }


    private function post($url, $data = null, $header = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_POST, 1);

        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        if ($header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);

        curl_close($curl);

        return json_decode($result);
    }

    private function reformatMobileNumber($mobileNumber): string
    {
        return '2'.$mobileNumber;
    }
}