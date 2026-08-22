<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroupOptions implements OptionSourceInterface
{
    /**
     * Cached customer group options.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $options = null;

    /**
     * Initialize the customer group source.
     *
     * @param CollectionFactory $collectionFactory Customer group collection factory.
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Return customer groups as UI component options.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = $this->collectionFactory->create()->toOptionArray();

            foreach ($this->options as &$option) {
                $option['__disableTmpl'] = true;
            }
            unset($option);
        }

        return $this->options;
    }
}
