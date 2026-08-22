<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pelaquin\EnhancedInstallments\Model\Config;
use Pelaquin\EnhancedInstallments\Model\PaymentMethod;
use Pelaquin\EnhancedInstallments\Model\Percentage;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testReadsPercentageFromRequestedStore(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects(self::once())
            ->method('getValue')
            ->with(Config::XML_PATH_PIX_DISCOUNT, ScopeInterface::SCOPE_STORE, 3)
            ->willReturn('7.5');

        $config = new Config($scopeConfig, new Percentage());

        self::assertSame(7.5, $config->getDefaultDiscount(PaymentMethod::PIX, 3));
    }

    public function testInvalidInstallmentValuesHaveSafeFallbacks(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnMap([
            [Config::XML_PATH_INSTALLMENT_NUMBER, ScopeInterface::SCOPE_STORE, null, '0'],
            [Config::XML_PATH_MINIMUM_INSTALLMENT_AMOUNT, ScopeInterface::SCOPE_STORE, null, 'invalid'],
        ]);

        $config = new Config($scopeConfig, new Percentage());

        self::assertSame(1, $config->getInstallmentNumber());
        self::assertSame(0.0, $config->getMinimumInstallmentAmount());
    }

    public function testNonFiniteMinimumInstallmentHasSafeFallback(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('1e309');

        $config = new Config($scopeConfig, new Percentage());

        self::assertSame(0.0, $config->getMinimumInstallmentAmount());
    }
}
