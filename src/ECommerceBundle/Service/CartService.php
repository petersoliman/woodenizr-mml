<?php

namespace App\ECommerceBundle\Service;

use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartHasCookie;
use App\ECommerceBundle\Entity\CartHasProductPrice;
use App\ECommerceBundle\Entity\CartHasUser;
use App\ECommerceBundle\Repository\CartHasProductPriceRepository;
use App\ECommerceBundle\Repository\CartRepository;
use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Entity\Zone;
use App\NewShippingBundle\Service\AvailableShippingTimesService;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use App\ProductBundle\Entity\Product;
use App\ShippingBundle\Entity\ShippingAddress;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartService
{
    const CART_COOKIE_NAME = "cart-cookie";
    private ?Cart $cart = null;
    private ?Request $request;

    public function __construct(
        private readonly EntityManagerInterface        $em,
        RequestStack                                   $requestStack,
        private readonly TranslatorInterface           $translator,
        private readonly UserService                   $userService,
        private readonly CartTotalsService             $cartTotalsService,
        private readonly CartRepository                $cartRepository,
        private readonly CartHasProductPriceRepository $cartHasProductPriceRepository,
        private readonly AvailableShippingTimesService $availableShippingTimesService
    )
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public
    function getCart(
        User $user = null,
        bool $createCartIfNotExist = false,
        bool $recalculateTotals = false
    ): ?Cart
    {
        if ($this->cart instanceof Cart and $recalculateTotals === false) {
            return $this->cart;
        }
        $user = $user === null ? $this->userService->getUser() : $user;

        if ($user instanceof User) {
            $cart = $this->getCartByUser($user, $createCartIfNotExist);
            $this->mergeCookieCartWithPersonalCart($cart);
        } else {
            $cart = $this->getCartFromCookie($createCartIfNotExist);
        }

        if (!$cart instanceof Cart) {
            return null;
        }
        $this->initCart($cart);
        $this->addShippingTimeToCart($cart);

        return $this->cart;
    }

    public function initCart(Cart $cart): void
    {
        $allCartHasProductPrices = $cart->getCartHasProductPrices();
        $cartHasProductPrices = $this->cartHasProductPriceRepository->getCartValidProductPriceByCart($cart);
        foreach ($allCartHasProductPrices as $cartHasProductPrice) {
            $key = array_search($cartHasProductPrice, $cartHasProductPrices);
            if ($key === false) {
                $cart->removeCartHasProductPrice($cartHasProductPrice);
            }
        }

        foreach ($cartHasProductPrices as $cartHasProductPrice) {
            $cart->addCartHasProductPrice($cartHasProductPrice);
        }

        $cart->setSubTotal($this->cartTotalsService->getCartSubTotal($cart));
        $cart->setShippingFees($this->cartTotalsService->getCartShippingFees($cart));
        $cart->setExtraFees($this->cartTotalsService->getCartExtraFees($cart));
        $cart->setDiscount($this->cartTotalsService->getCartDiscount($cart));
        $cart->setGrandTotal($this->cartTotalsService->getCartGrandTotal($cart));

        $this->cart = $cart;
    }

    public function checkStock(Cart $cart): bool
    {
        $return = true;
        $session = $this->request->getSession();

        $cartHasProductPrices = $this->cartHasProductPriceRepository->getCartValidProductPriceByCart($cart);
        foreach ($cartHasProductPrices as $cartHasProductPrice) {
            if ($cartHasProductPrice->getQty() > $cartHasProductPrice->getProductPrice()->getStock()) {
                $message = $this->translator->trans("have_only_product_in_stock_msg", [
                    "%%stock" => $cartHasProductPrice->getProductPrice()->getStock(),
                    "%productName%" => $cartHasProductPrice->getProductPrice()->getProduct()->getTitle()
                ]);
                $session->getFlashBag()->add('error', $message);
                $return = false;
            }
        }

        return $return;
    }

    public function removeCart(?Cart $cart = null): bool
    {
        if ($cart == null) {
            return false;
        }

        $this->cartRepository->remove($cart);

        return true;
    }

    public function getPaymentMethods(Cart $cart): array
    {
        if ($cart->getGrandTotal() < 500) {
            return $this->em->getRepository(PaymentMethod::class)->findByActiveTypes([
                PaymentMethodEnum::CREDIT_CARD->value,
                PaymentMethodEnum::CASH_ON_DELIVERY->value,
            ]);
        }
        return $this->em->getRepository(PaymentMethod::class)->findByActive();

    }

    private function getCartByUser(User $user, bool $createCartIfNotExist = false): ?Cart
    {
        $cart = ($this->cart instanceof Cart and $this->cart->getUser() === $user) ? $this->cart : null;

        if ($cart instanceof Cart) {
            return $this->cart;
        }
        $cart = $this->cartRepository->getCartByUser($user);
        if ($cart instanceof Cart) {
            return $this->cart = $cart;
        } elseif ($createCartIfNotExist === false) {
            return null;
        }


        $cart = new Cart();
        $cartHasUser = new CartHasUser();
        $cartHasUser->setCart($cart);
        $cartHasUser->setUser($user);
        $cart->setCartHasUser($cartHasUser);
        $this->em->persist($cart);

        $this->em->flush();

        return $cart;
    }

    private function getCartFromCookie(bool $createCartIfNotExist = false): ?Cart
    {
        if ($this->request->cookies->has(self::CART_COOKIE_NAME)) {
            $cartCookieHash = $this->request->cookies->get(self::CART_COOKIE_NAME);
        } else {
            // create a new cart with cookie token
            $cartCookieHash = md5(time());
            //set Cookie
            setcookie(self::CART_COOKIE_NAME, $cartCookieHash, time() + 60 * 60 * 24 * 30, "/", null, false, true);
            $this->request->cookies->set(self::CART_COOKIE_NAME, $cartCookieHash);
        }

        $cart = ($this->cart instanceof Cart and $this->cart->getCookieHash() === $cartCookieHash) ? $this->cart : null;

        if (!$cart instanceof Cart) {
            $cart = $this->cartRepository->getCartByCookie($cartCookieHash);
            if ($cart instanceof Cart) {
                return $this->cart = $cart;
            }
        }

        if ($createCartIfNotExist === false) {
            return null;
        }


        $cart = new Cart();
        $cartHasCookie = new CartHasCookie();
        $cartHasCookie->setCart($cart);
        $cartHasCookie->setCookie($cartCookieHash);
        $cart->setCartHasCookie($cartHasCookie);
        $this->em->persist($cartHasCookie);
        $this->em->flush();

        return $cart;
    }

    private function mergeCookieCartWithPersonalCart(Cart $cart = null): void
    {
        if ($cart == null) {
            return;
        }
        if (!$this->request->cookies->has(self::CART_COOKIE_NAME)) {
            return;
        }

        $cookieCart = $this->getCartFromCookie();
        if ($cookieCart == null) {
            return;
        }

        //check if cart has products
        $cookieCartHasProductPrices = $this->cartHasProductPriceRepository->getCartValidProductPriceByCart($cookieCart);
        if (count($cookieCartHasProductPrices) == 0) {
            return;
        }

        $cartHasProductPrice = new CartHasProductPrice();

        foreach ($cookieCartHasProductPrices as $cartHasProductPrice) {
            $productPrice = $cartHasProductPrice->getProductPrice();
            $personalCartProductPrice = $this->em->getRepository(CartHasProductPrice::class)->findOneBy(['cart' => $cart, 'productPrice' => $productPrice]);

            if ($personalCartProductPrice) {
                $newQty = $personalCartProductPrice->getQty() + $cartHasProductPrice->getQty();

                if ($newQty <= $productPrice->getStock()) {
                    $personalCartProductPrice->setQty($newQty);
                    $this->em->persist($personalCartProductPrice);
                }
            } else {
                $cartHasProductPrice->setCart($cart);
                $cart->addCartHasProductPrice($cartHasProductPrice);
                $this->em->persist($cartHasProductPrice);
            }
        }
        $this->em->flush();

        // remove cookie cart
        $this->em->remove($cookieCart);
        $this->em->flush();

        // remove cookie
        setcookie(self::CART_COOKIE_NAME, NULL, 0, "/", NULL, FALSE, TRUE);
        $this->em->refresh($cart);
    }

    public function addShippingTimeToCart(Cart $cart, bool $forceUpdate = false): void
    {

        if (!$cart->getShippingAddress() instanceof ShippingAddress and !$cart->getCartGuestData()?->getZone()) {
            return;
        }
        $isShippingTimeUpdated = false;
        foreach ($cart->getCartHasProductPrices() as $cartHasProductPrice) {
            if ($cartHasProductPrice->getShippingTime() instanceof ShippingTime and !$forceUpdate) {
                continue;
            }
            $product = $cartHasProductPrice->getProductPrice()->getProduct();
            $targetZone = $cart->getShippingAddress()?->getZone() ?? $cart->getCartGuestData()->getZone();

            $shippingTimes = $this->getSuitableShippingTimeForProduct(product: $product, targetZone: $targetZone);
            if (count($shippingTimes) > 0) {
                $cartHasProductPrice->setShippingTime($shippingTimes[0]);
                $this->em->persist($cartHasProductPrice);
                $isShippingTimeUpdated = true;
            }
        }
        if (!$isShippingTimeUpdated) {
            return;
        }
        $this->em->flush();
    }

    public function getSuitableShippingTimeForProduct(Product $product, Zone $targetZone): array
    {
        return $this->availableShippingTimesService->getProductShippingTimes(product: $product, targetZone: $targetZone);
    }
}