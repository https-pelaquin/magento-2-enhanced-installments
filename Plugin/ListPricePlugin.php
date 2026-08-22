<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Plugin;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Framework\Pricing\Render;
use Magento\Framework\View\Element\Template;
use Pelaquin\EnhancedInstallments\Block\Lists\Installment;
use Pelaquin\EnhancedInstallments\Block\Lists\PriceDiscount;
use Pelaquin\EnhancedInstallments\Model\Config;

class ListPricePlugin
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function afterGetProductPrice(
        ListProduct $subject,
        string $result,
        Product $product
    ): string {
        return $result . $this->renderEnhancedInstallments($subject, $product);
    }

    public function afterGetProductPriceHtml(
        ProductsList $subject,
        string $result,
        Product $product,
        mixed $priceType = null,
        mixed $renderZone = Render::ZONE_ITEM_LIST,
        array $arguments = []
    ): string {
        return $result . $this->renderEnhancedInstallments($subject, $product);
    }

    private function renderEnhancedInstallments(Template $subject, Product $product): string
    {
        if (!$this->config->isEnabled()) {
            return '';
        }

        $layout = $subject->getLayout();
        $uniqueId = uniqid((string) $product->getId() . '-', false);
        $priceDiscount = $layout->createBlock(
            PriceDiscount::class,
            'bp.list.price.discount.' . $uniqueId
        )->setTemplate('Pelaquin_EnhancedInstallments::list/price_discount.phtml')
            ->setProduct($product)
            ->toHtml();
        $installment = $layout->createBlock(
            Installment::class,
            'bp.list.installment.' . $uniqueId
        )->setTemplate('Pelaquin_EnhancedInstallments::list/installment.phtml')
            ->setProduct($product)
            ->toHtml();

        return $priceDiscount . $installment;
    }
}
