<?php


namespace App\BaseBundle\Listener;

use App\UserBundle\Model\UserInterface;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DoctrineExtensionListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenStorageInterface $tokenStorage;
    private LoggableListener $loggableListener;
    private TranslatableListener $translatableListener;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        TranslatableListener $translatableListener,
        LoggableListener $loggableListener

    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->translatableListener = $translatableListener;
        $this->loggableListener = $loggableListener;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::FINISH_REQUEST => 'onLateKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($this->tokenStorage !== null &&
            $this->tokenStorage->getToken() !== null &&
            $this->tokenStorage->getToken()->getUser() instanceof UserInterface
        ) {
            # for loggable behavior
            $this->loggableListener->setUsername($this->tokenStorage->getToken()->getUserIdentifier());
        }
    }

    public function onLateKernelRequest(FinishRequestEvent $event): void
    {
        $this->translatableListener->setTranslatableLocale($event->getRequest()->getLocale());
    }
}
