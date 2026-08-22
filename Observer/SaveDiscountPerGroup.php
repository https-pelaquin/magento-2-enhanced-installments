<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pelaquin\EnhancedInstallments\Model\Discount\DiscountPerGroup;
use Pelaquin\EnhancedInstallments\Model\ProductAttribute;

class SaveDiscountPerGroup implements ObserverInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly DiscountPerGroup $discountPerGroup
    ) {
    }

    public function execute(Observer $observer): void
    {
        $product = $observer->getData('product');
        $productData = $this->request->getParam('product');

        if (!$product instanceof Product || !is_array($productData)) {
            return;
        }

        $fieldsetData = $productData[DiscountPerGroup::FIELDSET_NAME] ?? null;
        if (!is_array($fieldsetData)) {
            return;
        }

        $rows = $fieldsetData[DiscountPerGroup::ROWS_NAME] ?? [];
        $product->setData(
            ProductAttribute::DISCOUNT_PER_GROUP,
            $this->discountPerGroup->serialize(is_array($rows) ? $rows : [])
        );
    }
}
