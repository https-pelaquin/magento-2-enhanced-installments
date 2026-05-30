<?php
/**
 * Copyright (c) Bruno Pelaquin. All rights reserved.
 * https://github.com/https-pelaquin
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Plugin;

use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Checkout\CustomerData\Cart;
use Pelaquin\EnhancedInstallments\Helper\Data as Helper;

class CartPlugin
{
    public function __construct(
        private readonly PricingHelper $princingHelper,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Session $customerSession,
        private readonly Helper $helper
    ) {}

    public function afterGetSectionData(Cart $subject, array $result)
    {
        $pixSubtotalAmountWithDiscount = 0;
        $installmentNumber = $this->helper->getInstallmentNumber();

        $productIds = [];

        // Validating and Separating Cart Product IDs
        if (isset($result['items']) && is_array($result['items'])) {
            foreach ($result['items'] as $item) {
                if (!empty($item['product_id'])) {
                    $productIds[] = $item['product_id'];
                }
            }
        }

        if (!empty($productIds)) {
            // Creating a Collection to retrieve data from all products in the cart
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['price', 'bp_slip_discount_per_group', 'bp_pix_discount', 'billet_price']);
            $collection->addFieldToFilter('entity_id', ['in' => $productIds]);

            foreach ($collection as $product) {
                // Pix discount calculation for the product
                $pixFinalPrice = $this->getProductFinalPriceWithDiscount($product,"pix", "bp_pix_discount");

                foreach ($result['items'] as $item) {
                    if ($item['product_id'] == $product->getId()) {

                        // Multiplying the product value by the quantity added to the cart and adding the value to the total sum
                        $pixSubtotalAmountWithDiscount = $pixSubtotalAmountWithDiscount + ($pixFinalPrice * $item['qty']);
                    }
                }
            }
        }

        $cartSubTotal = (float) $result['subtotalAmount'];
        $instalmentPrice = round($cartSubTotal / $installmentNumber, 2);

        if($instalmentPrice < $this->helper->getMinimalInstallmentAmount()){
            $installmentNumber = floor($cartSubTotal / $this->helper->getMinimalInstallmentAmount());
            if($installmentNumber <= 0){
                $installmentNumber = 1;
            }
            $instalmentPrice = round($cartSubTotal / $installmentNumber, 2);
        }

        $result['bp_pix_minicart_subtotal_formated'] = $this->princingHelper->currency($pixSubtotalAmountWithDiscount);
        $result['bp_pix_minicart_subtotal_value'] = $pixSubtotalAmountWithDiscount;
        $result['bp_pix_text'] = __(" no pix");
        $result['bp_installments'] = __("ou em até %1x de %2 sem juros no cartão", $installmentNumber, $this->princingHelper->currency($instalmentPrice));

        return $result;
    }

    private function priceGrouped($product) : float
    {
        $_associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
        $finalPrice = 0;
        foreach ($_associatedProducts as $_item) {
            $finalPrice += $_item->getFinalPrice() * $_item->getQty();
        }

        return $finalPrice;
    }

    private function priceSimple($product)
    {
        return (float) $product->getTierPrices() ? min($product->getFinalPrice(), $product->getTierPrice(1)) : $product->getFinalPrice();
    }

    public function getProductFinalPriceWithDiscount($product, $productArrayKey, $storeUrlKey)
    {
        $productFinalPrice = match ($product->getTypeId()) {
            "grouped" => $this->priceGrouped($product),
            "simple" => $this->priceSimple($product),
            default => $product->getFinalPrice()
        };
        $pixDiscountPercent = $this->helper->getDiscountData($product, $productArrayKey, $storeUrlKey);
        $pixDiscount = $productFinalPrice * $pixDiscountPercent / 100;
        return round($productFinalPrice - $pixDiscount, 2);
    }
}
