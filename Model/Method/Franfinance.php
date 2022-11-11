<?php
/**
 * Copyright © Lyra Network.
 * This file is part of Mi Cuenta Web plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Micuentaweb\Model\Method;

class Franfinance extends Micuentaweb
{
    protected $_code = \Lyranetwork\Micuentaweb\Helper\Data::METHOD_FRANFINANCE;
    protected $_formBlockType = \Lyranetwork\Micuentaweb\Block\Payment\Form\Franfinance::class;

    protected $_canUseInternal = false;

    protected $needsCartData = true;
    protected $needsShippingMethodData = true;

    protected $currencies = ['EUR'];
    protected $countries = ['FR', 'GP', 'MQ', 'GF', 'RE', 'YT'];

    protected function setExtraFields($order)
    {
        // Override with Franfinance specific params.
        $this->micuentawebRequest->set('validation_mode', '0');
        $this->micuentawebRequest->set('capture_delay', '0');

        $info = $this->getInfoInstance();

        // Override with selected Franfinance payment card.
        $option = @unserialize($info->getAdditionalInformation(\Lyranetwork\Micuentaweb\Helper\Payment::FRANFINANCE_OPTION));
        $this->micuentawebRequest->set('payment_cards', $option['payment_means']);

        $fees = $option['fees'] ? 'Y' : 'N';
        $code = substr($option['payment_means'], -2);
        $this->micuentawebRequest->set('acquirer_transient_data', '{"FRANFINANCE":{"FEES_' . $code . '":"' . $fees . '"}}');
    }

    /**
     * Assign data to info model instance.
     *
     * @param \Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();

        $micuentawebData = $this->extractPaymentData($data);

        // Load option information.
        $option = $this->getOption($micuentawebData->getData('micuentaweb_franfinance_option'));
        $info->setAdditionalInformation(\Lyranetwork\Micuentaweb\Helper\Payment::FRANFINANCE_OPTION, serialize($option));

        return $this;
    }

    /**
     * Return true if the method can be used at this time.
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (! parent::isAvailable($quote)) {
            return false;
        }

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        if ($amount) {
            $options = $this->getAvailableOptions($amount);
            return ! empty($options);
        }

        return true;
    }


    /**
     * Return available payment options to be displayed on payment method list page.
     *
     * @param double $amount
     *            a given amount
     * @return array[string][array] An array "$code => $option" of availables options
     */
    public function getAvailableOptions($amount = null)
    {
        $configOptions = $this->dataHelper->unserialize($this->getConfigData('franfinance_payment_options'));

        $options = [];
        if (is_array($configOptions) && ! empty($configOptions)) {
            foreach ($configOptions as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                if ((! $amount || ! $value['amount_min'] || $amount > $value['amount_min']) &&
                    (! $amount || ! $value['amount_max'] || $amount < $value['amount_max'])) {
                    // Option will be available.
                    $options[$code] = $value;
                }
            }
        }

        return $options;
    }

    private function getOption($code)
    {
        $info = $this->getInfoInstance();
        if ($info instanceof \Mage\Sales\Model\Order\Payment) {
            $amount = $info->getOrder()->getBaseGrandTotal();
        } else {
            $amount = $info->getQuote()->getBaseGrandTotal();
        }

        $options = $this->getAvailableOptions($amount);

        if ($code && isset($options[$code])) {
            return $options[$code];
        }

        return false;
    }

    public function canUseForCountry($country)
    {
        return in_array($country, $this->countries);
    }
}
