<?php

namespace App\ECommerceBundle\Service;

use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartHasProductPrice;
use App\ECommerceBundle\Entity\Coupon;
use App\ECommerceBundle\Entity\CouponHasProduct;
use App\ECommerceBundle\Enum\CouponTypeEnum;
use App\ECommerceBundle\Repository\CouponRepository;
use App\ECommerceBundle\Repository\OrderHasCouponRepository;
use App\ProductBundle\Entity\Product;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CouponService
{
    private EntityManagerInterface $em;
    private TranslatorInterface $translator;
    private SessionInterface $session;
    private CouponRepository $couponRepository;
    private OrderHasCouponRepository $orderHasCouponRepository;

    public function __construct(
        EntityManagerInterface   $em,
        TranslatorInterface      $translator,
        RequestStack             $requestStack,
        CouponRepository         $couponRepository,
        OrderHasCouponRepository $orderHasCouponRepository
    )
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->session = $requestStack->getSession();
        $this->couponRepository = $couponRepository;
        $this->orderHasCouponRepository = $orderHasCouponRepository;
    }

    public function validateCoupon(Cart $cart, Coupon $coupon = null, bool $returnErrorAsText = false): bool|string
    {
        $session = $this->session;
        if ($coupon == null) {
            $coupon = $cart->getCoupon();
        }
        if (!$coupon->isActive()) {
            $message = $this->translator->trans("coupon_not_activated_yet_msg");
            if ($returnErrorAsText) {
                return $message;
            }
            $session->getFlashBag()->add('error', $message);

            return false;
        }

        if ($coupon->getStartDate() != null) {
            $startDate = $coupon->getStartDate();
            $currentDate = new \DateTime();
            if ($startDate->format("Y-m-d") > $currentDate->format("Y-m-d")) {
                $message = $this->translator->trans("coupon_not_activated_yet_msg");
                if ($returnErrorAsText) {
                    return $message;
                }
                $session->getFlashBag()->add('error', $message);

                return false;
            }
        }

        if ($coupon->getExpiryDate() != null) {
            $expiryDate = $coupon->getExpiryDate();
            $currentDate = new \DateTime();
            if ($expiryDate->format("Y-m-d") < $currentDate->format("Y-m-d")) {

                $message = $this->translator->trans("coupon_expired_msg");
                if ($returnErrorAsText) {
                    return $message;
                }
                $session->getFlashBag()->add('error', $message);

                return false;
            }
        }

        if ($coupon->getMinimumPurchaseAmount() != null) {
            $subTotal = $cart->getSubTotal();

            if ($coupon->getMinimumPurchaseAmount() >= $subTotal) {
                $message = $this->translator->trans("coupon_not_valid_min_purchase_amount_msg", [
                    "%amount%" => number_format($coupon->getMinimumPurchaseAmount()),
                    "%currency%" => $this->translator->trans("egp_txt"),
                ]);
                if ($returnErrorAsText) {
                    return $message;
                }
                $session->getFlashBag()->add('error', $message);

                return false;
            }
        }
        if (!$coupon->isShipping()) {
            $relatedProducts = $this->couponRepository->getRelatedProductsBetweenCartAndCoupon($coupon, $cart);
            if ($relatedProducts < 1) {
                $message = $this->translator->trans("coupon_not_apply_on_cart_msg");
                if ($returnErrorAsText) {
                    return $message;
                }
                $session->getFlashBag()->add('error', $message);

                return false;
            }
        }

        $user = $cart->getUser();

        if ($coupon->getLimitUsePerUser() > 0) {
            if ($user instanceof User) {
                $couponUsedCount = $this->couponRepository->couponUsedCountByUser($coupon, $user);
                if ($couponUsedCount >= $coupon->getLimitUsePerUser()) {
                    $message = $this->translator->trans("coupon_passed_limit_msg");
                    if ($returnErrorAsText) {
                        return $message;
                    }
                    $session->getFlashBag()->add('error', $message);

                    return false;
                }
            } else {
                $message = $this->translator->trans("login_before_use_this_coupon_msg");
                if ($returnErrorAsText) {
                    return $message;
                }
                $session->getFlashBag()->add('error', $message);

                return false;

            }
        }

        if ($coupon->isFirstOrderOnly()) {
            if ($user instanceof User) {
                $numberOfUse = $this->orderHasCouponRepository->numberOfUse($coupon, $user);
                if ($numberOfUse > 0) {
                    $message = $this->translator->trans("coupon_passed_limit_msg");
                    if ($returnErrorAsText) {
                        return $message;
                    }
                    $session->getFlashBag()->add('error', $message);
                    return false;
                }
            } else {
                $message = $this->translator->trans("login_before_use_this_coupon_msg");
                if ($returnErrorAsText) {
                    return $message;
                }
                $session->getFlashBag()->add('error', $message);

                return false;

            }
        }
        return true;
    }

    public function getCouponDiscount(Cart $cart): float
    {
        $coupon = $cart->getCoupon();
        if (!$coupon instanceof Coupon) {
            return 0;
        }

        if ($this->validateCoupon($cart, $coupon, true) !== true) {
            $cart->setCoupon(null);
            $this->em->persist($cart);
            $this->em->flush($cart);
            return 0;
        }
        if ($coupon->isShipping()) {
            return $this->getCouponShippingDiscount($cart, $coupon);
        }
        return $this->getCouponProductDiscount($cart, $coupon);
    }

