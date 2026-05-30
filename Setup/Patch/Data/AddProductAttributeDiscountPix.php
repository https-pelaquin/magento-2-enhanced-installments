<?php
/**
* Copyright (c) Bruno Pelaquin. All rights reserved.
* https://github.com/https-pelaquin
*/

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddProductAttributeDiscountPix implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {}

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'bp_pix_discount',
            [
                'is_visible_in_grid' => true,
                'is_html_allowed_on_front' => false,
                'user_defined' => true,
                'visible_on_front' => false,
                'visible' => true,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'label' => 'Desconto no Pix',
                'source' => null,
                'type' => 'varchar',
                'is_used_in_grid' => true,
                'required' => false,
                'input' => 'text',
                'is_filterable_in_grid' => false,
                'sort_order' => 8,
                'group' => 'Product Details',
                'default' => 5
            ]
        );
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
