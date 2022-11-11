<?php
/**
 * Copyright © Lyra Network.
 * This file is part of Mi Cuenta Web plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Micuentaweb\Block\Adminhtml\System\Config\Form\Field;

/**
 * Custom renderer for the Shipping options field.
 */
class ShipOptions extends \Lyranetwork\Micuentaweb\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
    /**
     * @var \Lyranetwork\Micuentaweb\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var bool
     */
    protected $staticTable = true;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Lyranetwork\Micuentaweb\Helper\Checkout $checkoutHelper
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Lyranetwork\Micuentaweb\Helper\Checkout $checkoutHelper,
        \Magento\Shipping\Model\Config $shippingConfig,
        array $data = []
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->shippingConfig = $shippingConfig;

        parent::__construct($context, $data);
    }

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'title',
            [
                'label' => __('Method title'),
                'style' => 'width: 210px;',
                'renderer' => $this->getLabelRenderer('_title')
            ]
        );

        $this->addColumn(
            'type',
            [
                'label' => __('Type'),
                'style' => 'width: 130px;',
                'class' => 'micuentaweb_list_type',
                'renderer' => $this->getListRenderer(
                    '_type',
                    [
                        'PACKAGE_DELIVERY_COMPANY' => 'Delivery company',
                        'RECLAIM_IN_SHOP' => 'Reclaim in shop',
                        'RELAY_POINT' => 'Relay point',
                        'RECLAIM_IN_STATION' => 'Reclaim in station'
                    ]
                )
            ]
        );

        $this->addColumn(
            'speed',
            [
                'label' => __('Rapidity'),
                'style' => 'width: 75px;',
                'class' => 'micuentaweb_list_speed',
                'renderer' => $this->getListRenderer(
                    '_speed',
                    [
                        'STANDARD' => 'Standard',
                        'EXPRESS' => 'Express',
                        'PRIORITY' => 'Priority'
                    ]
                )
            ]
        );

        $this->addColumn(
            'delay',
            [
                'label' => __('Delay'),
                'style' => 'width: 75px;',
                'class' => 'micuentaweb_list_delay',
                'renderer' => $this->getListRenderer(
                    '_delay',
                    [
                        'INFERIOR_EQUALS' => __('<= 1 hour'),
                        'SUPERIOR' => __('> 1 hour'),
                        'IMMEDIATE' => __('Immediate'),
                        'ALWAYS' => __('24/7')
                    ]
                )
            ]
        );

        parent::_prepareToRender();
    }

    /**
     * Obtain existing data from form element.
     * Each row will be instance of \Magento\Framework\DataObject.
     *
     * @return array
     */
    public function getArrayRows()
    {
        $value = [];

        $allMethods = $this->getAllShippingMethods();

        $savedMethods = $this->getElement()->getValue();
        if ($savedMethods && is_array($savedMethods) && ! empty($savedMethods)) {
            foreach ($savedMethods as $id => $method) {
                if (key_exists($method['code'], $allMethods)) {
                    // Update magento method title.
                    $method['title'] = $allMethods[$method['code']];
                    $value[$id] = $method;

                    unset($allMethods[$method['code']]);
                }
            }
        }

        // Add not saved yet methods.
        if ($allMethods && is_array($allMethods) && ! empty($allMethods)) {
            foreach ($allMethods as $code => $name) {
                $value[uniqid('_' . $code . '_')] = [
                    'code' => $code,
                    'title' => $name,
                    'type' => 'PACKAGE_DELIVERY_COMPANY',
                    'speed' => 'STANDARD',
                    'mark' => true
                ];
            }
        }

        $this->getElement()->setValue($value);
        return parent::getArrayRows();
    }

    private function getAllShippingMethods()
    {
        $allMethods = [];

        $store = null;
        if ($this->getElement()->getScope() === \Magento\Config\Block\System\Config\Form::SCOPE_STORES) {
            $store = $this->getElement()->getScopeId();
        }

        // List of all configured carriers.
        $carriers = $this->shippingConfig->getAllCarriers($store);

        foreach ($carriers as $carrierCode => $carrierModel) {
            $carrierModel->setStore($store);

            // Filter carriers to get active ones on current scope.
            if (! $carrierModel->isActive()) {
                continue;
            }

            try {
                $carrierMethods = $carrierModel->getAllowedMethods();
                if (! $carrierMethods) {
                    continue;
                }

                $carrierTitle = $carrierModel->getConfigData('title');
                foreach ($carrierMethods as $methodCode => $methodTitle) {
                    $code = $carrierCode . '_' . $methodCode;

                    $title = '[' . $carrierTitle . '] ';
                    if (is_string($methodTitle) && ! empty($methodTitle)) {
                        $title .= $methodTitle;
                    } else { // Non standard method title.
                        $title .= $methodCode;
                    }

                    $allMethods[$code] = $title;
                }
            } catch (\Exception $e) {
                // Just this shipping method.
                continue;
            }
        }

        return $allMethods;
    }

    /**
     * Render element JavaScript code.
     *
     * @return string
     */
    protected function renderScript()
    {
        $script = parent::renderScript();

        $script .= "\n" . '
            <script>
                 require([
                    "prototype"
                ], function () {
                    // Enable delay select for rows with speed equals PRIORITY.
                    $$("select.micuentaweb_list_delay").each(function(elt) {
                        var speedName = elt.name.replace("[delay]", "[speed]");

                        // Select by name returns one element.
                        var speedElt = $$("select[name=\"" + speedName + "\"]")[0];

                        if (speedElt.value === "PRIORITY") {
                            elt.enable();
                        } else {
                            elt.disable();
                        }
                    });

                    $$("select.micuentaweb_list_speed").invoke("observe", "change", function() {
                        var delayName = this.name.replace("[speed]", "[delay]");

                        // Select by name returns one element.
                        var elt = $$("select[name=\"" + delayName + "\"]")[0];

                        if (this.value === "PRIORITY") {
                            elt.enable();
                        } else {
                            elt.disable();
                        }
                    });
                });
            </script>';

        return $script;
    }
}
