<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Pelaquin\EnhancedInstallments\Model\Discount\DiscountPerGroup;

class DiscountResolver
{
    public function __construct(
        private readonly Config $config,
        private readonly CustomerGroupProvider $customerGroupProvider,
        private readonly DiscountPerGroup $discountPerGroup,
        private readonly Percentage $percentage,
        private readonly PaymentMethod $paymentMethod
    ) {
    }

    public function getDiscount(
        ProductInterface $product,
        string $paymentMethod,
        ?int $storeId = null,
        ?int $customerGroupId = null
    ): float {
        if (!$this->paymentMethod->isSupported($paymentMethod)) {
            throw new \InvalidArgumentException('Unsupported payment method.');
        }

        $groupId = $customerGroupId ?? $this->customerGroupProvider->getCustomerGroupId();
        $groupDiscount = $this->discountPerGroup->findDiscount(
            $product->getCustomAttribute(ProductAttribute::DISCOUNT_PER_GROUP)?->getValue(),
            $groupId,
            $paymentMethod
        );

        if ($groupDiscount !== null) {
            return $groupDiscount;
        }

        $attributeCode = $paymentMethod === PaymentMethod::PIX
            ? ProductAttribute::PIX_DISCOUNT
            : ProductAttribute::BANK_SLIP_DISCOUNT;
        $productDiscount = $this->percentage->normalize(
            $product->getCustomAttribute($attributeCode)?->getValue()
        );

        return $productDiscount ?? $this->config->getDefaultDiscount($paymentMethod, $storeId);
    }

    public function getDiscountedPrice(float $price, float $discount): float
    {
        $price = is_finite($price) ? max(0.0, $price) : 0.0;
        $normalizedDiscount = $this->percentage->normalize($discount) ?? 0.0;

        return $price * (1 - ($normalizedDiscount / 100));
    }
}
