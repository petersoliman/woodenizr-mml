<?php

namespace App\ProductBundle\EventListener;

use App\ProductBundle\Entity\ProductHasCollection;
use App\ProductBundle\Messenger\UpdateCollectionProductNumber;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

class CollectionSubscriber implements EventSubscriber
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
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

    public function postRemove(LifecycleEventArgs $args)
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

        if (!$entity instanceof ProductHasCollection) {
            return;
        }

        $collection = $entity->getCollection();

        $this->bus->dispatch(new UpdateCollectionProductNumber($collection));
    }
}
