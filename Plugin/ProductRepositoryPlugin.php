<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Plugin;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Pelaquin\EnhancedInstallments\Model\ProductAttribute;

class ProductRepositoryPlugin
{
    public function __construct(
        private readonly ProductExtensionFactory $productExtensionFactory
    ) {
    }

    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ): ProductInterface {
        return $this->addDiscountPerGroupExtensionAttribute($product);
    }

    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ): ProductSearchResultsInterface {
        foreach ($searchResults->getItems() as $product) {
            $this->addDiscountPerGroupExtensionAttribute($product);
        }

        return $searchResults;
    }

    private function addDiscountPerGroupExtensionAttribute(ProductInterface $product): ProductInterface
    {
        $extensionAttributes = $product->getExtensionAttributes()
            ?? $this->productExtensionFactory->create();
        $attribute = $product->getCustomAttribute(ProductAttribute::DISCOUNT_PER_GROUP);

        $extensionAttributes->setBpSlipDiscountPerGroup($attribute?->getValue());
        $product->setExtensionAttributes($extensionAttributes);

        return $product;
    }
}
