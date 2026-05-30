/**
 * Copyright (c) Bruno Pelaquin. All rights reserved.
 * https://github.com/https-pelaquin
 */

define([
    'jquery',
    'mage/template',
    'Magento_Catalog/js/price-utils',
], function($, mageTemplate, utils) {
    'use strict';

    $.widget('mage.bpPriceDiscount', {
        options: {},

        _create: function() {
            this.priceBox = $(this.options.priceBoxSelector).first();

            if (this.priceBox.length) {
                this.priceBox.on('priceUpdated', this.updatePrice.bind(this));
            }

            this.updatePrice(null, {
                finalPrice: {
                    amount: this.options.productPrice
                }
            });
        },

        updatePrice: function(event, prices) {
            var finalPrice = this.options.productPrice;
            var discountedPrice;

            if (prices && prices.finalPrice && typeof prices.finalPrice.amount !== 'undefined') {
                finalPrice = prices.finalPrice.amount;
            } else if (prices && prices.prices && prices.prices.finalPrice &&
                typeof prices.prices.finalPrice.amount !== 'undefined') {
                finalPrice = this.options.productPrice + prices.prices.finalPrice.amount;
            }

            discountedPrice = this.calculatePrice(finalPrice);
            discountedPrice = this.getFormattedPrice(discountedPrice);
            this._renderTemplate(discountedPrice);
        },

        calculatePrice: function(price) {
            var discount = (price * this.options.discount) / 100;
            var finalPrice = price - discount;

            return this.roundUp(finalPrice, 2);
        },

        getFormattedPrice: function(price) {
            return utils.formatPrice(price, this.options.priceConfig.priceFormat);
        },

        _renderTemplate: function(price) {
            var priceTemplate = mageTemplate(this.options.priceTemplate);

            $(this.element).html(priceTemplate({
                data: {
                    price: price
                }
            }));
        },

        roundUp: function(num, precision) {
            precision = Math.pow(10, precision);
            return Math.round(num * precision) / precision;
        }
    });

    return $.mage.bpPriceDiscount;
});
