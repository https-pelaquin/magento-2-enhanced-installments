<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model;

class PaymentMethod
{
    public const PIX = 'pix';
    public const BANK_SLIP = 'boleto';

    public function isSupported(string $paymentMethod): bool
    {
        return $paymentMethod === self::PIX || $paymentMethod === self::BANK_SLIP;
    }
}
