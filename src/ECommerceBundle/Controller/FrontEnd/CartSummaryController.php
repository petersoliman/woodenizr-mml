<?php

namespace App\ECommerceBundle\Controller\FrontEnd;

use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartGuestData;
use App\ECommerceBundle\Service\FacebookConversionAPIService;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\ShippingBundle\Entity\ShippingAddress;
use App\UserBundle\Entity\User;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart/summery")
 */
class CartSummaryController extends AbstractCartController
{
    /**
     * @Route("", name="fe_cart_order_summery", methods={"GET", "POST"})
     */
    public function index(Request $request, FacebookConversionAPIService $facebookConversionAPIService): Response
    {
        $cart = $this->cartService->getCart();
        if (!$cart instanceof Cart) {
            throw $this->createNotFoundException();
        }
        $this->validateCouponAndRemoveIfNotValid($cart);

        if (!$this->selectPaymentMethod($request, $facebookConversionAPIService, $cart)) {
            return $this->redirectToRoute("fe_cart_payment_method");
        }

        $validateStock = $this->validateCartStock($cart);
        if ($validateStock instanceof RedirectResponse) {
            return $validateStock;
        }

        $validateShippingAddress = $this->validateShippingAddress($cart);
        if ($validateShippingAddress instanceof RedirectResponse) {
            return $validateShippingAddress;
        }

        $validatePaymentMethod = $this->validatePaymentMethod($cart);
        if ($validatePaymentMethod instanceof RedirectResponse) {
            return $validatePaymentMethod;
        }


        return $this->render("eCommerce/frontEnd/cart/cartSummery.html.twig", [
            "cart" => $cart
        ]);
    }

    private function selectPaymentMethod(Request $request, FacebookConversionAPIService $facebookConversionAPIService, Cart $cart): bool
    {
        if (!$request->request->has("payment_method")) {

            if (!$cart->getPaymentMethod() instanceof PaymentMethod) {
                $this->addFlash("error", $this->translator->trans("select_the_payment_method_msg"));
                return false;
            }

            return true;
        }
        $selectedPaymentMethod = null;
        $paymentMethodId = $request->request->get('payment_method');
        $paymentMethods = $this->cartService->getPaymentMethods($cart);
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->getId() != $paymentMethodId) {
                continue;
            }
            $selectedPaymentMethod = $paymentMethod;
        }
        if (!$selectedPaymentMethod instanceof PaymentMethod) {
            $this->addFlash("error", $this->translator->trans("select_the_payment_method_msg"));
            return false;
        }
        $cart->setPaymentMethod($selectedPaymentMethod);
        $this->em()->persist($cart);
        $this->em()->flush();

        $facebookConversionAPIService->sendEventByCart($cart, FacebookConversionAPIService::EVENT_ADD_PAYMENT_INFO);
        return true;
    }


}
