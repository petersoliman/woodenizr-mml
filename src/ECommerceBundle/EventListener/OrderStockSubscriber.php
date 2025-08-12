<?php

namespace App\ECommerceBundle\EventListener;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

class OrderStockSubscriber implements EventSubscriber
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Order) {
            return;
        }
        $this->handleStock($entity);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Order) {
            return;
        }
        $this->handleStock($entity);
    }


    private function handleStock(Order $order): void
    {
        switch ($order->getState()->decreaseOrIncreaseStockStock()) {
            case "decrease-stock":
                $this->decreaseStock($order);
                break;
            case "increase-stock":
                $this->increaseStock($order);
                break;
        }
    }

    private function decreaseStock(Order $order): void
    {
        foreach ($order->getOrderHasProductPrices() as $orderHasProductPrice) {
            if ($orderHasProductPrice->isStockWithdrawn()) {
                continue;
            }

            $productPrice = $orderHasProductPrice->getProductPrice();

            $newStock = $productPrice->getStock() - $orderHasProductPrice->getQty();
            if ($newStock < 0) {
                $newStock = 0;
            }
            $orderHasProductPrice->setStockWithdrawn(true);
            $orderHasProductPrice->getProductPrice()->setStock($newStock);
            $this->em->persist($orderHasProductPrice);
        }

        $this->em->flush();
    }

    private function increaseStock(Order $order): void
    {
        foreach ($order->getOrderHasProductPrices() as $orderHasProductPrice) {
            if (!$orderHasProductPrice->isStockWithdrawn()) {
                continue;
            }

            $productPrice = $orderHasProductPrice->getProductPrice();

            $newStock = $productPrice->getStock() + $orderHasProductPrice->getQty();
            if ($newStock < 0) {
                $newStock = 0;
            }

            $orderHasProductPrice->setStockWithdrawn(false);
            $orderHasProductPrice->getProductPrice()->setStock($newStock);
            $this->em->persist($orderHasProductPrice);
        }

        $this->em->flush();
    }
}
