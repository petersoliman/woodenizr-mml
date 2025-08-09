<?php

namespace App\ECommerceBundle\Controller\FrontEnd;

use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Service\CartService;
use App\ECommerceBundle\Service\CouponService;
use App\ECommerceBundle\Service\FacebookConversionAPIService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart")
 */
class CartController extends AbstractCartController
{

    /**
     * @Route("", name="fe_cart_show", methods={"GET"})
     */
    public function index(FacebookConversionAPIService $facebookConversionAPIService): Response
    {
        $cart = $this->cartService->getCart();
        if (!$cart instanceof Cart) {
            throw $this->createNotFoundException();
        }
        $this->validateCouponAndRemoveIfNotValid($cart);

        $facebookConversionAPIService->sendEventByCart($cart, FacebookConversionAPIService::EVENT_INITIATE_CHECKOUT);
        return $this->render("eCommerce/frontEnd/cart/index.html.twig", [
            'cart' => $cart,
        ]);
    }


}
