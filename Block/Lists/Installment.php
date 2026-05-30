<?php
/**
 * Copyright (c) Bruno Pelaquin. All rights reserved.
 * https://github.com/https-pelaquin
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Block\Lists;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class Installment extends Template
{
    private const BP_INSTALLMENT_NUMBER_PATH = 'bp_installment_billet_price/general/bp_installment_number';
    private const BP_MINIMAL_INSTALLMENT_AMOUNT_PATH = 'bp_installment_billet_price/general/bp_installment_minimal_amount';

    public function __construct(
        Context $context,
        private readonly FormatInterface $localeFormat,
        private readonly EncoderInterface $jsonEncoder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly PricingHelper $pricingHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getInstallmentNumber(): int
    {
        return (int) $this->scopeConfig->getValue(self::BP_INSTALLMENT_NUMBER_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getPriceFormat(): array
    {
        return $this->localeFormat->getPriceFormat();
    }

    public function getJsonConfig($product): string
    {
        $config = [
            'productId' => $product->getId(),
            'priceFormat' => $this->localeFormat->getPriceFormat()
        ];

        return $this->jsonEncoder->encode($config);
    }

    public function getMinimalInstallmentAmount(): int
    {
        $minimalAmount = (int) $this->scopeConfig->getValue(self::BP_MINIMAL_INSTALLMENT_AMOUNT_PATH, ScopeInterface::SCOPE_STORE);

        if ($minimalAmount <= 0) {
            return 0;
        }

        return $minimalAmount;
    }

    public function getFinalPrice(): float
    {
        $product = $this->getProduct();

        return match ($product->getTypeId()) {
            'grouped' => $this->priceGrouped($product),
            'simple' => $this->priceSimple($product),
            default => (float) $product->getFinalPrice()
        };
    }

    public function getFormattedFinalPrice(): string
    {
        return $this->pricingHelper->currency($this->getFinalPrice(), true, false);
    }

    public function getDiscountPercent(): int
    {
        $product = $this->getProduct();
        $regularPrice = (float) $product->getPriceInfo()
            ->getPrice('regular_price')
            ->getAmount()
            ->getValue();
        $finalPrice = $this->getFinalPrice();

        if ($regularPrice <= 0 || $finalPrice < 0) {
            return 0;
        }

        return (int) round(max(0, (($regularPrice - $finalPrice) / $regularPrice) * 100));
    }

    private function priceSimple($product): float
    {
        return $product->getTierPrices() ? (float) min($product->getFinalPrice(), $product->getTierPrice(1)) : (float) $product->getFinalPrice();
    }

    private function priceGrouped($product): float
    {
        $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
        $finalPrice = 0.0;

        foreach ($associatedProducts as $_item) {
            $finalPrice += $_item->getFinalPrice() * $_item->getQty();
        }

        return $finalPrice;
    }
}
