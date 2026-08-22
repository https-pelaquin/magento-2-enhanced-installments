<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Pelaquin\EnhancedInstallments\Model\ProductAttribute;
use Pelaquin\EnhancedInstallments\Model\Product\Attribute\Backend\Percentage as PercentageBackend;

class AddProductAttributeDiscountBoleto implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $eavSetup->addAttribute(
                Product::ENTITY,
                ProductAttribute::BANK_SLIP_DISCOUNT,
                [
                    'type' => 'varchar',
                    'backend' => PercentageBackend::class,
                    'label' => 'Bank Slip Discount (%)',
                    'input' => 'text',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => null,
                    'group' => 'Product Details',
                    'sort_order' => 90,
                    'frontend_class' => 'validate-number validate-number-range number-range-0-100',
                    'used_in_product_listing' => true,
                    'visible_on_front' => false,
                    'is_html_allowed_on_front' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => false,
                    'is_used_for_promo_rules' => false,
                ]
            );
        } finally {
            $connection->endSetup();
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
