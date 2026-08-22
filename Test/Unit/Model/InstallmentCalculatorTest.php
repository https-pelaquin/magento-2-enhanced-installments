<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Test\Unit\Model;

use Pelaquin\EnhancedInstallments\Model\InstallmentCalculator;
use PHPUnit\Framework\TestCase;

class InstallmentCalculatorTest extends TestCase
{
    private InstallmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InstallmentCalculator();
    }

    public function testUsesConfiguredMaximumWhenThereIsNoMinimumAmount(): void
    {
        $result = $this->calculator->calculate(100, 10, 0);

        self::assertSame(10, $result->count);
        self::assertSame(10.0, $result->amount);
    }

    public function testReducesInstallmentsToRespectMinimumAmount(): void
    {
        $result = $this->calculator->calculate(100, 10, 30);

        self::assertSame(3, $result->count);
        self::assertSame(33.33, $result->amount);
    }

    public function testPreventsZeroDivisionForInvalidConfiguration(): void
    {
        $result = $this->calculator->calculate(0, 0, 50);

        self::assertSame(1, $result->count);
        self::assertSame(0.0, $result->amount);
    }

    public function testNonFiniteValuesHaveSafeFallbacks(): void
    {
        $result = $this->calculator->calculate(INF, 10, INF);

        self::assertSame(1, $result->count);
        self::assertSame(0.0, $result->amount);
    }
}
