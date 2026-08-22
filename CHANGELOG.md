# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Customer-group-specific discounts for PIX and bank slip payments.
- Product extension attribute for the serialized customer group rules.
- Server-side validation for percentage and installment settings.
- Server-side validation for product percentage attributes.
- Unit tests and standalone Composer package metadata.

### Changed

- Centralized discount, price and installment calculations.
- Replaced direct customer session access with Magento HTTP context.
- Added safe store-scope configuration reads and CSP-compatible frontend initialization.
- Changed the safe module default to disabled until the store enables it explicitly.
- Simplified list rendering by removing an intermediate wrapper block and template.
- Improved Admin labels, product attributes and Brazilian Portuguese translations.
- Normalized configuration, Admin form and minicart identifiers for new installations.
- Removed upgrade aliases, migration-only patches and unused public facades.

### Fixed

- Corrected the bank slip attribute used by minicart calculations.
- Based the informational PIX subtotal on each quote item's effective unit price.
- Hidden explicit zero-discount rows instead of presenting a misleading special price.
- Prevented invalid percentages, zero divisions and undefined discount comparisons.

[Unreleased]: https://github.com/https-pelaquin/EnhancedInstallments/compare/main...HEAD
