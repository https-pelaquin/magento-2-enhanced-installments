<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model\Discount;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Pelaquin\EnhancedInstallments\Model\PaymentMethod;
use Pelaquin\EnhancedInstallments\Model\Percentage;

class DiscountPerGroup
{
    public const FIELDSET_NAME = 'bp_payment_discount_fieldset';
    public const ROWS_NAME = 'bp_payment_discount_rules';

    public function __construct(
        private readonly Json $json,
        private readonly Percentage $percentage,
        private readonly PaymentMethod $paymentMethod
    ) {
    }

    /**
     * @return array<int, array{customer_group: string, discount_type: string, discount: float, sort_order?: int}>
     */
    public function unserialize(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        try {
            $data = $this->json->unserialize($value);
        } catch (\InvalidArgumentException) {
            return [];
        }

        $rows = is_array($data) ? ($data[self::ROWS_NAME] ?? []) : [];
        if (!is_array($rows)) {
            return [];
        }

        $validRows = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !empty($row['is_delete'])) {
                continue;
            }

            $normalizedRow = $this->normalizeRow($row);
            if ($normalizedRow !== null) {
                $validRows[] = $normalizedRow;
            }
        }

        return $validRows;
    }

    /**
     * @param array<int, mixed> $rows
     * @throws LocalizedException
     */
    public function serialize(array $rows): ?string
    {
        $validRows = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !empty($row['is_delete'])) {
                continue;
            }

            $normalizedRow = $this->normalizeRow($row);
            if ($normalizedRow === null) {
                throw new LocalizedException(
                    __('Each customer group discount must have a group, payment method and percentage from 0 to 100.')
                );
            }

            $validRows[] = $normalizedRow;
        }

        if ($validRows === []) {
            return null;
        }

        return $this->json->serialize([self::ROWS_NAME => $validRows]);
    }

    public function findDiscount(mixed $value, int $customerGroupId, string $paymentMethod): ?float
    {
        foreach ($this->unserialize($value) as $row) {
            if ((int) $row['customer_group'] === $customerGroupId
                && $row['discount_type'] === $paymentMethod
            ) {
                return $row['discount'];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{customer_group: string, discount_type: string, discount: float, sort_order?: int}|null
     */
    private function normalizeRow(array $row): ?array
    {
        $customerGroup = $row['customer_group'] ?? null;
        $customerGroupId = filter_var(
            $customerGroup,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );
        $paymentMethod = $row['discount_type'] ?? null;
        $discount = $this->percentage->normalize($row['discount'] ?? null);

        if ($customerGroupId === false
            || !is_string($paymentMethod)
            || !$this->paymentMethod->isSupported($paymentMethod)
            || $discount === null
        ) {
            return null;
        }

        $normalizedRow = [
            'customer_group' => (string) $customerGroupId,
            'discount_type' => $paymentMethod,
            'discount' => $discount,
        ];

        if (isset($row['sort_order']) && is_numeric($row['sort_order'])) {
            $normalizedRow['sort_order'] = max(0, (int) $row['sort_order']);
        }

        return $normalizedRow;
    }
}
