<?php

namespace App\UserBundle\EventListener;

use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartHasProductPrice;
use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Repository\CartHasProductPriceRepository;
use App\ECommerceBundle\Repository\OrderRepository;
use App\UserBundle\Entity\User;
use App\UserBundle\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class UserCountTotals implements EventSubscriberInterface
{
    private OrderRepository $orderRepository;
    private UserRepository $userRepository;
    private CartHasProductPriceRepository $cartHasProductPriceRepository;

    public function __construct(
        OrderRepository               $orderRepository,
        UserRepository                $userRepository,
        CartHasProductPriceRepository $cartHasProductPriceRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
        $this->cartHasProductPriceRepository = $cartHasProductPriceRepository;

    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->updateCartItemInUser('persist', $args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->updateCartItemInUser('remove', $args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->updateCartItemInUser('update', $args);
        $this->updateSuccessOrderNoInUser('update', $args);
    }

    private function updateCartItemInUser($action, LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        // if this subscriber only applies to certain entity types,
        // add some code to check the entity type as early as possible
        if (!$entity instanceof CartHasProductPrice) {
            return;
        }
        $cart = $entity->getCart();
        if (!$cart instanceof Cart) {
            return;
        }

        $user = $cart->getUser();
        if (!$user instanceof User) {
            return;
        }
        $cartHasProductPrices = $this->cartHasProductPriceRepository->getCartValidProductPriceByCart($cart);


        $this->userRepository->updateCartItemsNoByUser($user, count($cartHasProductPrices));
    }

    private function updateSuccessOrderNoInUser(string $action, LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        // if this subscriber only applies to certain entity types,
        // add some code to check the entity type as early as possible
        if (!$entity instanceof Order) {
            return;
        }


        $user = $entity->getUser();
        if (!$user instanceof User) {
            return;
        }

        $successOrderNo = $this->orderRepository->getNoOfSuccessOrderByUser($user);
        $this->userRepository->updateNoOfSuccessOrderByUser($user, $successOrderNo);
    }
}
