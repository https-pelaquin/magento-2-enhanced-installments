<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Render;
use Magento\Framework\View\Element\AbstractBlock;
use Pelaquin\EnhancedInstallments\Block\Lists\PriceDiscount;
use Pelaquin\EnhancedInstallments\Model\Config;

class SearchRecommendationPlugin
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function afterGetProductPriceHtml(
        AbstractBlock $subject,
        string $result,
        Product $product,
        mixed $priceType = null,
        mixed $renderZone = Render::ZONE_ITEM_LIST,
        array $arguments = []
    ): string {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $priceDiscount = $subject->getLayout()
            ->createBlock(PriceDiscount::class)
            ->setTemplate('Pelaquin_EnhancedInstallments::list/price_discount.phtml')
            ->setProduct($product)
            ->setData('isSearchRecommendation', true)
            ->toHtml();

        return $result . $priceDiscount;
    }
}
