<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Test\Unit\Plugin;

use ArrayIterator;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Checkout\CustomerData\Cart;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Pelaquin\EnhancedInstallments\Model\Config;
use Pelaquin\EnhancedInstallments\Model\DiscountResolver;
use Pelaquin\EnhancedInstallments\Model\InstallmentCalculator;
use Pelaquin\EnhancedInstallments\Model\PaymentMethod;
use Pelaquin\EnhancedInstallments\Model\PriceCalculator;
use Pelaquin\EnhancedInstallments\Plugin\CartPlugin;
use PHPUnit\Framework\TestCase;

class CartPluginTest extends TestCase
{
    public function testPixSubtotalUsesEachQuoteItemPrice(): void
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn(10);

        $collection = $this->createStub(Collection::class);
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new ArrayIterator([$product]));

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('getInstallmentNumber')->willReturn(10);
        $config->method('getMinimumInstallmentAmount')->willReturn(0.0);

        $discountResolver = $this->createMock(DiscountResolver::class);
        $discountResolver->expects(self::once())
            ->method('getDiscount')
            ->with($product, PaymentMethod::PIX)
            ->willReturn(10.0);
        $discountResolver->method('getDiscountedPrice')
            ->willReturnCallback(
                static fn (float $price, float $discount): float => $price * (1 - ($discount / 100))
            );

        $priceCalculator = $this->createMock(PriceCalculator::class);
        $priceCalculator->expects(self::never())->method('getFinalPrice');

        $pricingHelper = $this->createStub(PricingHelper::class);
        $pricingHelper->method('currency')
            ->willReturnCallback(static fn (float $value): string => (string) $value);

        $plugin = new CartPlugin(
            $pricingHelper,
            $collectionFactory,
            $config,
            $discountResolver,
            $priceCalculator,
            new InstallmentCalculator()
        );
        $result = $plugin->afterGetSectionData(
            $this->createStub(Cart::class),
            [
                'subtotalAmount' => 400,
                'items' => [
                    ['product_id' => 10, 'qty' => 1, 'product_price_value' => 100],
                    ['product_id' => 10, 'qty' => 2, 'product_price_value' => 150],
                ],
            ]
        );

        self::assertSame(360.0, $result['bp_pix_minicart_subtotal_value']);
        self::assertSame('360', $result['bp_pix_minicart_subtotal_formatted']);
    }
}
