<?php
/**
 * Copyright (c) Bruno Pelaquin. All rights reserved.
 * https://github.com/https-pelaquin
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Block\Lists;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Session;
use Pelaquin\EnhancedInstallments\Helper\Data;

class PriceDiscount extends Template
{
    public function __construct(
        Context $context,
        private readonly FormatInterface $localeFormat,
        private readonly EncoderInterface $_jsonEncoder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Session $customerSession,
        private readonly Data $helper,
        array $data = []
    ){
        parent::__construct($context, $data);
    }

    public function getJsonConfig($product)
    {
        $config = [
            'productId' => $product->getId(),
            'priceFormat' => $this->localeFormat->getPriceFormat()
        ];
        return $this->_jsonEncoder->encode($config);
    }

    public function getFinalPrice()
    {
        $product = $this->getProduct();

        return match ($product->getTypeId()) {
            "grouped" => $this->priceGrouped($product),
            "simple" => $this->priceSimple($product),
            default => $product->getFinalPrice()
        };
    }

    private function getDiscountData($productArrayKey, $storeUrlKey)
    {
        if(!empty($this->getProduct()->getData('bp_slip_discount_per_group'))){
            $discountJson = json_decode($this->getProduct()->getData('bp_slip_discount_per_group'), true);
            foreach ($discountJson['product_slip_discount_field'] as $discount) {
                if ($discount['customer_group'] == $this->getCustomerGroup() && $discount['discount_type'] == $productArrayKey) {
                    return $discount['discount'];
                }
            }
        }

        $productPercent = $this->getProduct()->getCustomAttribute($productArrayKey == 'pix' ? 'bp_pix_discount' : 'bp_bank_slip_discount');
        return (empty($productPercent)) ? $this->scopeConfig->getValue('bp_installment_billet_price/general/'. $storeUrlKey, ScopeInterface::SCOPE_STORE) : $productPercent->getValue();
    }

    public function validateBiggestDiscount() : string
    {
        $pixPercent = $this->getDiscountData("pix", "bp_pix_discount");
        $bankSlipPercent = $this->getDiscountData("boleto", "bp_bank_slip_discount");
        if ($bankSlipPercent > $pixPercent) {
            return "bankSlipDiscount";
        }

        return "pixDiscount";
    }

    public function getText() : string
    {
        if ($this->validateBiggestDiscount() == "bankSlipDiscount") {
            return "special price with %1% discount";
        }

        return "on pix (%1% discount)";
    }

    public function getDiscountPercent()
    {
        if ($this->validateBiggestDiscount() == "bankSlipDiscount") {
            return $this->getDiscountData("boleto", "bp_bank_slip_discount");
        }

        return $this->getDiscountData("pix", "bp_pix_discount");
    }

    private function priceGrouped($product) : float
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

    public function hasDiscount() : bool
    {
        return $this->getDiscountPercent() != 0;
    }

    public function getCustomerGroup()
    {
        return $this->customerSession->getCustomerGroupId();
    }
}
