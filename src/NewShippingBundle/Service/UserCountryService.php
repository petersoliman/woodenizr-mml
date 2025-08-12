<?php

namespace App\NewShippingBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\BaseBundle\Service\UserCountryAndCurrencyService;
use App\NewShippingBundle\Entity\Zone;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class UserCountryService
{

    private EntityManagerInterface $em;
    private ContainerInterface $container;
    private ?Request $request;
    private string $cookieAndSessionName = "user-country";
    private $userCountry;
    private int $cookieExpireInSec = 2592000; //month

    public function __construct(EntityManagerInterface $em, ContainerInterface $container, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->container = $container;
        $this->request = $requestStack->getCurrentRequest();
    }


    public function setCountryIfNotExist(Zone $country, Response $response = null)
    {
        if (!$this->hasCountry()) {
            $this->setCountry($country, $response);
        }

    }

    public function setCountry(Zone $country, ?Response $response = null): void
    {
        $this->userCountry = $country;
        if ($response) {
            $this->setCookie($response, $country);
        }
        $this->setSession($country);
    }

    public function getCountry(): Zone
    {
        if ($this->userCountry) {
            return $this->userCountry;
        }
        $countryId = $this->getSessionOrCookie();
        $country = null;
        if ($countryId) {
            $country = $this->em->getRepository(Zone::class)->findOneBy(["id" => $countryId, "deleted" => null]);
        }
        if ($country == null) {
            $siteCountry = $this->container->get(UserCountryAndCurrencyService::class)->getDefaultCountry();
            $country = $siteCountry->getZone();
        }

        return $country;
    }

    public function hasCountry(): bool
    {
        if (!$this->getRequest() instanceof Request) {
            return false;
        }
        $hasCookie = $this->getRequest()->cookies->has($this->cookieAndSessionName);
        if ($hasCookie) {
            return $hasCookie;
        }

        return $this->getRequest()->getSession()->has($this->cookieAndSessionName);
    }

    private function getSessionOrCookie(): ?string
    {
        if (!$this->getRequest() instanceof Request) {
            return null;
        }
        $countryId = $this->getRequest()->cookies->get($this->cookieAndSessionName);
        if (!$countryId) {
            $countryId = $this->getRequest()->getSession()->get($this->cookieAndSessionName);
        }

        return $countryId;
    }

    private function setSession(Zone $country):void
    {
        if ($this->getRequest() instanceof Request) {
            $this->getRequest()->getSession()->set($this->cookieAndSessionName, $country->getId());
        }
    }

    private function setCookie(Response $response, Zone $country)
    {
        $response->headers
            ->setCookie(
                new Cookie(
                    $this->cookieAndSessionName,
                    $country->getId(),
                    time() + $this->cookieExpireInSec,
                    '/',
                    null,
                    false,
                    true
                )
            );

    }

    private function getRequest(): ?Request
    {
        return $this->request;
    }

}
