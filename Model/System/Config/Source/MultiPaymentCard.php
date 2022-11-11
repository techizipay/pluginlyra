<?php
/**
 * Copyright © Lyra Network.
 * This file is part of Mi Cuenta Web plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Micuentaweb\Model\System\Config\Source;

class MultiPaymentCard implements \Magento\Framework\Option\ArrayInterface
{
    protected $multiCards = [
        'AMEX',
        'CB',
        'DINERS',
        'DISCOVER',
        'E-CARTEBLEUE',
        'JCB',
        'MASTERCARD',
        'PRV_BDP',
        'PRV_BDT',
        'PRV_OPT',
        'PRV_SOC',
        'VISA',
        'VISA_ELECTRON',
        'VPAY'
    ];

    public function toOptionArray()
    {
        $options = [
            [
                'value' => '',
                'label' => __('ALL'),
                'simple_label' => __('ALL')
            ]
        ];

        foreach (\Lyranetwork\Micuentaweb\Model\Api\Form\Api::getSupportedCardTypes() as $code => $name) {
            if (in_array($code, $this->multiCards)) {
                $options[] = [
                    'value' => $code,
                    'label' => $code . ' - ' . $name,
                    'simple_label' => $name
                ];
            }
        }

        return $options;
    }
}