//Done
    private function getCouponShippingDiscount(Cart $cart, Coupon $coupon): float
    {
        $discount = 0;

        $shippingFees = $cart->getShippingFees();
        if ($coupon->getDiscountType() == CouponTypeEnum::FIXED_AMOUNT) {
            if ($coupon->getDiscountValue() > $shippingFees) {
                $discount = $shippingFees;
            } else {
                $discount = $coupon->getDiscountValue();
            }
        } elseif ($coupon->getDiscountType() == CouponTypeEnum::PERCENTAGE) {
            $discountItem = ($shippingFees / 100) * $coupon->getDiscountValue();
            $discount += $discountItem;
        }
        return $discount;
    }

    public function getCouponProductDiscount(Cart $cart, Coupon $coupon): float
    {
        $discount = 0;
        $totalItemPrice = 0;

        $relatedProducts = $this->em->getRepository(Coupon::class)->getRelatedProductsBetweenCartAndCoupon($coupon, $cart, false);

        $cartHasProductPrices = $this->em->getRepository(CartHasProductPrice::class)->getCartProductPriceByProductIdAndCartId($cart, $relatedProducts);
        foreach ($cartHasProductPrices as $cartHasProductPrice) {
            $productPrice = $cartHasProductPrice->getProductPrice();

            if ($productPrice->getPromotionalPercentage() > 0 and !$coupon->isAddDiscountAfterProductDiscount()) {
                continue;
            }

            $totalItemPrice += $productPrice->getSellPrice() * $cartHasProductPrice->getQty();
        }
        if ($coupon->getDiscountType() == CouponTypeEnum::FIXED_AMOUNT) {
            if ($coupon->getDiscountValue() > $totalItemPrice) {
                $discount = $totalItemPrice;
            } else {
                $discount = $coupon->getDiscountValue();
            }
        } elseif ($coupon->getDiscountType() == CouponTypeEnum::PERCENTAGE) {
            $discountItem = ($totalItemPrice / 100) * $coupon->getDiscountValue();
            $discount = $discountItem;
        }
        return $discount;
    }

    //Done
    private function isProductMatchWithCoupon(Coupon $coupon, Product $product): bool
    {
        $checkCouponHasProduct = $this->em->getRepository(CouponHasProduct::class)->findOneBy(array(
            'coupon' => $coupon->getId(),
            'product' => $product->getId(),
        ));

        return $checkCouponHasProduct instanceof CouponHasProduct;
    }
}