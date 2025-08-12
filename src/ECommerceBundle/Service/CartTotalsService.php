<?php

namespace App\ECommerceBundle\Service;

use App\CurrencyBundle\Service\ExchangeRateService;
use App\CurrencyBundle\Service\UserCurrencyService;
use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\Coupon;
use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Entity\Zone;
use App\NewShippingBundle\Service\ShippingFeeService;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\ProductBundle\Entity\ProductPrice;
use App\ShippingBundle\Entity\ShippingAddress;

class CartTotalsService
{

    public function __construct(
        private readonly CouponService       $couponService,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly ShippingFeeService  $shippingFeeService,
        private readonly UserCurrencyService $userCurrencyService,

    )
    {
    }

    public function getCartSubTotal(Cart $cart): float
    {
        $total = 0;
        foreach ($cart->getCartHasProductPrices() as $price) {
            $sellPrice = $this->exchangeRateService->convertAmountUserCurrency(
                $price->getProductPrice()->getCurrency(),
                $price->getProductPrice()->getUnitPriceWithCommission()
            );

            $total += $price->getQty() * $sellPrice;
        }
        return $total;
    }

    public function getCartDiscount(Cart $cart): float
    {
        $discount = $this->discountByPromotionPrice($cart);
        $discount += $this->couponService->getCouponDiscount($cart);
        return $discount;
    }

    public function getCartShippingFees(Cart $cart): float
    {
        $totalShippingFees = 0;

        $zone = null;
        if ($cart->getCartGuestData()) {
            $zone = $cart->getCartGuestData()->getZone();
        } else {
            $shippingAddress = $cart->getShippingAddress();
            if ($shippingAddress instanceof ShippingAddress) {
                $zone = $shippingAddress->getZone();
            }
        }
        $subTotal = $this->getCartSubTotal($cart);
        $shipments = [];
        if ($subTotal > 0 and $zone != null) {
//            $totalShippingFees += $zone->getPrice();

            foreach ($cart->getCartHasProductPrices() as $cartHasProductPrice) {
                $productPrice = $cartHasProductPrice->getProductPrice();
                $product = $productPrice->getProduct();

                $weight = $this->shippingFeeService->productPriceWeight(
                    $productPrice,
                    $cartHasProductPrice->getQty()
                );
                if (!$product?->getStoreAddress()?->getZone() instanceof Zone or !$cartHasProductPrice->getShippingTime() instanceof ShippingTime) {
                    continue;
                }

                $identifier = $product?->getStoreAddress()->getId() . '-' . $cartHasProductPrice->getShippingTime()->getId();

                if (array_key_exists($identifier, $shipments)) {
                    $shipments[$identifier]['weight'] += $weight;
                    continue;
                }

                $shipments[$identifier] = [
                    'shippingTime' => $cartHasProductPrice->getShippingTime(),
                    'sourceZone' => $product->getStoreAddress()->getZone(),
                    'weight' => $weight,
                ];


            }

            foreach ($shipments as $shipment) {
                $shippingTime = $shipment['shippingTime'];
                $sourceZone = $shipment['sourceZone'];
                $weight = $shipment['weight'];

                $shippingFee = $this->shippingFeeService->calculate(
                    sourceZone: $sourceZone,
                    targetZone: $zone,
                    shippingTime: $shippingTime,
                    currency: $this->userCurrencyService->getCurrency(),
                    totalProductPriceWeight: $weight
                );
                $totalShippingFees += $shippingFee->shippingFee;
            }

        }

        return $totalShippingFees;
    }

    public function getCartExtraFees(Cart $cart): float
    {
        $extraFees = 0;
        $paymentMethod = $cart->getPaymentMethod();
        if (
            $paymentMethod instanceof PaymentMethod
            and (
                $cart->getCoupon() == null
                or
                ($cart->getCoupon() instanceof Coupon and !$cart->getCoupon()->isFreePaymentMethodFee())
            )
        ) {
            $extraFees += $paymentMethod->getFees();
        }

        return $extraFees;
    }

    public function getCartGrandTotal(Cart $cart): float
    {
        $subTotal = $cart->getSubTotal();
        $shippingFees = $cart->getShippingFees();

        $discount = $cart->getDiscount();
        $extraFees = $cart->getExtraFees();

        return $subTotal + $shippingFees - $discount + $extraFees;
    }


    private function discountByPromotionPrice(Cart $cart): float
    {
        $discount = 0;
        foreach ($cart->getCartHasProductPrices() as $cartHasProductPrice) {

            /**
             * @var ProductPrice $productPrice
             */
            $productPrice = $cartHasProductPrice->getProductPrice();

            $convertedOriginalPrice = $this->exchangeRateService->convertAmountUserCurrency($productPrice->getCurrency(),
                $productPrice->getUnitPriceWithCommission());

            $convertedSellPrice = $this->exchangeRateService->convertAmountUserCurrency($productPrice->getCurrency(),
                $productPrice->getSellPrice());

            $discount += $cartHasProductPrice->getQty() * ($convertedOriginalPrice - $convertedSellPrice);
        }

        return $discount;
    }


}