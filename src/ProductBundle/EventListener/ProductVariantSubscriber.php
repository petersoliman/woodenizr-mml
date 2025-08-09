<?php

namespace App\ProductBundle\EventListener;

use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Entity\ProductVariant;
use App\ProductBundle\Repository\ProductVariantRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use PN\ServiceBundle\Service\UserService;

class ProductVariantSubscriber implements EventSubscriber
{

    private ProductVariantRepository $productVariantRepository;
    private UserService $userService;

    public function __construct(ProductVariantRepository $productVariantRepository, UserService $userService)
    {
        $this->productVariantRepository = $productVariantRepository;
        $this->userService = $userService;
    }

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];

    }

    // Update all product price currency when change the currency in product entity
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Product) {
                continue;
            }
            $changedData = $uow->getEntityChangeSet($entity);
            if (is_array($changedData) and array_key_exists("enableVariants", $changedData)) {
                // $oldEnableVariants = $changedData["enableVariants"][0];
                $newEnableVariants = $changedData["enableVariants"][1];
//                foreach ($entity->getPrices() as $price) {
//                    $price->setDeleted(new \DateTime());
//                    $em->persist($price);
//                    $classMetadata = $em->getClassMetadata(ProductPrice::class);
//                    $uow->computeChangeSet($classMetadata, $price);
//                }

                if ($newEnableVariants === false) {
                    $variants = $this->productVariantRepository->findByProduct($entity);
                    foreach ($variants as $variant) {
                        $variant->setDeleted(new \DateTime());
                        $variant->setDeletedBy($this->userService->getUserName());
                        $em->persist($variant);

                        $classMetadata = $em->getClassMetadata(ProductVariant::class);
                        $uow->computeChangeSet($classMetadata, $variant);
                    }
                }

                /*foreach ($entity->getPrices() as $price) {
                    $price->setCurrency($newCurrency);
                    $em->persist($price);
                    $classMetadata = $em->getClassMetadata(ProductPrice::class);
                    $uow->computeChangeSet($classMetadata, $price);
                }*/
            }
        }
    }

}
