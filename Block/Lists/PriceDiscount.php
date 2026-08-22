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
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Pelaquin\EnhancedInstallments\Model\DiscountResolver;
use Pelaquin\EnhancedInstallments\Model\PaymentMethod;
use Pelaquin\EnhancedInstallments\Model\PriceCalculator;

class PriceDiscount extends Template
{
    /**
     * @var array{type: string, percentage: float}|null
     */
    private ?array $bestDiscount = null;

    public function __construct(
        Context $context,
        private readonly FormatInterface $localeFormat,
        private readonly EncoderInterface $jsonEncoder,
        private readonly DiscountResolver $discountResolver,
        private readonly PriceCalculator $priceCalculator,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getFinalPrice(): float
    {
        return $this->priceCalculator->getFinalPrice($this->getProduct());
    }

    public function getText(): string
    {
        return $this->getBestDiscount()['type'] === 'bankSlipDiscount'
            ? 'Special price with %1% discount'
            : 'via PIX (%1% discount)';
    }

    public function getDiscountPercent(): float
    {
        return $this->getBestDiscount()['percentage'];
    }

    public function hasDiscount(): bool
    {
        return $this->getDiscountPercent() > 0;
    }

    public function getWidgetConfig(Product $product, string $elementId): string
    {
        return $this->jsonEncoder->encode([
            '#' . $elementId => [
                'bpPriceDiscount' => [
                    'priceConfig' => [
                        'productId' => (int) $product->getId(),
                        'priceFormat' => $this->localeFormat->getPriceFormat(),
                    ],
                    'priceBoxSelector' => sprintf(
                        '[data-price-box="product-id-%d"]',
                        (int) $product->getId()
                    ),
                    'productPrice' => $this->getFinalPrice(),
                    'discount' => $this->getDiscountPercent(),
                ],
            ],
        ]);
    }

    private function getPaymentDiscount(string $paymentMethod): float
    {
        return $this->discountResolver->getDiscount($this->getProduct(), $paymentMethod);
    }

    /**
     * @return array{type: string, percentage: float}
     */
    private function getBestDiscount(): array
    {
        if ($this->bestDiscount !== null) {
            return $this->bestDiscount;
        }

        $pixDiscount = $this->getPaymentDiscount(PaymentMethod::PIX);
        $bankSlipDiscount = $this->getPaymentDiscount(PaymentMethod::BANK_SLIP);

        $this->bestDiscount = $bankSlipDiscount > $pixDiscount
            ? ['type' => 'bankSlipDiscount', 'percentage' => $bankSlipDiscount]
            : ['type' => 'pixDiscount', 'percentage' => $pixDiscount];

        return $this->bestDiscount;
    }
}
