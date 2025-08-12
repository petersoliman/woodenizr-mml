<?php

namespace App\ProductBundle\EventListener;

use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Messenger\IndexProduct;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Repository\ProductSearchRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PN\MediaBundle\Entity\Image as ImageAlias;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductMainImageSubscriber implements EventSubscriber
{
    private MessageBusInterface $bus;
    private ProductRepository $productRepository;
    private ProductSearchRepository $productSearchRepository;

    public function __construct(
        MessageBusInterface $bus,
        ProductRepository $productRepository,
        ProductSearchRepository $productSearchRepository
    ) {
        $this->bus = $bus;
        $this->productRepository = $productRepository;
        $this->productSearchRepository = $productSearchRepository;
    }

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::preRemove,
            Events::onFlush,
        ];

    }

    // Update all product price currency when change the currency in product entity
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Image) {
                continue;
            }
            $changedData = $uow->getEntityChangeSet($entity);
            if (is_array($changedData) and array_key_exists("imageType", $changedData)) {
                $newImageType = $changedData["imageType"][1];
                if ($newImageType == ImageAlias::TYPE_MAIN) {
                    $product = $this->productRepository->getProductByImage($entity);
                    if ($product instanceof Product) {
                        $product->setMainImage($entity);
                        $em->persist($product);
                        $classMetadata = $em->getClassMetadata(Product::class);
                        $uow->computeChangeSet($classMetadata, $product);
                        $this->bus->dispatch(new IndexProduct($product));
                    }
                }
            }
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof Image) {
            return;
        }
        if ($entity->getImageType() == ImageAlias::TYPE_MAIN) {
            $this->productRepository->makeImageEmptyByImage($entity);
            $this->productSearchRepository->makeImageEmptyByImage($entity);
        }
    }
}
