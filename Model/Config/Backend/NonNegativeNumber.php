<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class NonNegativeNumber extends Value
{
    /**
     * @throws LocalizedException
     */
    public function beforeSave(): self
    {
        $value = $this->getValue();
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0) {
            throw new LocalizedException(__('Enter a number equal to or greater than zero.'));
        }

        $this->setValue((float) $value);

        return parent::beforeSave();
    }
}
