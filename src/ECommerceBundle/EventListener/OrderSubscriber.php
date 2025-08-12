<?php

namespace App\ECommerceBundle\EventListener;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class OrderSubscriber implements EventSubscriber
{
    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Order) {
            return;
        }
        if ($args->hasChangedField('state')) {
            if ($args->getNewValue('state') == OrderStatusEnum::SUCCESS->value) {
                $entity->setSuccessDate(new \DateTime());
            } else {
                $entity->setSuccessDate(null);
            }
        }
    }


}
