<?php
/*
 * Copyright (c) Bruno Pelaquin. All rights reserved.
 * https://github.com/https-pelaquin
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface as UrlEncoderInterface;
use Magento\Store\Model\ScopeInterface;
use Pelaquin\EnhancedInstallments\Helper\Data;

class PriceDiscount extends View
{
    public function __construct(
        Context $context,
        UrlEncoderInterface $urlEncoder,
        EncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly PricingHelper $pricingHelper,
        private readonly Data $helper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency
        );
    }

    public function getFinalPrice()
    {
        $product = $this->getProduct();

        return match ($product->getTypeId()) {
            'grouped' => $this->priceGrouped($product),
            'simple' => $this->priceSimple($product),
            default => $product->getFinalPrice()
        };
    }

    public function getDiscountData($product, $productArrayKey, $storeUrlKey)
    {
        return $this->helper->getDiscountData($product, $productArrayKey, $storeUrlKey);
    }

    public function validateBiggestDiscount(): string
    {
        if ($this->hasBankSlipDiscount() && $this->hasPixDiscount()) {
            $pixPercent = $this->getDiscountData($this->getProduct(), 'pix', 'bp_pix_discount');
            $bankSlipPercent = $this->getDiscountData($this->getProduct(), 'boleto', 'bp_bank_slip_discount');
        } elseif ($this->hasBankSlipDiscount()) {
            return 'bankSlipDiscount';
        } elseif ($this->hasPixDiscount()) {
            return 'pixDiscount';
        }

        return ($bankSlipPercent > $pixPercent) ? 'bankSlipDiscount' : 'pixDiscount';
    }

    private function priceGrouped($product): float
    {
        $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
        $finalPrice = 0;
        foreach ($associatedProducts as $_item) {
            $finalPrice += $_item->getFinalPrice() * $_item->getQty();
        }

        return $finalPrice;
    }

    private function priceSimple($product)
    {
        return $product->getTierPrices() ? min($product->getFinalPrice(), $product->getTierPrice(1)) : $product->getFinalPrice();
    }

    public function hasBankSlipDiscount(): bool
    {
        return $this->getDiscountData($this->getProduct(), 'boleto', 'bp_bank_slip_discount') != 0;
    }

    public function hasPixDiscount(): bool
    {
        return $this->getDiscountData($this->getProduct(), 'pix', 'bp_pix_discount') != 0;
    }

    public function getCustomerGroup()
    {
        return $this->customerSession->getCustomerGroupId();
    }

    protected function roundUp($num, $precision): float
    {
        $precision = pow(10, $precision);
        return ceil($num * $precision) / $precision;
    }



    // VALIDAR MAIS TARDE

    public function priceForGoogleMerchantCenter($price): string
    {
        $discount = $price * $this->validateDiscountForMerchantCenter() / 100;
        $finalPrice = $price - $discount;
        $finalPrice = round($finalPrice, 2);
        return $this->pricingHelper->currency($finalPrice, true, false);
    }

    public function priceForStruturedData($price): float
    {
        $discount = $price * $this->validateDiscountForMerchantCenter() / 100;
        $finalPrice = $price - $discount;
        return round($finalPrice, 2);
    }

    protected function validateDiscountForMerchantCenter(): int
    {
        if ($this->validateBiggestDiscount() == 'pixDiscount') {
            return (int) $this->getDiscountData($this->getProduct(), 'pix', 'bp_pix_discount');
        }

        return (int) $this->getDiscountData($this->getProduct(), 'boleto', 'bp_bank_slip_discount');
    }


    // POR ENQAUNTO É LIXO

    // public function getDiscountData($productArrayKey, $storeUrlKey)
    // {
    //     if (!empty($this->getProduct()->getData('bp_slip_discount_per_group'))) {
    //         $discountJson = json_decode($this->getProduct()->getData('bp_slip_discount_per_group'), true);
    //         foreach ($discountJson['product_slip_discount_field'] as $discount) {
    //             if ($discount['customer_group'] == $this->getCustomerGroup() && $discount['discount_type'] == $productArrayKey) {
    //                 return $discount['discount'];
    //             }
    //         }
    //     }

    //     $productPercent = $this->getProduct()->getCustomAttribute($productArrayKey == 'pix' ? 'bp_pix_discount' : 'bp_bank_slip_discount');
    //     return empty($productPercent)
    //         ? $this->scopeConfig->getValue('bp_installment_billet_price/general/' . $storeUrlKey, ScopeInterface::SCOPE_STORE)
    //         : $productPercent->getValue();
    // }
}
