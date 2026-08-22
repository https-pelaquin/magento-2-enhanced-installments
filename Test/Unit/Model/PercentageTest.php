<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Test\Unit\Model;

use Pelaquin\EnhancedInstallments\Model\Percentage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PercentageTest extends TestCase
{
    #[DataProvider('valuesProvider')]
    public function testNormalize(mixed $value, ?float $expected): void
    {
        self::assertSame($expected, (new Percentage())->normalize($value));
    }

    public static function valuesProvider(): array
    {
        return [
            'zero' => [0, 0.0],
            'integer' => [15, 15.0],
            'numeric string' => [' 7.5 ', 7.5],
            'maximum' => [100, 100.0],
            'empty' => ['', null],
            'negative' => [-1, null],
            'above maximum' => [101, null],
            'non numeric' => ['discount', null],
        ];
    }
}
