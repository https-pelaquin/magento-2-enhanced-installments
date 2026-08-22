<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model;

class Percentage
{
    public function normalize(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null || !is_numeric($value)) {
            return null;
        }

        $percentage = (float) $value;
        if (!is_finite($percentage) || $percentage < 0 || $percentage > 100) {
            return null;
        }

        return $percentage;
    }
}
