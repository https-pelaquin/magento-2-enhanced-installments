<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Pelaquin\EnhancedInstallments\Model\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    public function testUsesTierPriceWithoutBuildingTheTierPriceList(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getFinalPrice')->willReturn(80.0);
        $product->expects(self::never())->method('getTierPrices');
        $product->expects(self::once())->method('getTierPrice')->with(1)->willReturn(70.0);

        self::assertSame(70.0, (new PriceCalculator())->getFinalPrice($product));
    }
}
