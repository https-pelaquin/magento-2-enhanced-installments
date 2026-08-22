<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Test\Unit\Model\Product\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Pelaquin\EnhancedInstallments\Model\Percentage as PercentageNormalizer;
use Pelaquin\EnhancedInstallments\Model\Product\Attribute\Backend\Percentage;
use PHPUnit\Framework\TestCase;

class PercentageTest extends TestCase
{
    private Percentage $backend;

    protected function setUp(): void
    {
        $attribute = $this->createStub(AbstractAttribute::class);
        $attribute->method('getAttributeCode')->willReturn('bp_pix_discount');

        $this->backend = new Percentage(new PercentageNormalizer());
        $this->backend->setAttribute($attribute);
    }

    public function testNormalizesValidPercentage(): void
    {
        $product = new DataObject(['bp_pix_discount' => '7.5']);

        $this->backend->beforeSave($product);

        self::assertSame(7.5, $product->getData('bp_pix_discount'));
    }

    public function testKeepsEmptyValueAsNull(): void
    {
        $product = new DataObject(['bp_pix_discount' => '  ']);

        $this->backend->beforeSave($product);

        self::assertNull($product->getData('bp_pix_discount'));
    }

    public function testDoesNotAddMissingAttributeToPartialProduct(): void
    {
        $product = new DataObject();

        $this->backend->beforeSave($product);

        self::assertFalse($product->hasData('bp_pix_discount'));
    }

    public function testRejectsInvalidPercentage(): void
    {
        $this->expectException(LocalizedException::class);

        $this->backend->beforeSave(new DataObject(['bp_pix_discount' => 101]));
    }
}
