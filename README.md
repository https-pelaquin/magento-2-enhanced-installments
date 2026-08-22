# Enhanced Installments for Magento 2

Display PIX and bank slip discounts alongside interest-free credit card installment information throughout the Magento storefront.

> [!IMPORTANT]
> This module was designed for Brazilian e-commerce stores, where PIX, boleto bancário (bank slip), and credit cards with installments are common payment options.
>
> For stores in other countries, adapt the PIX price and labels to represent the local **cash/upfront price** (pay in full). Bank slip terminology and payment mappings may also need localization before production use.

## What the module does

- Displays PIX and bank slip discount prices on product pages.
- Displays the best payment discount and installment information in category and widget product lists.
- Adds PIX subtotal and credit card installment messages to Magento minicart customer data.
- Supports default discounts by store view.
- Supports product-specific PIX and bank slip percentages.
- Supports PIX or bank slip discounts by customer group on each product.
- Exposes the serialized customer group rules through a product extension attribute.
- Integrates optionally with MageWorx Search Suite Autocomplete when that module is installed.
- Uses Magento HTTP context for customer group resolution, preserving full-page cache compatibility.
- Provides Brazilian Portuguese translations in `i18n/pt_BR.csv`.
- Starts disabled by default so each store can review its payment rules before publishing the messages.

The module is a presentation and pricing-information layer. It does **not** install PIX, bank slip, or credit card payment methods, and it does not change quote or order totals. Your payment integration must enforce the advertised price during checkout.

This package is designed for new installations. It does not migrate configuration, product attributes, or patch history from other modules.

## Discount resolution

Discounts are resolved independently for PIX and bank slip in this order:

1. Customer group rule configured on the product.
2. Percentage configured directly on the product.
3. Default percentage configured for the current store view.

A configured value of `0` is an explicit override. It disables that discount instead of falling back to the next level.

## Requirements

| Dependency | Supported version |
| --- | --- |
| Magento Open Source | 2.4.9 |
| PHP | 8.3, 8.4, or 8.5 |

The Composer constraints document the Magento component versions used by Magento 2.4.9.

## Installation

### Composer

When the package is available in a Composer repository configured by your project:

```bash
composer require pelaquin/module-enhanced-installments
bin/magento module:enable Pelaquin_EnhancedInstallments
bin/magento setup:upgrade
bin/magento cache:flush
```

Run production compilation and static content deployment according to your deployment pipeline:

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

### Manual installation

Copy the module to:

```text
app/code/Pelaquin/EnhancedInstallments
```

Then run:

```bash
bin/magento module:enable Pelaquin_EnhancedInstallments
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

Go to:

```text
Stores > Configuration > Pelaquin > Enhanced Installments
```

The settings support Default, Website, and Store View scopes.

| Setting | Default | Purpose |
| --- | ---: | --- |
| Enable | No | Enables storefront discount and installment information. |
| Bank Slip Discount (%) | 5 | Default bank slip percentage, from 0 to 100. |
| PIX Discount (%) | 5 | Default PIX percentage, from 0 to 100. |
| Maximum Number of Installments | 10 | Maximum interest-free credit card installments. |
| Minimum Installment Amount | 0 | Minimum amount per installment in the current store currency; use 0 to disable the limit. |

### Product overrides

Open a product in the Admin and use these fields under **Product Details**:

- **PIX Discount (%)**
- **Bank Slip Discount (%)**

To configure customer-specific rules, open **Payment Discount per Customer Group** and add:

- customer group;
- payment method: PIX or Bank Slip;
- discount percentage from 0 to 100.

The customer group rule is global at product level. Default configuration remains store-scoped.
Product percentages and customer-group percentages are validated from 0 through 100 on the server before saving.

## Storefront integration

The module publishes these identifiers for theme and integration use:

| Contract | Identifier |
| --- | --- |
| PIX product attribute | `bp_pix_discount` |
| Bank slip product attribute | `bp_bank_slip_discount` |
| Customer group rules attribute | `bp_slip_discount_per_group` |
| Product extension attribute | `bp_slip_discount_per_group` |
| PIX minicart formatted subtotal | `bp_pix_minicart_subtotal_formatted` |
| PIX minicart numeric subtotal | `bp_pix_minicart_subtotal_value` |
| PIX minicart label | `bp_pix_text` |
| Installment minicart label | `bp_installments` |

Frontend widgets are initialized through Magento-compatible `text/x-magento-init` configuration and use the store's native price format.
Discount rows with an explicit value of `0` are not rendered, while installment information remains available.

### Optional MageWorx integration

If `MageWorx_SearchSuiteAutocomplete` is installed, the best payment discount is also appended to its product price suggestions. MageWorx is optional and is not a Composer requirement.

## International adaptation

Outside Brazil, PIX and boleto may not exist. Before using the module:

1. Rename PIX storefront and Admin labels to the local **cash price**, **upfront price**, or **pay-in-full price**.
2. Map that displayed price to a real payment method that enforces the discount at checkout.
3. Remove or rename bank slip output when boleto is not applicable.
4. Review installment wording, interest rules, taxes, and consumer-credit regulations for the target country.

Currency formatting already follows the current Magento store configuration; the payment meaning and checkout enforcement remain the merchant's responsibility.

## Development

Run the module checks from the Magento project:

```bash
php -l app/code/Pelaquin/EnhancedInstallments/path/to/file.php
vendor/bin/phpcs app/code/Pelaquin/EnhancedInstallments
vendor/bin/phpunit -c app/code/Pelaquin/EnhancedInstallments/phpunit.xml.dist
bin/magento setup:di:compile
```

Customer group JSON uses the module-owned root name:

```json
{
  "bp_payment_discount_rules": [
    {
      "customer_group": "1",
      "discount_type": "pix",
      "discount": 10,
      "sort_order": 0
    }
  ]
}
```

## Contributing

Bug reports and focused pull requests are welcome through [GitHub Issues](https://github.com/https-pelaquin/EnhancedInstallments/issues).

Please include:

- Magento and PHP versions;
- the affected store scope and customer group;
- steps to reproduce;
- expected and actual behavior.

## License

Released under the [MIT License](LICENSE).
