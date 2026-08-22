<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Test\Unit\Model\Discount;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Pelaquin\EnhancedInstallments\Model\Discount\DiscountPerGroup;
use Pelaquin\EnhancedInstallments\Model\PaymentMethod;
use Pelaquin\EnhancedInstallments\Model\Percentage;
use PHPUnit\Framework\TestCase;

class DiscountPerGroupTest extends TestCase
{
    private DiscountPerGroup $discountPerGroup;

    protected function setUp(): void
    {
        $this->discountPerGroup = new DiscountPerGroup(
            new Json(),
            new Percentage(),
            new PaymentMethod()
        );
    }

    public function testSerializesOnlyActiveValidRows(): void
    {
        $value = $this->discountPerGroup->serialize([
            [
                'customer_group' => '2',
                'discount_type' => 'pix',
                'discount' => '12.5',
                'sort_order' => '1',
            ],
            [
                'customer_group' => '3',
                'discount_type' => 'boleto',
                'discount' => '10',
                'is_delete' => '1',
            ],
        ]);

        self::assertSame(12.5, $this->discountPerGroup->findDiscount($value, 2, 'pix'));
        self::assertNull($this->discountPerGroup->findDiscount($value, 3, 'boleto'));
    }

    public function testZeroDiscountIsAValidExplicitOverride(): void
    {
        $value = $this->discountPerGroup->serialize([
            [
                'customer_group' => 1,
                'discount_type' => 'boleto',
                'discount' => 0,
            ],
        ]);

        self::assertSame(0.0, $this->discountPerGroup->findDiscount($value, 1, 'boleto'));
    }

    public function testRejectsInvalidPercentageOnSave(): void
    {
        $this->expectException(LocalizedException::class);

        $this->discountPerGroup->serialize([
            [
                'customer_group' => 1,
                'discount_type' => 'pix',
                'discount' => 101,
            ],
        ]);
    }

    public function testRejectsNonIntegerCustomerGroupOnSave(): void
    {
        $this->expectException(LocalizedException::class);

        $this->discountPerGroup->serialize([
            [
                'customer_group' => '1.5',
                'discount_type' => 'pix',
                'discount' => 10,
            ],
        ]);
    }

    public function testInvalidStoredJsonFailsSafely(): void
    {
        self::assertSame([], $this->discountPerGroup->unserialize('{invalid-json'));
    }
}
