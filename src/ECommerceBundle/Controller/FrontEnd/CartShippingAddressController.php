<?php

namespace App\ECommerceBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartHasProductPrice;
use App\ECommerceBundle\Service\CartService;
use App\ECommerceBundle\Service\CouponService;
use App\ShippingBundle\Entity\ShippingAddress;
use App\ShippingBundle\Service\ShippingService;
use App\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/cart/shipping-address")
 */
class CartShippingAddressController extends AbstractCartController
{
    /**
     * @Route("", name="fe_cart_shipping_address", methods={"GET", "POST"})
     */
    public function index(TranslatorInterface $translator, ShippingService $shippingService): Response
    {
        $cart = $this->cartService->getCart();
        if (!$cart instanceof Cart) {
            throw $this->createNotFoundException();
        }
        $this->selectDefaultAddress($cart);


        $this->validateCouponAndRemoveIfNotValid($cart);

        $validateStock = $this->validateCartStock($cart);
        if ($validateStock instanceof RedirectResponse) {
            return $validateStock;
        }

        $zones = $shippingService->getZonesReadyToShipping();

        return $this->render("eCommerce/frontEnd/cart/shippingAddress.html.twig", [
            "cart" => $cart,
            "zones" => $zones
        ]);
    }

    private function selectDefaultAddress(Cart $cart): void
    {
        if ($cart->getShippingAddress() instanceof ShippingAddress) {
            return;
        }
        if (!$cart->getUser() instanceof User) {
            return;
        }

        $defaultAddress = $this->em()->getRepository(ShippingAddress::class)->getUserDefaultAddress($cart->getUser());
        if ($defaultAddress instanceof ShippingAddress) {
            $cart->setShippingAddress($defaultAddress);
            $this->em()->persist($cart);
            $this->em()->flush($cart);
        }
    }
}
