<?php

namespace App\ProductBundle\EventListener;

use App\BaseBundle\SystemConfiguration;
use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\ProductBundle\DTO\ProductDTOService;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class ProductSubscriber implements EventSubscriber
{

    private CurrencyRepository $currencyRepository;

    public function __construct(CurrencyRepository $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
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
            if (is_array($changedData) and array_key_exists("currency", $changedData)) {
                $newCurrency = $changedData["currency"][1];
                foreach ($entity->getPrices() as $price) {
                    $price->setCurrency($newCurrency);
                    $em->persist($price);
                    $classMetadata = $em->getClassMetadata(ProductPrice::class);
                    $uow->computeChangeSet($classMetadata, $price);
                }
            }
        }
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof Product) {
            return;
        }
        if (!SystemConfiguration::ENABLE_MULTI_CURRENCIES and !$entity->getCurrency() instanceof Currency) {
            $defaultCurrency = $this->currencyRepository->getDefaultCurrency();
            $entity->setCurrency($defaultCurrency);
        }
    }

}
