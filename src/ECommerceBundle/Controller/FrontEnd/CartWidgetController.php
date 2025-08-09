<?php

namespace App\ECommerceBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CurrencyBundle\Service\GoogleAnalyticService;
use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartHasProductPrice;
use App\ECommerceBundle\Repository\CartHasProductPriceRepository;
use App\ECommerceBundle\Repository\CartRepository;
use App\ECommerceBundle\Repository\CouponRepository;
use App\ECommerceBundle\Service\CartService;
use App\ECommerceBundle\Service\CouponService;
use App\ECommerceBundle\Service\FacebookConversionAPIService;
use App\NewShippingBundle\Entity\ShippingTime;
use App\ProductBundle\DTO\CartProductDTO;
use App\ProductBundle\Entity\ProductFavorite;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Repository\ProductFavoriteRepository;
use App\ProductBundle\Repository\ProductPriceRepository;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Utils\Date;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/cart-widget")
 */
class CartWidgetController extends AbstractController
{
    private array $excludeAgents = [
        "google",
        "facebook",
        "yandex",
        "bing",
        "freshpingbot",
        "bot",
    ];
    private TranslatorInterface $translator;
    private CartRepository $cartRepository;
    private CartService $cartService;
    private CartProductDTO $cartProductDTO;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        CartRepository         $cartRepository,
        CartService            $cartService,
        CartProductDTO         $cartProductDTO
    )
    {
        parent::__construct($em);
        $this->translator = $translator;
        $this->cartRepository = $cartRepository;
        $this->cartService = $cartService;
        $this->cartProductDTO = $cartProductDTO;
    }

    /**
     * @Route("/list-ajax", name="fe_cart_widget_list_ajax", methods={"GET"})
     */
    public function cart(Request $request): Response
    {
        $userAgent = $request->headers->get("User-Agent");
        foreach ($this->excludeAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return $this->json(new \stdClass());
            }
        }

        $cart = $this->cartService->getCart(createCartIfNotExist: true);
        if (!$cart) {
            throw $this->createNotFoundException('Unable to find Cart entity.');
        }

        return $this->json($this->getCartObject($cart));
    }

    /**
     * @Route("/add-item-ajax", name="fe_cart_widget_add_item_ajax", methods={"POST"})
     */
    public function addItemAjax(
        Request                      $request,
        GoogleAnalyticService        $googleAnalyticService,
        FacebookConversionAPIService $facebookConversionAPIService,
        ProductPriceRepository       $productPriceRepository
    ): Response
    {
        $productPriceId = $request->request->get('productPriceId');
        $qty = $request->request->has('qty') ? $request->request->get('qty') : 1;

        if (!Validate::not_null($productPriceId)) {
            return $this->json(["error" => true, 'message' => "Please enter product price ID"]);
        }

        $cart = $this->cartService->getCart();
        if (!$cart instanceof Cart) {
            return $this->json(["error" => true, 'message' => "don't play with us"]);
        }

        $productPrice = $productPriceRepository->find($productPriceId);
        if (!$productPrice instanceof ProductPrice) {
            return $this->json(["error" => true, 'message' => "Invalid Product"]);
        }
        if ($productPrice->getUnitPrice() < 1) {
            return $this->json(["error" => true, 'message' => $this->translator->trans("no_stock_msg")]);
        }
//        if ($qty < $productPrice->getMinimumPurchaseQty()) {
//            $qty = $productPrice->getMinimumPurchaseQty();
//        }

        $cartHasProductPrice = $this->em()->getRepository(CartHasProductPrice::class)->findOneBy([
            'cart' => $cart,
            'productPrice' => $productPrice,
        ]);
        $newQty = (($cartHasProductPrice) ? $cartHasProductPrice->getQty() : 0) + $qty;
        if ($newQty > $productPrice->getStock()) {
            return $this->json(["error" => true, 'message' => $this->translator->trans("no_stock_msg")]);
        }

        if (!$cartHasProductPrice instanceof CartHasProductPrice) {
            $cartHasProductPrice = new CartHasProductPrice();
            $cartHasProductPrice->setCart($cart);
            $cartHasProductPrice->setProductPrice($productPrice);
            $cart->addCartHasProductPrice($cartHasProductPrice);
        }
        $cartHasProductPrice->setQty($newQty);
        $cart->setCreated(new \DateTime());
        $this->em()->persist($cartHasProductPrice);
        $this->em()->flush();

        $this->cartService->initCart($cart);

        $facebookConversionAPIService->sendEventByCart($cart, FacebookConversionAPIService::EVENT_ADD_TO_CART);

        $gtmProductsObject = $googleAnalyticService->getProductObject($productPrice,
            $cartHasProductPrice->getQty());

        return $this->json([
            "error" => false,
            'cart' => $this->getCartObject($cart),
            'gtmProductsObjects' => $gtmProductsObject,
        ]);
    }

    /**
     * @Route("/remove-item-ajax", name="fe_cart_widget_remove_item_ajax", methods={"GET", "POST"})
     */
    public function removeItemAjax(
        Request                $request,
        GoogleAnalyticService  $googleAnalyticService,
        ProductPriceRepository $productPriceRepository,
    ): Response
    {
        $productPriceId = $request->get('productPriceId');
        if (!Validate::not_null($productPriceId)) {
            return $this->json(["error" => false, 'message' => "Please enter product price ID"]);
        }

        $cart = $this->cartService->getCart();
        if (!$cart instanceof Cart) {
            return $this->json(["error" => false, 'message' => "don't play with us"]);
        }

        $productPrice = $productPriceRepository->find($productPriceId);
        if (!$productPrice instanceof ProductPrice) {
            return $this->json(["error" => true, 'message' => "Invalid Product"]);
        }

        $cartHasProductPrice = $this->em()->getRepository(CartHasProductPrice::class)->findOneBy([
            'cart' => $cart,
            'productPrice' => $productPrice,
        ]);
        if ($cartHasProductPrice instanceof CartHasProductPrice) {
            $this->em()->remove($cartHasProductPrice);
            $this->em()->flush();
        }

        $this->cartService->initCart($cart);

        $gtmProductsObject = $googleAnalyticService->getProductObject(
            $productPrice,
            $cartHasProductPrice != null ? $cartHasProductPrice->getQty() : 0
        );


        return $this->json([
            "error" => false,
            'cart' => $this->getCartObject($cart),
            'gtmProductsObjects' => $gtmProductsObject,
        ]);
    }

    /**
     * @Route("/update-qty-item-ajax", name="fe_cart_widget_update_qty_item_ajax", methods={"POST"})
     */
    public function updateQtyItemAjax(
        Request                $request,
        ProductPriceRepository $productPriceRepository,
    ): Response
    {
        $productPriceId = $request->get('productPriceId');
        $qty = $request->request->has('qty') ? $request->request->get('qty') : 1;
        if (!isset($qty)) {
            $qty = 1;
        }
        //Validation
        if (!is_numeric($qty)) {
            return $this->json(["error" => true, 'message' => 'Please enter a valid qty']);
        }
        if (!Validate::not_null($productPriceId)) {
            return $this->json(["error" => false, 'message' => "Please enter product price ID"]);
        }


        $cart = $this->cartService->getCart();
        $productPrice = $productPriceRepository->find($productPriceId);
        if (!$productPrice instanceof ProductPrice) {
            return $this->json(["error" => true, 'message' => "Invalid Product"]);
        }

        if ($productPrice->getStock() < $qty) {
            return $this->json(["error" => false, 'message' => $this->translator->trans("dont_have_require_qty_in_stock_msg")]);
        }

        $cartHasProductPrice = $this->em()->getRepository(CartHasProductPrice::class)->findOneBy([
            'cart' => $cart,
            'productPrice' => $productPrice,
        ]);

        if ($cartHasProductPrice instanceof CartHasProductPrice and $qty > 0) {
            $cartHasProductPrice->setQty($qty);
            $cart->setCreated(new \DateTime());
            $this->em()->persist($cartHasProductPrice);
            $this->em()->flush();
            $this->cartService->initCart($cart);
        }

        return $this->json([
            "error" => false,
            'cart' => $this->getCartObject($cart),
        ]);
    }

    /**
     * @Route("/remove-from-cart-and-add-to-wishlist-ajax", name="fe_cart_widget_remove_from_cart_and_add_to_wishlist_ajax", methods={"POST"})
     */
    public function removeFromCartAndAddToWishlist(
        Request                       $request,
        GoogleAnalyticService         $googleAnalyticService,
        CartHasProductPriceRepository $cartHasProductPriceRepository,
        ProductFavoriteRepository     $productFavoriteRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(["error" => false, "message" => $this->translator->trans("please_login_first_msg")]);
        }
        $cart = $this->cartService->getCart();
        $productPriceId = $request->request->get('productPriceId');

        $cartHasProductPrice = $cartHasProductPriceRepository->findOneBy([
            'cart' => $cart,
            'productPrice' => $productPriceId,
        ]);

        $gtmProductsObject = null;
        if ($cartHasProductPrice instanceof CartHasProductPrice) {
            $gtmProductsObject = $googleAnalyticService->getProductObject($cartHasProductPrice->getProductPrice(),
                $cartHasProductPrice->getQty());
        }

        $this->doRemoveFromCartAndAddToWishlist($productFavoriteRepository, $cartHasProductPrice);
        $this->cartService->initCart($cart);

        return $this->json([
            "error" => false,
            'cart' => $this->getCartObject($cart),
            'gtmProductsObjects' => $gtmProductsObject,
        ]);
    }

    /**
     * @Route("/add-coupon-ajax", name="fe_cart_widget_add_coupon_ajax", methods={"POST"})
     */
    public function addCouponAjax(
        Request          $request,
        CouponService    $couponService,
        CouponRepository $couponRepository
    ): Response
    {
        $code = $request->request->get('code');
        if (!Validate::not_null($code)) {
            return $this->json(["error" => true, 'message' => "Please enter the coupon code"]);
        }

        $cart = $this->cartService->getCart();
        if (!$cart instanceof Cart) {
            return $this->json(["error" => true, 'message' => "don't play with us"]);
        }

        $coupon = $couponRepository->findOneBy(array('code' => $code));
        if (!$coupon) {
            return $this->json([
                "error" => true,
                "message" => "Please add a valid Coupon",
                'cart' => $this->getCartObject($cart),
            ]);
        }

        $validateCoupon = $couponService->validateCoupon($cart, $coupon, true);
        if ($validateCoupon !== true) {
            return $this->json([
                "error" => true,
                "message" => $validateCoupon,
                'cart' => $this->getCartObject($cart),
            ]);
        }

        $cart->setCoupon($coupon);
        $this->em()->persist($cart);
        $this->em()->flush();

        $this->cartService->initCart($cart);

        return $this->json([
            "error" => false,
            "message" => $this->translator->trans("coupon_code_added_successfully_msg"),
            'cart' => $this->getCartObject($cart),
        ]);
    }

    /**
     * @Route("/remove-coupon-ajax", name="fe_cart_widget_remove_coupon_ajax", methods={"POST"})
     */
    public function removeCouponAjax(): Response
    {
        $cart = $this->cartService->getCart();
        if (!$cart instanceof Cart) {
            return $this->json(["error" => true, 'message' => "don't play with us"]);
        }

        $cart->setCoupon(null);
        $this->em()->persist($cart);
        $this->em()->flush();

        $this->cartService->initCart($cart);

        return $this->json([
            "error" => false,
            "message" => $this->translator->trans("coupon_code_removed_successfully_msg"),
            'cart' => $this->getCartObject($cart),
        ]);
    }

    private function doRemoveFromCartAndAddToWishlist(
        ProductFavoriteRepository $productFavoriteRepository,
        CartHasProductPrice       $cartHasProductPrice = null
    ): void
    {
        $user = $this->getUser();
        if (!$cartHasProductPrice instanceof CartHasProductPrice or !$user instanceof User) {
            return;
        }
        $product = $cartHasProductPrice->getProductPrice()->getProduct();

        $productFavorite = $productFavoriteRepository->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);
        if (!$productFavorite instanceof ProductFavorite) {
            $productFavorite = new ProductFavorite();
            $productFavorite->setProduct($product);
            $productFavorite->setUser($user);
            $this->em()->persist($productFavorite);
        }

        $this->em()->remove($cartHasProductPrice);
        $this->em()->flush();

    }

    public function getCartLastUpdateHash(Request $request): Response
    {
        $userAgent = $request->headers->get("User-Agent");
        foreach ($this->excludeAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return new Response('null');
            }
        }

        $cart = $this->cartService->getCart(createCartIfNotExist: true);
        if (!$cart instanceof Cart) {
            return new Response('null');
        }
        $hash = $this->getLastUpdatedHash($cart);

        return new Response('"' . $hash . '"');
    }


    public function getLastUpdatedHash(Cart $cart): string
    {
        $lastCartUpdateDate = $this->cartRepository->getLastUpdateDate($cart);

        if ($lastCartUpdateDate instanceof \DateTimeInterface) {
            $lastCartUpdateDate = $lastCartUpdateDate->format(Date::DATE_FORMAT1);

            return md5($lastCartUpdateDate . "-" . $this->translator->getLocale() . "-" . $cart->getId());
        }

        return md5($this->translator->getLocale() . "-" . $cart->getId() . "-" . $cart->getCreated()->format(Date::DATE_FORMAT1));
    }

    private function getCartObject(Cart $cart): array
    {
        $object = $cart->getObj();
        $object["products"] = [];
        $object["lastUpdateHash"] = $this->getLastUpdatedHash($cart);
        $object['subTotal'] = round($cart->getSubTotal(), 2);
        $object['discount'] = round($cart->getDiscount(), 2);
        $object['shippingFee'] = round($cart->getShippingFees(), 2);
        $object['extraFees'] = round($cart->getExtraFees(), 2);
        $object['grandTotal'] = round($cart->getGrandTotal(), 2);

        foreach ($cart->getCartHasProductPrices() as $cartHasProductPrice) {
            $object['products'][] = $this->getCartHasProductPrice($cartHasProductPrice);
        }
        return $object;
    }

    private function getCartHasProductPrice(CartHasProductPrice $cartHasProductPrice): array
    {
        $shippingTime = $cartHasProductPrice->getShippingTime();
        $shippingTimeObj = null;
        if ($shippingTime instanceof ShippingTime) {
            $shippingTimeObj = [
                "id" => $shippingTime->getId(),
                "name" => $shippingTime->getName(),
            ];
        }
        return [
            "product" => $this->cartProductDTO->getProduct($cartHasProductPrice->getProductPrice()),
            "qty" => $cartHasProductPrice->getQty(),
            "shippingTime" => $shippingTimeObj
        ];
    }


}