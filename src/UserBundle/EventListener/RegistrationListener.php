<?php

namespace App\UserBundle\EventListener;

use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\UserEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegistrationListener implements EventSubscriberInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::REGISTRATION_COMPLETED => 'onRegistrationComplete',
        ];
    }

    public function onRegistrationComplete(RegistrationEvent $event)
    {
        $user = $event->getUser();

    }

}