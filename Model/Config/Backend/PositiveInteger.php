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

class PositiveInteger extends Value
{
    /**
     * @throws LocalizedException
     */
    public function beforeSave(): self
    {
        $value = $this->getValue();
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new LocalizedException(__('Enter an integer greater than zero.'));
        }

        $this->setValue((int) $value);

        return parent::beforeSave();
    }
}
