<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Checkout\CustomerData\Cart;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Pelaquin\EnhancedInstallments\Model\Config;
use Pelaquin\EnhancedInstallments\Model\DiscountResolver;
use Pelaquin\EnhancedInstallments\Model\InstallmentCalculator;
use Pelaquin\EnhancedInstallments\Model\PaymentMethod;
use Pelaquin\EnhancedInstallments\Model\PriceCalculator;
use Pelaquin\EnhancedInstallments\Model\ProductAttribute;

class CartPlugin
{
    public function __construct(
        private readonly PricingHelper $pricingHelper,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly Config $config,
        private readonly DiscountResolver $discountResolver,
        private readonly PriceCalculator $priceCalculator,
        private readonly InstallmentCalculator $installmentCalculator
    ) {
    }

    public function afterGetSectionData(Cart $subject, array $result): array
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $items = $result['items'] ?? [];
        $pixSubtotal = $this->getPixSubtotal(is_array($items) ? $items : []);
        $cartSubtotal = max(0.0, (float) ($result['subtotalAmount'] ?? 0));
        $installment = $this->installmentCalculator->calculate(
            $cartSubtotal,
            $this->config->getInstallmentNumber(),
            $this->config->getMinimumInstallmentAmount()
        );

        $result['bp_pix_minicart_subtotal_formatted'] = $this->pricingHelper->currency($pixSubtotal);
        $result['bp_pix_minicart_subtotal_value'] = $pixSubtotal;
        $result['bp_pix_text'] = __(' via PIX');
        $result['bp_installments'] = __(
            'or in up to %1 installments of %2 without interest on the credit card',
            $installment->count,
            $this->pricingHelper->currency($installment->amount)
        );

        return $result;
    }

    /**
     * Calculate the informational PIX subtotal using each quote item's unit price.
     *
     * @param array<int, array<string, mixed>> $items
     */
    private function getPixSubtotal(array $items): float
    {
        $itemsByProduct = $this->groupItemsByProduct($items);
        if ($itemsByProduct === []) {
            return 0.0;
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect([
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            ProductAttribute::DISCOUNT_PER_GROUP,
            ProductAttribute::PIX_DISCOUNT,
        ]);
        $collection->addFieldToFilter('entity_id', ['in' => array_keys($itemsByProduct)]);

        $subtotal = 0.0;
        foreach ($collection as $product) {
            $discount = $this->discountResolver->getDiscount($product, PaymentMethod::PIX);
            $fallbackPrice = null;

            foreach ($itemsByProduct[(int) $product->getId()] ?? [] as $item) {
                $price = $item['price'];
                if ($price === null) {
                    $fallbackPrice ??= $this->priceCalculator->getFinalPrice($product);
                    $price = $fallbackPrice;
                }

                $subtotal += $this->discountResolver->getDiscountedPrice($price, $discount)
                    * $item['quantity'];
            }
        }

        return round($subtotal, 2);
    }

    /**
     * Normalize and group cart rows without merging different option prices.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<int, array{quantity: float, price: ?float}>>
     */
    private function groupItemsByProduct(array $items): array
    {
        $itemsByProduct = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = $item['qty'] ?? null;
            if ($productId <= 0 || !is_numeric($quantity) || (float) $quantity <= 0) {
                continue;
            }

            $price = $item['product_price_value'] ?? null;
            $price = is_numeric($price) && is_finite((float) $price)
                ? max(0.0, (float) $price)
                : null;

            $itemsByProduct[$productId][] = [
                'quantity' => (float) $quantity,
                'price' => $price,
            ];
        }

        return $itemsByProduct;
    }
}
