<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Ui\DataProvider\Product\Form;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Pelaquin\EnhancedInstallments\Model\Config\Source\CustomerGroupOptions;
use Pelaquin\EnhancedInstallments\Model\Discount\DiscountPerGroup as DiscountPerGroupSerializer;
use Pelaquin\EnhancedInstallments\Model\ProductAttribute;

class DiscountPerGroup extends AbstractModifier
{
    private const FIELD_IS_DELETE = 'is_delete';
    private const FIELD_SORT_ORDER_NAME = 'sort_order';

    /**
     * Product currently loaded in the Admin form.
     *
     * @var ProductInterface
     */
    private ProductInterface $product;

    public function __construct(
        private readonly LocatorInterface $locator,
        private readonly CustomerGroupOptions $customerGroups,
        private readonly DiscountPerGroupSerializer $discountPerGroup
    ) {
        $this->product = $this->locator->getProduct();
    }

    /**
     * Load saved discount rows into the product form data.
     *
     * @param array $data Product form data.
     */
    public function modifyData(array $data): array
    {
        if (!$this->product->getSku() && !$this->product->getId()) {
            return $data;
        }

        $value = $this->product->getCustomAttribute(ProductAttribute::DISCOUNT_PER_GROUP)?->getValue();
        if (!is_string($value) || $value === '') {
            return $data;
        }

        $rows = $this->discountPerGroup->unserialize($value);
        if ($rows === []) {
            return $data;
        }

        $productId = (int) $this->product->getId();
        $data[$productId]['product'][DiscountPerGroupSerializer::FIELDSET_NAME]
            [DiscountPerGroupSerializer::ROWS_NAME] = $rows;

        return $data;
    }

    /**
     * Add the customer group discount fieldset to the product form.
     *
     * @param array $meta Product form metadata.
     */
    public function modifyMeta(array $meta): array
    {
        return array_replace_recursive(
            $meta,
            [
                DiscountPerGroupSerializer::FIELDSET_NAME => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Payment Discount per Customer Group'),
                                'componentType' => Fieldset::NAME,
                                'dataScope' => 'data.product.' . DiscountPerGroupSerializer::FIELDSET_NAME,
                                'collapsible' => true,
                                'sortOrder' => 5,
                            ],
                        ],
                    ],
                    'children' => [
                        DiscountPerGroupSerializer::ROWS_NAME => $this->getChildrenField(),
                    ],
                ],
            ]
        );
    }

    /**
     * Build the DynamicRows field configuration.
     */
    protected function getChildrenField(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'addButtonLabel' => __('Add'),
                        'componentType' => DynamicRows::NAME,
                        'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows',
                        'additionalClasses' => 'admin__field-wide',
                        'deleteProperty' => self::FIELD_IS_DELETE,
                        'deleteValue' => '1',
                        'renderDefaultRecord' => false,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Container::NAME,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'positionProvider' => self::FIELD_SORT_ORDER_NAME,
                                'isTemplate' => true,
                                'is_collection' => true,
                            ],
                        ],
                    ],
                    'children' => [
                        'customer_group' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => 'select',
                                        'options' => $this->customerGroups->toOptionArray(),
                                        'componentType' => Field::NAME,
                                        'dataType' => Text::NAME,
                                        'dataScope' => 'customer_group',
                                        'label' => __('Customer Group'),
                                        'resize' => true,
                                        'validation' => [
                                            'required-entry' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'type' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => 'select',
                                        'options' => [
                                            ['value' => 'pix', 'label' => __('PIX')],
                                            ['value' => 'boleto', 'label' => __('Bank Slip')],
                                        ],
                                        'componentType' => Field::NAME,
                                        'dataType' => Text::NAME,
                                        'dataScope' => 'discount_type',
                                        'label' => __('Payment Method'),
                                        'validation' => [
                                            'required-entry' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'discount' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Field::NAME,
                                        'formElement' => Input::NAME,
                                        'dataType' => Number::NAME,
                                        'label' => __('Discount (%)'),
                                        'enableLabel' => true,
                                        'dataScope' => 'discount',
                                        'resizeDefaultWidth' => '200',
                                        'validation' => [
                                            'required-entry' => true,
                                            'validate-number' => true,
                                            'validate-number-range' => '0-100',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'actionDelete' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => 'actionDelete',
                                        'dataType' => Text::NAME,
                                        'label' => __('Delete'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
