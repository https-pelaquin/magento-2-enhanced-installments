/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

define([
    'jquery',
    'mage/template',
    'mage/translate',
    'Magento_Catalog/js/price-utils'
], function ($, mageTemplate, $t, priceUtils) {
    'use strict';

    $.widget('mage.priceBoxInstallment', {
        options: {
            onSaleTemplate: '<span class="price"><%- data.orText %> ' +
                '<strong><%- data.installment %>x <%- data.ofText %> <%- data.price %></strong> ' +
                '<%- data.interestFreeText %></span>',
            installmentNumber: 1,
            minimalInstallmentAmount: 0,
            productPrice: 0
        },

        _create: function () {
            this.options.productPrice = this._toNumber(this.options.productPrice, 0);
            this.options.installmentNumber = Math.max(
                1,
                Math.floor(this._toNumber(this.options.installmentNumber, 1))
            );
            this.options.minimalInstallmentAmount = Math.max(
                0,
                this._toNumber(this.options.minimalInstallmentAmount, 0)
            );
            this.template = mageTemplate(
                this.options.priceTemplate || this.options.onSaleTemplate
            );
            this.priceBox = $(this.options.priceBoxSelector).first();

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
            var installmentData = this._calculateInstallment(this._getFinalPrice(prices));

            installmentData.price = priceUtils.formatPrice(
                installmentData.price,
                this.options.priceConfig.priceFormat
            );
            this._renderTemplate(installmentData);
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

        _calculateInstallment: function (price) {
            var installmentNumber = this.options.installmentNumber;

            price = Math.max(0, this._toNumber(price, 0));
            if (price === 0) {
                return {
                    installmentNumber: 1,
                    price: 0
                };
            }

            if (this.options.minimalInstallmentAmount > 0) {
                installmentNumber = Math.min(
                    installmentNumber,
                    Math.max(
                        1,
                        Math.floor(price / this.options.minimalInstallmentAmount)
                    )
                );
            }

            return {
                installmentNumber: installmentNumber,
                price: this._round(price / installmentNumber)
            };
        },

        _renderTemplate: function (priceData) {
            this.element.html(this.template({
                data: {
                    installment: priceData.installmentNumber,
                    price: priceData.price,
                    orText: $t('or'),
                    ofText: $t('of'),
                    interestFreeText: $t('without interest')
                }
            }));
        },

        _round: function (number) {
            return Math.round(number * 100) / 100;
        },

        _toNumber: function (value, fallback) {
            var number = Number(value);

            return Number.isFinite(number) ? number : fallback;
        }
    });

    return $.mage.priceBoxInstallment;
});
