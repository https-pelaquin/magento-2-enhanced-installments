<?php
/**
 * Copyright (c) Bruno Pelaquin. All rights reserved.
 * https://github.com/https-pelaquin
 */

namespace Pelaquin\EnhancedInstallments\Plugin;

use Magento\Catalog\Model\Product as ProductModel;
use MageWorx\SearchSuiteAutocomplete\Block\Product;
use Magento\Catalog\Block\Product\AbstractProduct;

class SearchRecommendationPlugin extends AbstractProduct
{
    public function afterGetProductPriceHtml(
        Product $subject,
        $result,
        ProductModel $product,
        $priceType,
        $renderZone = 1,
        array $arguments = []
    ){
        $bpPrice = $this->getLayout()->createBlock('Pelaquin\EnhancedInstallments\Block\Lists\PriceDiscount')
            ->setTemplate('Pelaquin_EnhancedInstallments::list/price_discount.phtml')
            ->setProduct($product)
            ->setData('isSearchRecommendation', true)
            ->toHtml();

        return $result . $bpPrice;
    }
}
