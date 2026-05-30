<?php
/**
 * Copyright (c) Bruno Pelaquin. All rights reserved.
 * https://github.com/https-pelaquin
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly Session $customerSession
    ) {
        parent::__construct($context);
    }

    public function getMinimalInstallmentAmount()
    {
        $minimalAmount = $this->scopeConfig->getValue('bp_installment_billet_price/general/bp_installment_minimal_amount', ScopeInterface::SCOPE_STORE);

        if (!$minimalAmount || $minimalAmount <= 0) {
            $minimalAmount = 0;
        }

        return $minimalAmount;
    }
    public function getInstallmentNumber()
    {
        return $this->scopeConfig->getValue('bp_installment_billet_price/general/bp_installment_number', ScopeInterface::SCOPE_STORE);
    }


    public function getDiscountData($product, $productArrayKey, $storeUrlKey)
    {
        if (!empty($product->getData('bp_slip_discount_per_group'))) {
            $discountJson = json_decode($product->getData('bp_slip_discount_per_group'), true);
            foreach ($discountJson['product_slip_discount_field'] as $discount) {
                if ($discount['customer_group'] == $this->getCustomerGroup() && $discount['discount_type'] == $productArrayKey) {
                    return $discount['discount'];
                }
            }
        }

        $productPercent = $product->getCustomAttribute($productArrayKey == 'pix' ? 'bp_pix_discount' : 'bp_bank_slip_discount');
        return (empty($productPercent)) ? $this->scopeConfig->getValue('bp_installment_billet_price/general/' . $storeUrlKey, ScopeInterface::SCOPE_STORE) : $productPercent->getValue();
    }

    public function getCustomerGroup()
    {
        return $this->customerSession->getCustomerGroupId();
    }
}
