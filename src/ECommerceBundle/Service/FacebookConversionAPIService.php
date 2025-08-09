<?php

namespace App\ECommerceBundle\Service;

use App\CMSBundle\Service\SiteSettingService;
use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\Order;
use App\UserBundle\Entity\User;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\DeliveryCategory;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use PN\ServiceBundle\Utils\General;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FacebookConversionAPIService
{

    const EVENT_ADD_TO_CART = "AddToCart";
    const EVENT_INITIATE_CHECKOUT = "InitiateCheckout";
    const EVENT_ADD_PAYMENT_INFO = "AddPaymentInfo";
    const EVENT_PURCHASE = "Purchase";
    private string $environment;

    private ?Request $request;
    private RouterInterface $router;
    private SiteSettingService $siteSettingService;


    public function __construct(
        RequestStack       $requestStack,
        RouterInterface    $router,
        KernelInterface    $kernel,
        SiteSettingService $siteSettingService
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->environment = $kernel->getEnvironment();

        $this->siteSettingService = $siteSettingService;
    }

    public function sendEventByCart(Cart $cart, string $eventName): void
    {
        $accessToken = $this->getAccessToken();
        $pixelID = $this->getPixelID();
        $testEventCode = $this->getTestEventCode();
        if ($accessToken == null or $pixelID == null) {
            return;
        }

        $api = Api::init(null, null, $accessToken);
        $api->setLogger(new CurlLogger());

        $userData = $this->getUserDataByCart($cart);
        $customData = $this->getCustomDataByCart($cart);

        $url = $this->router->generate('fe_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        if ($this->request instanceof Request) {
            $url = $this->request->getUri();
        }

        $event = (new Event())
            ->setEventName($eventName)
            ->setEventTime(time())
            ->setEventSourceUrl($url)
            ->setUserData($userData)
            ->setCustomData($customData)
            ->setActionSource(ActionSource::WEBSITE);

        $events = [];
        $events[] = $event;

        $request = (new EventRequest($pixelID))
            ->setEvents($events);
        if ($this->environment == "dev" and $testEventCode != null) {
            $request->setTestEventCode($testEventCode);
        }
        try {
            $response = $request->execute();
        } catch (\Exception $e) {
            // Handle exception if needed
            return;
        }

    }

    public function sendEventByOrder(Order $order, string $eventName): void
    {
        $accessToken = $this->getAccessToken();
        $pixelID = $this->getPixelID();
        $testEventCode = $this->getTestEventCode();
        if ($accessToken == null or $pixelID == null) {
            return;
        }

        $api = Api::init(null, null, $accessToken);
        $api->setLogger(new CurlLogger());

        $userData = $this->getUserDataByOrder($order);
        $customData = $this->getCustomDataByOrder($order);

        $url = $this->router->generate('fe_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        if ($this->request instanceof Request) {
            $url = $this->request->getUri();
        }

        $event = (new Event())
            ->setEventName($eventName)
            ->setEventTime(time())
            ->setEventSourceUrl($url)
            ->setUserData($userData)
            ->setCustomData($customData)
            ->setActionSource(ActionSource::WEBSITE);


        $events = [];
        $events[] = $event;

        $request = (new EventRequest($pixelID))
            ->setEvents($events);

        if ($this->environment == "dev" and $testEventCode != null) {
            $request->setTestEventCode($testEventCode);
        }
        $response = $request->execute();
//        dump($response);
    }


    private function getUserDataByCart(Cart $cart): UserData
    {
        $_fbp = 'fb.1.1558571054389.1098115397';
        $_fbc = 'fb.1.1554763741205.AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';
        if (array_key_exists("_fbp", $_COOKIE)) {
            $_fbp = $_COOKIE['_fbp'];
        }
        if (array_key_exists("_fbc", $_COOKIE)) {
            $_fbc = $_COOKIE['_fbc'];
        }

        $user = $cart->getUser();
        $userName = $cart->getBuyerName();
        $userFirstName = $userLastName = "N/A";
        if ($userName != null) {
            $userFirstName = General::splitFullName($userName)[0];
            $userLastName = General::splitFullName($userName)[1];
        }

        $userEmail = $cart->getBuyerEmail();
        $mobileNumber = $cart->getBuyerMobileNumber();
        $userData = (new UserData())
            // It is recommended to send Client IP and User Agent for Conversions API Events.
            ->setClientIpAddress($_SERVER['REMOTE_ADDR'])
            ->setClientUserAgent($_SERVER['HTTP_USER_AGENT'])
            ->setFbc($_fbc)
            ->setFbp($_fbp);

        if ($user instanceof User) {
            if ($user->getFacebookId() != null) {
                $userData->setFbLoginId($user->getFacebookId());
            }
            $userData->setExternalId($user->getId());
        }
        if ($userFirstName != null) {
            $userData->setFirstName($userFirstName);
        }
        if ($userLastName != null) {
            $userData->setLastName($userLastName);
        }
        if ($userEmail != null) {
            $userData->setEmails([$userEmail]);
        }
        if ($mobileNumber != null) {
            $userData->setPhones([$mobileNumber]);
        }

        return $userData;
    }

    private function getCustomDataByCart(Cart $cart): CustomData
    {
        $contents = [];

        foreach ($cart->getCartHasProductPrices() as $cartHasProductPrice) {
            $title = $cartHasProductPrice->getProductPrice()->getProduct()->getTitle();
            if ($cartHasProductPrice->getProductPrice()->getTitle() != null) {
                $title .= " - " . $cartHasProductPrice->getProductPrice()->getTitle();
            }

            $content = (new Content())
                ->setProductId($cartHasProductPrice->getProductPrice()->getId())
                ->setQuantity($cartHasProductPrice->getQty())
                ->setTitle($title)
                ->setDeliveryCategory(DeliveryCategory::HOME_DELIVERY);

            $contents[] = $content;
        }

        return (new CustomData())
            ->setContents($contents)
            ->setCurrency('egp')
            ->setValue($cart->getSubTotal());
    }

    private function getUserDataByOrder(Order $order): UserData
    {

        $_fbp = 'fb.1.1558571054389.1098115397';
        $_fbc = 'fb.1.1554763741205.AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';
        if (array_key_exists("_fbp", $_COOKIE)) {
            $_fbp = $_COOKIE['_fbp'];
        }
        if (array_key_exists("_fbc", $_COOKIE)) {
            $_fbc = $_COOKIE['_fbc'];
        }

        $user = $order->getUser();
        $userName = $order->getBuyerName();
        $userFirstName = $userLastName = "N/A";
        if ($userName != null) {
            $userFirstName = General::splitFullName($userName)[0];
            $userLastName = General::splitFullName($userName)[1];
        }

        $userEmail = $order->getBuyerEmail();
        $mobileNumber = $order->getBuyerMobileNumber();
        $userData = (new UserData())
            // It is recommended to send Client IP and User Agent for Conversions API Events.
            ->setClientIpAddress($_SERVER['REMOTE_ADDR'])
            ->setClientUserAgent($_SERVER['HTTP_USER_AGENT'])
            ->setFbc($_fbc)
            ->setFbp($_fbp);

        if ($user instanceof User) {
            if ($user->getFacebookId() != null) {
                $userData->setFbLoginId($user->getFacebookId());
            }
            $userData->setExternalId($user->getId());
        }
        if ($userFirstName != null) {
            $userData->setFirstName($userFirstName);
        }
        if ($userLastName != null) {
            $userData->setLastName($userLastName);
        }
        if ($userEmail != null) {
            $userData->setEmails([$userEmail]);
        }
        if ($mobileNumber != null) {
            $userData->setPhones([$mobileNumber]);
        }

        return $userData;
    }

    private function getCustomDataByOrder(Order $order): CustomData
    {
        $contents = [];
        foreach ($order->getOrderHasProductPrices() as $orderHasProductPrice) {
            $title = $orderHasProductPrice->getProductPrice()->getProduct()->getTitle();
            if ($orderHasProductPrice->getProductPrice()->getTitle() != null) {
                $title .= " - " . $orderHasProductPrice->getProductPrice()->getTitle();
            }
            $content = (new Content())
                ->setProductId($orderHasProductPrice->getProductPrice()->getId())
                ->setQuantity($orderHasProductPrice->getQty())
                ->setTitle($title)
                ->setDeliveryCategory(DeliveryCategory::HOME_DELIVERY);


            $contents[] = $content;
        }

        return (new CustomData())
            ->setContents($contents)
            ->setCurrency('egp')
            ->setValue($order->getTotalPrice());
    }

    private function getAccessToken(): ?string
    {
        return $this->siteSettingService->getByConstantName("facebook-conversion-api-access-token");
    }

    private function getPixelID(): ?int
    {
        return $this->siteSettingService->getByConstantName("facebook-conversion-api-pixel-id");
    }

    private function getTestEventCode(): ?string
    {
        return $this->siteSettingService->getByConstantName("facebook-conversion-api-test-event-code");
    }
}
