<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model;

class InstallmentCalculator
{
    public function calculate(
        float $price,
        int $maximumInstallments,
        float $minimumInstallmentAmount
    ): InstallmentResult {
        $price = is_finite($price) ? max(0.0, $price) : 0.0;
        $maximumInstallments = max(1, $maximumInstallments);
        $minimumInstallmentAmount = is_finite($minimumInstallmentAmount)
            ? max(0.0, $minimumInstallmentAmount)
            : 0.0;

        if ($price <= 0) {
            return new InstallmentResult(1, 0.0);
        }

        $installments = $maximumInstallments;
        if ($minimumInstallmentAmount > 0) {
            $installments = min(
                $maximumInstallments,
                max(1, (int) floor($price / $minimumInstallmentAmount))
            );
        }

        return new InstallmentResult($installments, round($price / $installments, 2));
    }
}
