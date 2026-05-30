<?php
/**
 * Copyright (c) Bruno Pelaquin. All rights reserved.
 * https://github.com/https-pelaquin
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface as UrlEncoderInterface;
use Magento\Store\Model\ScopeInterface;

class Installment extends View
{
    private const BP_INSTALLMENT_NUMBER_PATH = 'bp_installment_billet_price/general/bp_installment_number';
    private const BP_MINIMAL_INSTALLMENT_AMOUNT_PATH = 'bp_installment_billet_price/general/bp_installment_minimal_amount';

    public function __construct(
        Context $context,
        UrlEncoderInterface $urlEncoder,
        EncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        private readonly ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency
        );
    }

    public function getFinalPrice(): float
    {
        $product = $this->getProduct();

        if ($product->getTypeId() === 'grouped') {
            $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
            $finalPrice = 0.0;

            foreach ($associatedProducts as $_item) {
                $finalPrice += $_item->getFinalPrice() * $_item->getQty();
            }

            return $finalPrice;
        }

        return (float) $product->getPriceInfo()
            ->getPrice('final_price')
            ->getAmount()
            ->getValue();
    }

    public function getInstallmentNumber(): int
    {
        return (int) $this->scopeConfig->getValue(self::BP_INSTALLMENT_NUMBER_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getMinimalInstallmentAmount(): int
    {
        $minimalAmount = (int) $this->scopeConfig->getValue(self::BP_MINIMAL_INSTALLMENT_AMOUNT_PATH, ScopeInterface::SCOPE_STORE);

        if ($minimalAmount <= 0) {
            return 0;
        }

        return $minimalAmount;
    }

    public function getSlipBankDiscount(): float
    {
        return $this->getIsEnabledSlipBank() ? (float) $this->getDiscountAmountSlipBank() : 0.0;
    }
}
