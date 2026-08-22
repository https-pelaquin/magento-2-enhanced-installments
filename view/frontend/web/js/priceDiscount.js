/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

define([
    'jquery',
    'mage/template',
    'Magento_Catalog/js/price-utils'
], function ($, mageTemplate, priceUtils) {
    'use strict';

    $.widget('mage.bpPriceDiscount', {
        options: {
            discount: 0,
            productPrice: 0,
            priceTemplate: '<span class="price"><strong><%- data.price %></strong></span>'
        },

        _create: function () {
            this.priceBox = $(this.options.priceBoxSelector).first();
            this.options.productPrice = this._toNumber(this.options.productPrice, 0);
            this.options.discount = Math.min(
                100,
                Math.max(0, this._toNumber(this.options.discount, 0))
            );
            this.template = mageTemplate(this.options.priceTemplate);

            if (this.priceBox.length) {
                this._on(this.priceBox, {
                    priceUpdated: this.updatePrice
                });
            }

            this.updatePrice(null, {
                finalPrice: {
                    amount: this.options.productPrice
                }
            });
        },

        updatePrice: function (event, prices) {
            var finalPrice = this._getFinalPrice(prices);
            var discountedPrice = finalPrice * (1 - (this.options.discount / 100));

            this._renderTemplate(
                priceUtils.formatPrice(
                    Math.max(0, Math.round(discountedPrice * 100) / 100),
                    this.options.priceConfig.priceFormat
                )
            );
        },

        _getFinalPrice: function (prices) {
            if (prices && prices.finalPrice && typeof prices.finalPrice.amount !== 'undefined') {
                return this._toNumber(prices.finalPrice.amount, this.options.productPrice);
            }

            if (prices && prices.prices && prices.prices.finalPrice &&
                typeof prices.prices.finalPrice.amount !== 'undefined'
            ) {
                return this.options.productPrice +
                    this._toNumber(prices.prices.finalPrice.amount, 0);
            }

            return this.options.productPrice;
        },

        _renderTemplate: function (price) {
            this.element.html(this.template({
                data: {
                    price: price
                }
            }));
        },

        _toNumber: function (value, fallback) {
            var number = Number(value);

            return Number.isFinite(number) ? number : fallback;
        }
    });

    return $.mage.bpPriceDiscount;
});
