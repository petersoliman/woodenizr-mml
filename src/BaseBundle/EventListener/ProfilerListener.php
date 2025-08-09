<?php

namespace App\BaseBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class ProfilerListener
{

    private string $environment;
    private LoggerInterface $logger;
    private ?SessionInterface $session = null;

    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        RequestStack    $requestStack
    )
    {
        $this->environment = $kernel->getEnvironment();
        $this->logger = $logger;
        $this->session = $requestStack->getSession();
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the master request
            return;
        }
        if ($this->environment == "dev") {
            // display k8s pod name
            $event->getRequest()->attributes->set("dev-hostname", gethostname());
        }
        $this->logger->info("OnControllerRequest loaded", [
            "controllerName" => $event->getController(),
            "fullUrl" => $event->getRequest()->getUri(),
            "sessionData" => $this->session?->all(),
            "cookiesData" => $event->getRequest()->cookies->all(),
        ]);
    }

}