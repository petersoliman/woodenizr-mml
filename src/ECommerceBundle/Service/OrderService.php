<?php

namespace App\ECommerceBundle\Service;

use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartGuestData;
use App\ECommerceBundle\Entity\CartHasCookie;
use App\ECommerceBundle\Entity\CartHasProductPrice;
use App\ECommerceBundle\Entity\CartHasUser;
use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Entity\OrderGuestData;
use App\ECommerceBundle\Entity\OrderHasCoupon;
use App\ECommerceBundle\Entity\OrderHasProductPrice;
use App\ECommerceBundle\Entity\OrderShippingAddress;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Enum\ShippingStatusEnum;
use App\ECommerceBundle\Event\AfterCreateOrderEvent;
use App\ECommerceBundle\Event\ChangeOrderPaymentStatusEvent;
use App\ECommerceBundle\Repository\CouponRepository;
use App\ECommerceBundle\Repository\OrderRepository;
use App\NewShippingBundle\Entity\ShippingTime;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use App\OnlinePaymentBundle\Repository\PaymentRepository;
use App\UserBundle\Entity\User;
use App\VendorBundle\Entity\Vendor;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Date;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderService
{
    private int $orderDuplicatedTime = 5; // in minutes

    public function __construct(
        private readonly EntityManagerInterface   $em,
        private readonly RouterInterface          $router,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserService              $userService,
        private readonly CartService              $cartService,
        private readonly CartTotalsService        $cartTotalsService,
        private readonly OrderRepository          $orderRepository,
        private readonly CouponRepository         $couponRepository,
        private readonly PaymentRepository        $paymentRepository,
    )
    {
    }

    public function createOrder(Cart $cart): RedirectResponse|Order
    {
        $this->cartReadyToOrder($cart);

        $isReadyToShipping = $this->cartReadyToShipping($cart);
        if (!$isReadyToShipping) {
            $url = $this->router->generate("fe_cart_show");

            return new RedirectResponse($url);
        }


        $checkDuplicateOrder = $this->checkDuplicateOrder($cart);
        if ($checkDuplicateOrder !== null) {

            if ($checkDuplicateOrder['status'] === "continue-online-payment") {
                $payment = $this->paymentRepository->getOneByTypeAndObjectId(PaymentTypesEnum::ORDER,
                    $checkDuplicateOrder['order']->getId());

                return new RedirectResponse(
                    $this->router->generate("fe_payment_paymob_pay", ["uuid" => $payment->getUuid()])
                );
            } elseif ($checkDuplicateOrder['status'] === "show-success-failure-page") {
                return new RedirectResponse($this->router->generate("fe_order_success_failure"));
            }
        }
        $originalCartId = $cart->getId();
        //TODO: SPLIT ORDER HERE
        $splitCartItems = $this->splitCartByShippingTimeAndVendor($cart);
        $parentOrder = null;
        foreach ($splitCartItems as $splitCartItem) {

            $vendor = $splitCartItem["vendor"];
            $shippingTime = $splitCartItem["shippingTime"];
            $cartHasProductPrices = $splitCartItem["cartHasProductPrices"];

            $clonedCart = $this->cloneCartAndAddNewCartHasProductPrices($cart, $cartHasProductPrices);

            // insert in DB
            $order = $this->initOrder(
                cart: $clonedCart,
                shippingTime: $shippingTime,
                vendor: $vendor,
                originalCartId: $originalCartId,
                parentOrder: $parentOrder
            );

            $this->initOrderGuest($order, $clonedCart);
            $this->initOrderShippingAddress($order, $clonedCart);
            $this->initOrderCoupon($order, $clonedCart);
            $this->initOrderProducts($order, $clonedCart);
            $this->em->persist($order);
            $this->em->flush($order);

            if (!$order->getPaymentMethod()->getType()->isOnlinePaymentMethod()) {
                $event = new ChangeOrderPaymentStatusEvent($order, PaymentStatusEnum::NOT_PAID);
                $this->eventDispatcher->dispatch($event, ChangeOrderPaymentStatusEvent::NAME);

                $event = new AfterCreateOrderEvent($order);
                $this->eventDispatcher->dispatch($event, AfterCreateOrderEvent::NAME);
            }
            $parentOrder = $parentOrder ?? $order;
        }

        return $parentOrder;
    }

    private function cartReadyToOrder(Cart $cart): void
    {

        if ($cart->getUser() instanceof User) {


        }
    }

    private function cartReadyToShipping(Cart $cart): bool
    {

        $isStockValid = $this->cartService->checkStock($cart);
        if (!$isStockValid) {
            return false;
        }

        return true;
    }

    private function checkDuplicateOrder(Cart $cart): ?array
    {
        if (!$cart->getUser() instanceof User) {
            return null;
        }

        $cartSubTotal = $this->cartTotalsService->getCartSubTotal($cart);

        $shippingAddress = $cart->getShippingAddress();
        $checkOrderIfExist = $this->orderRepository->checkOrderExist(
            $cart->getUser()->getId(),
            OrderStatusEnum::NEW,
            $cartSubTotal,
            $shippingAddress->getId(),
            $cart->getProductPricesHash()
        );

        if ($checkOrderIfExist instanceof Order) {

            $diffTimeInMins = Date::timeDiffInMins(new \DateTime(), $checkOrderIfExist->getCreated());
            if ($diffTimeInMins > $this->orderDuplicatedTime) {
                return null;
            }

            $paymentMethod = $cart->getPaymentMethod();
            if ($paymentMethod->getType()->isOnlinePaymentMethod() and $checkOrderIfExist->getPaymentState() == PaymentStatusEnum::PENDING) {
                return ["status" => "continue-online-payment", "order" => $checkOrderIfExist];
            } elseif ($paymentMethod->getType()->isOnlinePaymentMethod() and $checkOrderIfExist->getPaymentState() == PaymentStatusEnum::NOT_PAID) {
                return ["status" => "show-success-failure-page", "order" => $checkOrderIfExist];
            }

            return ["status" => "show-success-failure-page", "order" => $checkOrderIfExist];
        }

        return null;
    }

    private function initOrder(
        Cart         $cart,
        ShippingTime $shippingTime,
        Vendor       $vendor,
        int          $originalCartId,
        ?Order       $parentOrder = null
    ): Order
    {
        $order = new Order();
        $order->setParent($parentOrder);
        $order->setShippingTime($shippingTime);
        $order->setVendor($vendor);
        $user = ($cart->getUser() instanceof User) ? $cart->getUser() : null;
        $order->setUser($user);
        $order->setPaymentState(PaymentStatusEnum::PENDING);
        $order->setState(OrderStatusEnum::NEW);
        $order->setShippingState(ShippingStatusEnum::AWAITING_PROCESSING);
        $order->setSubTotal($cart->getSubTotal());
        $order->setShippingCost($cart->getShippingFees());
        $order->setDiscount($cart->getDiscount());
        $order->setTotalPrice($cart->getGrandTotal());
        if ($parentOrder == null) {
            $order->setExtraFee($cart->getExtraFees());
            $order->setTotalPrice($cart->getGrandTotal() - $cart->getExtraFees());
        }
        $order->setPaymentMethod($cart->getPaymentMethod());
        $order->setCartObject($cart->getObj());
        $order->setCartId($originalCartId);
        $order->setCartProductPricesHash($cart->getProductPricesHash());

        $userName = "Guest";
        if ($cart->getUser() instanceof User) {
            $userName = $this->getUser()->getUserIdentifier();
        }

        $order->setCreator($userName);
        $order->setModifiedBy($userName);
        $this->em->persist($order);
        $this->em->flush($order);

        return $order;
    }

    private function initOrderGuest(Order $order, Cart $cart): void
    {
        $guestData = $cart->getCartGuestData();
        if ($guestData == null) {
            return;
        }
        $orderGuestData = new OrderGuestData();
        $orderGuestData->setEmail($guestData->getEmail());
        $orderGuestData->setName($guestData->getName());
        $order->setOrderGuestData($orderGuestData);
    }

    private function initOrderShippingAddress(Order $order, Cart $cart): void
    {
        $shippingAddress = $cart->getShippingAddress();

        $orderShippingAddress = new OrderShippingAddress();
        $orderShippingAddress->setOrder($order);

        if ($order->getUser() instanceof User) {
            $orderShippingAddress->setShippingAddress($shippingAddress);
            $orderShippingAddress->setZone($shippingAddress->getZone());
            $orderShippingAddress->setFullAddress($shippingAddress->getFullAddress());
            $orderShippingAddress->setTitle($shippingAddress->getTitle());
            $orderShippingAddress->setMobileNumber($shippingAddress->getMobileNumber());
            $orderShippingAddress->setNote($shippingAddress->getNote());
        } else {
            $guestData = $cart->getCartGuestData();
            $orderShippingAddress->setFullAddress($guestData->getAddress());
            $orderShippingAddress->setMobileNumber($guestData->getMobileNumber());
            $orderShippingAddress->setZone($guestData->getZone());
        }

        $order->setOrderShippingAddress($orderShippingAddress);
    }

    //Coupon

    private function initOrderCoupon(Order $order, Cart $cart): void
    {
        if ($cart->getCoupon() != null) {
            $coupon = $cart->getCoupon();
            if ($cart->getUser() instanceof User) {
                $couponUsedCount = $this->couponRepository->couponUsedCountByUser($coupon, $cart->getUser());
                if ($coupon->getLimitUsePerUser() > 0 and $couponUsedCount >= $coupon->getLimitUsePerUser()) {
                    return;
                }
            }

            $orderHasCoupon = new OrderHasCoupon();
            $orderHasCoupon->setOrder($order);
            $orderHasCoupon->setCoupon($coupon);
            $orderHasCoupon->setCode($coupon->getCode());
            $orderHasCoupon->setStartDate($coupon->getStartDate());
            $orderHasCoupon->setExpiryDate($coupon->getExpiryDate());
            $orderHasCoupon->setMinimumPurchaseAmount($coupon->getMinimumPurchaseAmount());
            $orderHasCoupon->setActive($coupon->isActive());
            $orderHasCoupon->setDiscountType($coupon->getDiscountType());
            $orderHasCoupon->setDiscountValue($coupon->getDiscountValue());
            $orderHasCoupon->setShipping($coupon->isShipping());
            $orderHasCoupon->setFirstOrderOnly($coupon->isFirstOrderOnly());
            $orderHasCoupon->setAddDiscountAfterProductDiscount($coupon->isAddDiscountAfterProductDiscount());
            $orderHasCoupon->setFreePaymentMethodFee($coupon->isFreePaymentMethodFee());
            $orderHasCoupon->setTotalNumberOfUse($coupon->getTotalNumberOfUse());
            $orderHasCoupon->setLimitUsePerUser($coupon->getLimitUsePerUser());
            $order->setOrderHasCoupon($orderHasCoupon);
        }

    }

    private function initOrderProducts(Order $order, Cart $cart): void
    {
        $totalQty = 0;
        foreach ($cart->getCartHasProductPrices() as $cartHasProductPrice) {
            $totalQty += $cartHasProductPrice->getQty();
            $orderHasProductPrice = new OrderHasProductPrice();
            $orderHasProductPrice->setOrder($order);
            $orderHasProductPrice->setProductPrice($cartHasProductPrice->getProductPrice());
            $orderHasProductPrice->setQty($cartHasProductPrice->getQty());
            $orderHasProductPrice->setUnitPriceBeforeCommission($cartHasProductPrice->getProductPrice()->getSellPriceBeforeCommission());
            $orderHasProductPrice->setUnitPrice($cartHasProductPrice->getProductPrice()->getSellPrice());
            $orderHasProductPrice->setCurrency($cartHasProductPrice->getProductPrice()->getCurrency());

            $totalItemPriceBeforeCommission = $cartHasProductPrice->getProductPrice()->getSellPriceBeforeCommission() * $cartHasProductPrice->getQty();
            $orderHasProductPrice->setTotalPriceBeforeCommission($totalItemPriceBeforeCommission);

            $totalItemPrice = $cartHasProductPrice->getProductPrice()->getSellPrice() * $cartHasProductPrice->getQty();
            $orderHasProductPrice->setTotalPrice($totalItemPrice);
            $order->addOrderHasProductPrice($orderHasProductPrice);
        }
        $order->setItemQty($totalQty);
    }

    private function getUser(): ?UserInterface
    {
        return $this->userService->getUser();
    }


    private function splitCartByShippingTimeAndVendor(Cart $cart): array
    {
        $splitCartItems = [];
        $generateKeyName = function ($shippingTime, $vendor) {
            return $shippingTime->getId() . "-" . $vendor->getId();
        };
        foreach ($cart->getCartHasProductPrices() as $cartHasProductPrice) {
            $productPrice = $cartHasProductPrice->getProductPrice();
            $shippingTime = $cartHasProductPrice->getShippingTime();
            $vendor = $productPrice->getProduct()->getVendor();

            if ($shippingTime == null or $vendor == null) {
                continue;
            }

            $keyName = $generateKeyName($shippingTime, $vendor);
            if (!array_key_exists($keyName, $splitCartItems)) {
                $splitCartItems[$keyName] = [
                    "shippingTime" => $shippingTime,
                    "vendor" => $vendor,
                    "cartHasProductPrices" => []
                ];
            }
            $splitCartItems[$keyName]["cartHasProductPrices"][] = $cartHasProductPrice;
        }
        return $splitCartItems;
    }

    /**
     * @param Cart $cart
     * @param array<CartHasProductPrice> $cartHasProductPrices
     * @return Cart
     */
    private function cloneCartAndAddNewCartHasProductPrices(Cart $cart, array $cartHasProductPrices): Cart
    {
        $newCart = new Cart();
        $newCart->setPaymentMethod($cart->getPaymentMethod());
        $newCart->setCoupon($cart->getCoupon());
        $newCart->setShippingAddress($cart->getShippingAddress());
        if ($cart->getCartHasUser() != null) {
            $newCartHasUser = new CartHasUser();
            $newCartHasUser->setCart($newCart);
            $newCartHasUser->setUser($cart->getCartHasUser()->getUser());
            $newCart->setCartHasUser($newCartHasUser);
        }

        if ($cart->getCartHasCookie() != null) {
            $newCartHasCookie = new CartHasCookie();
            $newCartHasCookie->setCart($newCart);
            $newCartHasCookie->setCookie($cart->getCartHasCookie()->getCookie());
            $newCart->setCartHasCookie($newCartHasCookie);
        }
        if ($cart->getCartGuestData() != null) {
            $newCartGuestData = new CartGuestData();
            $newCartGuestData->setCart($newCart);
            $newCartGuestData->setName($cart->getCartGuestData()->getName());
            $newCartGuestData->setZone($cart->getCartGuestData()->getZone());
            $newCartGuestData->setEmail($cart->getCartGuestData()->getEmail());
            $newCartGuestData->setMobileNumber($cart->getCartGuestData()->getMobileNumber());
            $newCartGuestData->setAddress($cart->getCartGuestData()->getAddress());

            $newCart->setCartGuestData($newCartGuestData);
        }
        foreach ($cartHasProductPrices as $cartHasProductPrice) {
            $newCartHasProductPrice = new CartHasProductPrice();
            $newCartHasProductPrice->setCart($newCart);
            $newCartHasProductPrice->setProductPrice($cartHasProductPrice->getProductPrice());
            $newCartHasProductPrice->setShippingTime($cartHasProductPrice->getShippingTime());
            $newCartHasProductPrice->setQty($cartHasProductPrice->getQty());
            $newCart->addCartHasProductPrice($newCartHasProductPrice);
        }

        $newCart->setSubTotal($this->cartTotalsService->getCartSubTotal($newCart));
        $newCart->setShippingFees($this->cartTotalsService->getCartShippingFees($newCart));
        $newCart->setExtraFees($this->cartTotalsService->getCartExtraFees($newCart));
        $newCart->setDiscount($this->cartTotalsService->getCartDiscount($newCart));
        $newCart->setGrandTotal($this->cartTotalsService->getCartGrandTotal($newCart));
//        dd($newCart);
        $this->em->detach($newCart);
//        $this->em->clear();
        return $newCart;
    }
}