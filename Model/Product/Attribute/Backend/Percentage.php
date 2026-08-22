<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model\Product\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Pelaquin\EnhancedInstallments\Model\Percentage as PercentageNormalizer;

class Percentage extends AbstractBackend
{
    public function __construct(
        private readonly PercentageNormalizer $percentageNormalizer
    ) {
    }

    /**
     * Validate and normalize a product percentage before saving it.
     *
     * @param DataObject $object
     * @throws LocalizedException
     */
    public function beforeSave($object): self
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        if (!$object->hasData($attributeCode)) {
            return parent::beforeSave($object);
        }

        $value = $object->getData($attributeCode);

        if ($value === null || (is_string($value) && trim($value) === '')) {
            $object->setData($attributeCode, null);

            return parent::beforeSave($object);
        }

        $percentage = $this->percentageNormalizer->normalize($value);
        if ($percentage === null) {
            throw new LocalizedException(__('Enter a percentage from 0 to 100.'));
        }

        $object->setData($attributeCode, $percentage);

        return parent::beforeSave($object);
    }
}
