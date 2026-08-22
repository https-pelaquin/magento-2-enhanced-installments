<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface as UrlEncoderInterface;
use Pelaquin\EnhancedInstallments\Model\DiscountResolver;
use Pelaquin\EnhancedInstallments\Model\PriceCalculator;

class PriceDiscount extends View
{
    public function __construct(
        Context $context,
        UrlEncoderInterface $urlEncoder,
        private readonly EncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        private readonly FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        private readonly DiscountResolver $discountResolver,
        private readonly PriceCalculator $priceCalculator,
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
            $priceCurrency,
            $data
        );
    }

    public function getFinalPrice(): float
    {
        return $this->priceCalculator->getFinalPrice($this->getProduct());
    }

    public function getPaymentDiscount(string $paymentMethod): float
    {
        return $this->discountResolver->getDiscount($this->getProduct(), $paymentMethod);
    }

    public function getWidgetConfig(float $pixDiscount, float $bankSlipDiscount): string
    {
        $product = $this->getProduct();
        $priceConfig = [
            'productId' => (int) $product->getId(),
            'priceFormat' => $this->localeFormat->getPriceFormat(),
        ];
        $baseConfig = [
            'priceConfig' => $priceConfig,
            'priceBoxSelector' => sprintf(
                '[data-price-box="product-id-%d"]',
                (int) $product->getId()
            ),
            'productPrice' => $this->getFinalPrice(),
        ];

        $widgetConfig = [];
        foreach ([
            '#bp-pix-price' => $pixDiscount,
            '#bp-bank-slip-discount' => $bankSlipDiscount,
        ] as $selector => $discount) {
            if ($discount > 0) {
                $widgetConfig[$selector]['bpPriceDiscount'] = $baseConfig + [
                    'discount' => $discount,
                ];
            }
        }

        return $this->jsonEncoder->encode($widgetConfig);
    }
}
