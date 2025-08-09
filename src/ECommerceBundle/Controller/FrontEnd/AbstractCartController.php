<?php

namespace App\ECommerceBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartGuestData;
use App\ECommerceBundle\Entity\Coupon;
use App\ECommerceBundle\Service\CartService;
use App\ECommerceBundle\Service\CouponService;
use App\NewShippingBundle\Entity\ShippingTime;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\ShippingBundle\Entity\ShippingAddress;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;


abstract class AbstractCartController extends AbstractController
{
    protected CartService $cartService;
    protected TranslatorInterface $translator;
    private CouponService $couponService;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, CartService $cartService, CouponService $couponService)
    {
        parent::__construct($em);
        $this->translator = $translator;
        $this->cartService = $cartService;
        $this->couponService = $couponService;
    }

    protected function validateCouponAndRemoveIfNotValid(Cart $cart): void
    {
        if ($cart->getCoupon() instanceof Coupon) {
            $validateCoupon = $this->couponService->validateCoupon($cart, $cart->getCoupon());
            if (!$validateCoupon) {
                $cart->setCoupon(null);
                $this->em()->persist($cart);
                $this->em()->flush();
            }
        }
    }

    protected function validateCartStock(Cart $cart): ?RedirectResponse
    {
        $cartHasProductPrices = $cart->getCartHasProductPrices();

        foreach ($cartHasProductPrices as $cartHasProductPrice) {
            if ($cartHasProductPrice->getQty() > $cartHasProductPrice->getProductPrice()->getStock()) {
                $this->addFlash('error', $this->translator->trans("check_products_stock_msg"));

                return $this->redirectToRoute("fe_cart_show");
            }
        }
        return null;
    }

    protected function validateShippingAddress(Cart $cart): ?RedirectResponse
    {
        if ($cart->getUser() instanceof User) { // logged in user
            if ($cart->getShippingAddress() instanceof ShippingAddress) {
                return null;
            }
            $this->addFlash("error", $this->translator->trans("select_your_address_msg"));
            return $this->redirectToRoute("fe_cart_shipping_address");
        }

        // guest user
        if (!$cart->getCartGuestData() instanceof CartGuestData) {
            $this->addFlash("error", $this->translator->trans("select_your_address_msg"));
            return $this->redirectToRoute("fe_cart_shipping_address");
        }
        if ($cart->getCartGuestData()->getZone() == null) {
            $this->addFlash("error", $this->translator->trans("select_your_zone_msg"));
            return $this->redirectToRoute("fe_cart_shipping_address");
        }
        if (!Validate::not_null($cart->getCartGuestData()->getAddress())) {
            $this->addFlash("error", $this->translator->trans("select_your_zone_msg"));
            return $this->redirectToRoute("fe_cart_shipping_address");
        }
        if (!Validate::not_null($cart->getCartGuestData()->getName())) {
            $this->addFlash("error", $this->translator->trans("enter_your_name_msg"));
            return $this->redirectToRoute("fe_cart_shipping_address");
        }
        if (!Validate::not_null($cart->getCartGuestData()->getEmail())) {
            $this->addFlash("error", $this->translator->trans("enter_your_email_msg"));
            return $this->redirectToRoute("fe_cart_shipping_address");
        }
        if (!Validate::not_null($cart->getCartGuestData()->getMobileNumber())) {
            $this->addFlash("error", $this->translator->trans("enter_your_phone_msg"));
            return $this->redirectToRoute("fe_cart_shipping_address");
        }
        return null;
    }

    protected function validatePaymentMethod(Cart $cart): ?RedirectResponse
    {
        if ($cart->getPaymentMethod() instanceof PaymentMethod) {
            return null;
        }
        $this->addFlash("error", $this->translator->trans("select_the_payment_method_msg"));
        return $this->redirectToRoute("fe_cart_payment_method");
    }

    protected function validateShippingTime(Cart $cart): ?RedirectResponse
    {
        $errors = [];
        foreach ($cart->getCartHasProductPrices() as $cartHasProductPrice) {
            if (!$cartHasProductPrice->getShippingTime() instanceof ShippingTime) {
                $errors[] = $this->translator->trans("product_cannot_shipped_to_your_address", [
                    "%productName%" => $cartHasProductPrice->getProductPrice()->getProduct()->getTitle()
                ]);
            }
        }
        if (count($errors) > 0) {
            $this->addFlash("error", implode("<br />", $errors));
            return $this->redirectToRoute("fe_cart_show");
        }
        return null;
    }
}
