<?php

namespace App\BaseBundle\Listener;

use App\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class KernelRequestListener
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage )
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(RequestEvent $event)
    {

        if ($event->isMainRequest()) {
            $session = $event->getRequest()->getSession();
            if (!$session->isStarted()) {
                $session->start();
            }

            if (!$this->getUser() instanceof User and $this->addToSession($event->getRequest())) {
                $session->set('_security.main.target_path', $event->getRequest()->getUri());
            }
        }
    }

    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        return $token->getUser();
    }

    private function addToSession(Request $request): bool
    {
        $routeName = $request->get("_route");
        $exceptRoutes = [
            "app_user_",
            "_profiler",
            "_wdt",
            "_ajax",
            "hwi_",
        ];

        foreach ($exceptRoutes as $route) {
            if (strpos($routeName, $route) !== false) {
                return false;
            }
        }

        return true;
    }

}
