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

    $.widget('mage.priceBoxInstallment', {
        options: {
            onSaleTemplate: '<span class="price">ou <strong><%- data.installment %>x de <%- data.price %></strong> sem juros</span>',
            priceToCalculate: null,
            baseInstallmentNumber: null
        },

        _create: function() {
            this.options.priceToCalculate = this.options.productPrice;
            this.options.baseInstallmentNumber = this.options.installmentNumber;
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
            var installmentData;

            if (prices && prices.finalPrice && typeof prices.finalPrice.amount !== 'undefined') {
                finalPrice = prices.finalPrice.amount;
            } else if (prices && prices.prices && prices.prices.finalPrice &&
                typeof prices.prices.finalPrice.amount !== 'undefined') {
                finalPrice = this.options.productPrice + prices.prices.finalPrice.amount;
            }

            installmentData = this.calculateInstallment(finalPrice);
            installmentData.price = this.getFormattedPrice(installmentData.price);
            this._renderTemplate(installmentData);
        },

        calculateInstallment: function(price) {
            var installmentNumber = this.options.baseInstallmentNumber;
            var installmentPrice = price / installmentNumber;

            installmentPrice = this.roundUp(installmentPrice, 2);

            if (installmentPrice < this.options.minimalInstallmentAmount) {
                while (installmentPrice < this.options.minimalInstallmentAmount && installmentNumber > 1) {
                    installmentNumber -= 1;
                    installmentPrice = price / installmentNumber;
                    installmentPrice = this.roundUp(installmentPrice, 2);
                }
            }

            return {
                installmentNumber: installmentNumber,
                price: installmentPrice,
                finalPrice: price
            };
        },

        getFormattedPrice: function(price) {
            return utils.formatPrice(price, this.options.priceConfig.priceFormat);
        },

        _renderTemplate: function(priceData) {
            var priceTemplate;

            if (this.options.priceTemplate) {
                priceTemplate = mageTemplate(this.options.priceTemplate);
            } else {
                priceTemplate = mageTemplate(this.options.onSaleTemplate);
            }

            $(this.element).html(priceTemplate({
                data: {
                    finalPrice: priceData.finalPrice,
                    discount: this.options.discount,
                    installment: priceData.installmentNumber,
                    price: priceData.price
                }
            }));
        },

        roundUp: function(num, precision) {
            precision = Math.pow(10, precision);
            return Math.round(num * precision) / precision;
        }
    });

    return $.mage.priceBoxInstallment;
});
