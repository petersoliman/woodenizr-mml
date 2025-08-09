<?php

namespace App\ProductBundle\EventListener;

use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductDetails;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Messenger\IndexCategory;
use App\ProductBundle\Messenger\IndexProduct;
use App\ProductBundle\Messenger\IndexVendor;
use App\VendorBundle\Entity\Vendor;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductSearchIndexing implements EventSubscriber
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
            Events::postUpdate,
            Events::preUpdate,
        ];

    }

    // callback methods must be called exactly like the events they listen to;
    // they receive an argument of type LifecycleEventArgs, which gives you access
    // to both the entity object of the event and the entity manager itself
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->indexingProduct($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Product and !$entity instanceof ProductDetails and !$entity instanceof ProductPrice) {
            return;
        }
        $this->indexingProduct($args);
    }

    private function indexingProduct(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        // if this subscriber only applies to certain entity types,
        // add some code to check the entity type as early as possible
        if ($entity instanceof ProductPrice or $entity instanceof ProductDetails) {
            $product = $entity->getProduct();
            $this->bus->dispatch(new IndexProduct($product));
        } elseif ($entity instanceof Product) {
            $product = $entity;
            $this->bus->dispatch(new IndexProduct($product));
        }
    }

    // callback methods must be called exactly like the events they listen to;
    // they receive an argument of type LifecycleEventArgs, which gives you access
    // to both the entity object of the event and the entity manager itself
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Vendor) {
            $this->indexingVendor($args);
        }

        if (!$entity instanceof Category) {
            return;
        }
        if ($args->hasChangedField('publish')) {
            $this->bus->dispatch(new IndexCategory($entity));
        }
    }

    private function indexingVendor(PreUpdateEventArgs $args): void
    {
        $vendor = $args->getObject();

        if (
            $args->hasChangedField('commissionPercentage')) {
            $this->bus->dispatch(new IndexVendor($vendor));
        }

    }

}
