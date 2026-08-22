<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Block\Lists;

use Magento\Catalog\Model\Product;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Pelaquin\EnhancedInstallments\Model\Config;
use Pelaquin\EnhancedInstallments\Model\PriceCalculator;

class Installment extends Template
{
    private ?float $finalPrice = null;

    public function __construct(
        Context $context,
        private readonly FormatInterface $localeFormat,
        private readonly EncoderInterface $jsonEncoder,
        private readonly Config $config,
        private readonly PricingHelper $pricingHelper,
        private readonly PriceCalculator $priceCalculator,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getInstallmentNumber(): int
    {
        return $this->config->getInstallmentNumber();
    }

    public function getMinimalInstallmentAmount(): float
    {
        return $this->config->getMinimumInstallmentAmount();
    }

    public function getFinalPrice(): float
    {
        return $this->finalPrice ??= $this->priceCalculator->getFinalPrice($this->getProduct());
    }

    public function getFormattedFinalPrice(): string
    {
        return $this->pricingHelper->currency($this->getFinalPrice(), true, false);
    }

    public function getDiscountPercent(): int
    {
        return (int) round(
            $this->priceCalculator->getCatalogDiscount($this->getProduct(), $this->getFinalPrice())
        );
    }

    public function getWidgetConfig(Product $product, string $elementId): string
    {
        return $this->jsonEncoder->encode([
            '#' . $elementId => [
                'bpPriceBoxInstallment' => [
                    'priceConfig' => [
                        'productId' => (int) $product->getId(),
                        'priceFormat' => $this->localeFormat->getPriceFormat(),
                    ],
                    'priceBoxSelector' => sprintf(
                        '[data-price-box="product-id-%d"]',
                        (int) $product->getId()
                    ),
                    'installmentNumber' => $this->getInstallmentNumber(),
                    'minimalInstallmentAmount' => $this->getMinimalInstallmentAmount(),
                    'productPrice' => $this->getFinalPrice(),
                ],
            ],
        ]);
    }
}
