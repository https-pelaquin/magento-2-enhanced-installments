<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model;

use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ACTIVE = 'bp_enhanced_installments/general/active';
    public const XML_PATH_BANK_SLIP_DISCOUNT =
        'bp_enhanced_installments/general/bp_bank_slip_discount';
    public const XML_PATH_PIX_DISCOUNT =
        'bp_enhanced_installments/general/bp_pix_discount';
    public const XML_PATH_INSTALLMENT_NUMBER =
        'bp_enhanced_installments/general/bp_installment_number';
    public const XML_PATH_MINIMUM_INSTALLMENT_AMOUNT =
        'bp_enhanced_installments/general/bp_installment_minimal_amount';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Percentage $percentage
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getDefaultDiscount(string $paymentMethod, ?int $storeId = null): float
    {
        $path = match ($paymentMethod) {
            PaymentMethod::PIX => self::XML_PATH_PIX_DISCOUNT,
            PaymentMethod::BANK_SLIP => self::XML_PATH_BANK_SLIP_DISCOUNT,
            default => throw new InvalidArgumentException('Unsupported payment method.')
        };

        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);

        return $this->percentage->normalize($value) ?? 0.0;
    }

    public function getInstallmentNumber(?int $storeId = null): int
    {
        $value = (int) $this->scopeConfig->getValue(
            self::XML_PATH_INSTALLMENT_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return max(1, $value);
    }

    public function getMinimumInstallmentAmount(?int $storeId = null): float
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_MINIMUM_INSTALLMENT_AMOUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!is_numeric($value) || !is_finite((float) $value)) {
            return 0.0;
        }

        return max(0.0, (float) $value);
    }
}
