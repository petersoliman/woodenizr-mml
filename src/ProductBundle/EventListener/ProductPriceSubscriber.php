<?php

namespace App\ProductBundle\EventListener;

use App\ECommerceBundle\Repository\CartHasProductPriceRepository;
use App\ProductBundle\Entity\ProductPrice;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PN\ServiceBundle\Service\UserService;

class ProductPriceSubscriber implements EventSubscriber
{

    private UserService $userService;
    private CartHasProductPriceRepository $cartHasProductPriceRepository;

    public function __construct(UserService $userService, CartHasProductPriceRepository $cartHasProductPriceRepository)
    {
        $this->userService = $userService;
        $this->cartHasProductPriceRepository = $cartHasProductPriceRepository;
    }

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];

    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof ProductPrice) {
            return;
        }
        $entity->setCurrency($entity->getProduct()->getCurrency());
    }
    // callback methods must be called exactly like the events they listen to;
    // they receive an argument of type LifecycleEventArgs, which gives you access
    // to both the entity object of the event and the entity manager itself
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof ProductPrice) {
            return;
        }
        if ($args->hasChangedField('deleted') and $args->getOldValue('deleted') == null and $args->getNewValue('deleted') instanceof \DateTime) {
            $userName = $this->userService->getUserName();
            $entity->setDeletedBy($userName);
            $this->cartHasProductPriceRepository->deleteByProductPrice($entity);
        }
    }

}
