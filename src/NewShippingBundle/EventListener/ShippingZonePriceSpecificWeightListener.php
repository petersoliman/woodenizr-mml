<?php

namespace App\NewShippingBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use App\NewShippingBundle\Entity\ShippingZonePriceSpecificWeight;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShippingZonePriceSpecificWeightListener implements EventSubscriber
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents()
    {
        return [
//            Events::postPersist,
//            Events::postRemove,
//            Events::postUpdate,
            Events::preUpdate,
        ];
    }

    // callback methods must be called exactly like the events they listen to;
    // they receive an argument of type LifecycleEventArgs, which gives you access
    // to both the entity object of the event and the entity manager itself
    public function postPersist(LifecycleEventArgs $args)
    {
    }

    public function postRemove(LifecycleEventArgs $args)
    {
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof ShippingZonePriceSpecificWeight) {
            return;
        }
        $entityManager = $args->getObjectManager();
        if ($args->hasChangedField('deleted')) {
            $oldValue = $args->getOldValue('deleted');
            $newValue = $args->getNewValue('deleted');
            if ($oldValue != $newValue and $newValue instanceof \DateTime) {
                $entity->setDeletedBy($this->container->get('user')->getUserName());
            }
        }


    }

}
