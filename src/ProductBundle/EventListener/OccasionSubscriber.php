<?php

namespace App\ProductBundle\EventListener;

use App\ProductBundle\Entity\ProductHasOccasion;
use App\ProductBundle\Messenger\UpdateOccasionProductNumber;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

class OccasionSubscriber implements EventSubscriber
{
    private MessageBusInterface $bus;

    public function __construct( MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }
    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preRemove,
            Events::postUpdate,
        ];
    }

    // callback methods must be called exactly like the events they listen to;
    // they receive an argument of type LifecycleEventArgs, which gives you access
    // to both the entity object of the event and the entity manager itself
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->updateNoOfProducts($args);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->updateNoOfProducts($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->updateNoOfProducts($args);
    }

    private function updateNoOfProducts(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof ProductHasOccasion) {
            return;
        }

        $occasion = $entity->getOccasion();

        $this->bus->dispatch(new UpdateOccasionProductNumber($occasion));
    }


}
