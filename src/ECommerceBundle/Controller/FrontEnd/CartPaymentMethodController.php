<?php

namespace App\ECommerceBundle\Controller\FrontEnd;

use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartGuestData;
use App\NewShippingBundle\Entity\Zone;
use App\ShippingBundle\Entity\ShippingAddress;
use App\ShippingBundle\Service\ShippingService;
use App\UserBundle\Entity\User;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart/payment-method")
 */
class CartPaymentMethodController extends AbstractCartController
{
    /**
     * @Route("", name="fe_cart_payment_method", methods={"GET", "POST"})
     */
    public function index(Request $request, ShippingService $shippingService): Response
    {
        $cart = $this->cartService->getCart();
        if (!$cart instanceof Cart) {
            throw $this->createNotFoundException();
        }
        $this->validateCouponAndRemoveIfNotValid($cart);

        $validateStock = $this->validateCartStock($cart);
        if ($validateStock instanceof RedirectResponse) {
            return $validateStock;
        }
        if (!$this->saveShippingAddress($request, $cart)) {
            return $this->redirectToRoute("fe_cart_shipping_address");
        }

        $validatePaymentMethod = $this->validateShippingTime($cart);
        if ($validatePaymentMethod instanceof RedirectResponse) {
            return $validatePaymentMethod;
        }

        $zones = $shippingService->getZonesReadyToShipping();

        $paymentMethods = $this->cartService->getPaymentMethods($cart);
        return $this->render("eCommerce/frontEnd/cart/paymentMethod.html.twig", [
            "cart" => $cart,
            "zones" => $zones,
            "paymentMethods" => $paymentMethods
        ]);
    }

    private function saveShippingAddress(Request $request, Cart $cart): bool
    {
        if (!$request->isMethod("POST")) {
            return true;
        }
        if (!$cart->getUser() instanceof User) { // Guest

            $guest = $request->request->get('guest');
            $guestName = $guest['name'];
            $guestEmail = $guest['email'];
            $mobile = $guest['mobile'];
            $address = $guest['address'];
            $zoneId = $guest['zone'];
            if (!Validate::not_null($guestName)) {
                $this->addFlash('error', $this->translator->trans("enter_your_name_msg"));

                return false;
            }
            if (!Validate::not_null($guestEmail)) {
                $this->addFlash('error', $this->translator->trans("enter_your_email_msg"));

                return false;
            }
            if (!Validate::email($guestEmail)) {
                $this->addFlash('error', $this->translator->trans("enter_valid_email_msg"));

                return false;
            }
            if (!Validate::not_null($mobile)) {
                $this->addFlash('error', $this->translator->trans("enter_your_phone_msg"));

                return false;
            }
            if (!Validate::not_null($address)) {
                $this->addFlash('error', $this->translator->trans("enter_your_address_msg"));

                return false;
            }

            if (!Validate::not_null($zoneId)) {
                $this->addFlash('error', $this->translator->trans("select_your_zone_msg") . "1");

                return false;
            }
            $zone = $this->em()->getRepository(Zone::class)->find($zoneId);
            $zone = $this->em()->getRepository(Zone::class)->getZonesReadyToShipping($zone);
            if (!$zone instanceof Zone) {
                $this->addFlash('error', $this->translator->trans("select_your_zone_msg") . "2");

                return false;
            }
            $cartGuestData = $cart->getCartGuestData();
            if (!$cartGuestData instanceof CartGuestData) {
                $cartGuestData = new CartGuestData();
                $cartGuestData->setCart($cart);
                $cart->setCartGuestData($cartGuestData);
            }
            $cartGuestData->setName($guestName);
            $cartGuestData->setEmail($guestEmail);
            $cartGuestData->setAddress($address);
            $cartGuestData->setMobileNumber($mobile);
            $cartGuestData->setZone($zone);

            $this->em()->persist($cart);
            $this->em()->flush();

            return true;
        }

        // Logged in User
        $shippingAddressId = $request->request->get('address');
        $shippingAddress = $this->em()->getRepository(ShippingAddress::class)->findOneBy(["id" => $shippingAddressId, "user" => $cart->getUser()]);
        if (!$shippingAddress instanceof ShippingAddress) {
            $this->addFlash('error', $this->translator->trans("select_your_address_msg"));

            return false;
        }

        $cart->setShippingAddress($shippingAddress);
        $this->em()->persist($cart);
        $this->em()->flush();


        $this->cartService->addShippingTimeToCart($cart, true);

        return true;
    }

}
