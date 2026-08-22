<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Pelaquin\EnhancedInstallments\Model\Config;
use Pelaquin\EnhancedInstallments\Model\CustomerGroupProvider;
use Pelaquin\EnhancedInstallments\Model\Discount\DiscountPerGroup;
use Pelaquin\EnhancedInstallments\Model\DiscountResolver;
use Pelaquin\EnhancedInstallments\Model\PaymentMethod;
use Pelaquin\EnhancedInstallments\Model\Percentage;
use Pelaquin\EnhancedInstallments\Model\ProductAttribute;
use PHPUnit\Framework\TestCase;

class DiscountResolverTest extends TestCase
{
    private Config $config;
    private CustomerGroupProvider $customerGroupProvider;
    private DiscountPerGroup $discountPerGroup;
    private Percentage $percentage;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->customerGroupProvider = $this->createStub(CustomerGroupProvider::class);
        $this->discountPerGroup = new DiscountPerGroup(
            new Json(),
            new Percentage(),
            new PaymentMethod()
        );
        $this->percentage = new Percentage();
    }

    public function testCustomerGroupDiscountHasHighestPriority(): void
    {
        $groupRules = $this->discountPerGroup->serialize([
            [
                'customer_group' => 2,
                'discount_type' => PaymentMethod::PIX,
                'discount' => 15,
            ],
        ]);
        $product = $this->createProduct([
            ProductAttribute::DISCOUNT_PER_GROUP => $groupRules,
            ProductAttribute::PIX_DISCOUNT => 8,
        ]);
        $this->customerGroupProvider->method('getCustomerGroupId')->willReturn(2);
        $this->config->expects(self::never())->method('getDefaultDiscount');

        self::assertSame(15.0, $this->createResolver()->getDiscount($product, PaymentMethod::PIX));
    }

    public function testProductDiscountOverridesStoreConfiguration(): void
    {
        $product = $this->createProduct([
            ProductAttribute::PIX_DISCOUNT => '8.5',
        ]);
        $this->customerGroupProvider->method('getCustomerGroupId')->willReturn(1);
        $this->config->expects(self::never())->method('getDefaultDiscount');

        self::assertSame(8.5, $this->createResolver()->getDiscount($product, PaymentMethod::PIX));
    }

    public function testStoreConfigurationIsTheFallback(): void
    {
        $product = $this->createProduct([]);
        $this->customerGroupProvider->method('getCustomerGroupId')->willReturn(0);
        $this->config->expects(self::once())
            ->method('getDefaultDiscount')
            ->with(PaymentMethod::BANK_SLIP, 4)
            ->willReturn(5.0);

        self::assertSame(
            5.0,
            $this->createResolver()->getDiscount($product, PaymentMethod::BANK_SLIP, 4)
        );
    }

    public function testExplicitZeroDoesNotFallBackToConfiguration(): void
    {
        $product = $this->createProduct([
            ProductAttribute::PIX_DISCOUNT => 0,
        ]);
        $this->customerGroupProvider->method('getCustomerGroupId')->willReturn(0);
        $this->config->expects(self::never())->method('getDefaultDiscount');

        self::assertSame(0.0, $this->createResolver()->getDiscount($product, PaymentMethod::PIX));
    }

    public function testNonFinitePriceFailsSafely(): void
    {
        $this->config->expects(self::never())->method('getDefaultDiscount');

        self::assertSame(0.0, $this->createResolver()->getDiscountedPrice(INF, 10));
    }

    private function createResolver(): DiscountResolver
    {
        return new DiscountResolver(
            $this->config,
            $this->customerGroupProvider,
            $this->discountPerGroup,
            $this->percentage,
            new PaymentMethod()
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createProduct(array $values): ProductInterface
    {
        $attributes = [];
        foreach ($values as $code => $value) {
            $attribute = $this->createStub(AttributeInterface::class);
            $attribute->method('getValue')->willReturn($value);
            $attributes[$code] = $attribute;
        }

        $product = $this->createStub(ProductInterface::class);
        $product->method('getCustomAttribute')
            ->willReturnCallback(
                static fn (string $code): ?AttributeInterface => $attributes[$code] ?? null
            );

        return $product;
    }
}
