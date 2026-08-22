<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model;

use Magento\Catalog\Model\Product;

class PriceCalculator
{
    public function getFinalPrice(Product $product): float
    {
        if ($product->getTypeId() === 'grouped') {
            $price = 0.0;
            foreach ($product->getTypeInstance()->getAssociatedProducts($product) as $associatedProduct) {
                $price += max(0.0, (float) $associatedProduct->getFinalPrice())
                    * max(0.0, (float) $associatedProduct->getQty());
            }

            return $price;
        }

        $finalPrice = max(0.0, (float) $product->getFinalPrice());
        $tierPrice = $product->getTierPrice(1);

        if (is_numeric($tierPrice) && (float) $tierPrice > 0) {
            return min($finalPrice, (float) $tierPrice);
        }

        return $finalPrice;
    }

    public function getCatalogDiscount(Product $product, float $finalPrice): float
    {
        $regularPrice = max(0.0, (float) $product->getPrice());
        if ($regularPrice <= 0 || $finalPrice >= $regularPrice) {
            return 0.0;
        }

        return (($regularPrice - $finalPrice) / $regularPrice) * 100;
    }
}
