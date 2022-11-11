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

class Other extends Micuentaweb
{
    protected $_code = \Lyranetwork\Micuentaweb\Helper\Data::METHOD_OTHER;
    protected $_formBlockType = \Lyranetwork\Micuentaweb\Block\Payment\Form\Other::class;

    protected $_canUseInternal = false;

    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    protected function setExtraFields($order)
    {
        $info = $this->getInfoInstance();
        $this->micuentawebRequest->set('payment_cards', $info->getCcType());

        $option = @unserialize($info->getAdditionalInformation(\Lyranetwork\Micuentaweb\Helper\Payment::OTHER_OPTION));

        // Check if capture_delay and validation_mode are overriden.
        if (is_numeric($option['capture_delay'])) {
            $this->micuentawebRequest->set('capture_delay', $option['capture_delay']);
        }

        if ($option['validation_mode'] !== '-1') {
            $this->micuentawebRequest->set('validation_mode', $option['validation_mode']);
        }

        // Add cart data.
        if ($option['cart_data'] === '1') {
            $this->checkoutHelper->setCartData($order, $this->micuentawebRequest, true);
        }
    }

    /**
     * Assign data to info model instance.
     *
     * @param array|\Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();
        $micuentawebData = $this->extractPaymentData($data);

        // Load option informations.
        $option = $this->_getMeans($micuentawebData->getData('micuentaweb_other_option'));

        $info->setCcType($option['means'])
            ->setAdditionalInformation(\Lyranetwork\Micuentaweb\Helper\Payment::OTHER_OPTION, serialize($option));
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

        if ($quote) {
            $means = $this->getAvailableMeans($quote);
            return ! empty($means);
        }

        return true;
    }

    /**
     * Return available payment means to be displayed on payment method list page.
     *
     * @param  double $amount a given amount
     * @return array[string][array] An array "$code => $option" of availables means
     */
    public function getAvailableMeans($quote = null)
    {
        $configMeans = $this->dataHelper->unserialize($this->getConfigData('other_payment_means'));

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        $country = $quote ? $quote->getBillingAddress()->getCountryId() : null;

        $means = [];
        if (is_array($configMeans) && ! empty($configMeans)) {
            foreach ($configMeans as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                if ($country && isset($value['countries']) && ! empty($value['countries'])
                    && ! in_array($country, $value['countries'])) {
                    continue;
                }

                if ((! $amount || ! $value['minimum'] || $amount > $value['minimum'])
                    && (! $amount || ! $value['maximum'] || $amount < $value['maximum'])) {
                    // Means will be available.
                    $means[$code] = $value;
                }
            }
        }

        return $means;
    }

    private function _getMeans($code)
    {
        $options = $this->getAvailableMeans();

        if ($code && $options[$code]) {
            return $options[$code];
        }

        return false;
    }

    /**
     * Return added payment means.
     *
     * @return array[string][array] An array "$code => $option" of added payment means
     */
    public function getAddedPaymentMeans()
    {
        $configAddedPaymentMeans = $this->dataHelper->unserialize($this->getConfigData('added_payment_means')); // The user-added payment means.
        $addedPaymentMeans = [];
        if (is_array($configAddedPaymentMeans) && ! empty($configAddedPaymentMeans)) {
            foreach ($configAddedPaymentMeans as $value) {
                if (empty($value)) {
                    continue;
                }

                $addedPaymentMeans[$value['meanCode']] = $value['meanName'];
            }
        }

        return $addedPaymentMeans;
    }

    public function getSupportedPaymentMeans()
    {
        $supportedCards = \Lyranetwork\Micuentaweb\Model\Api\MicuentawebApi::getSupportedCardTypes();

        // Added payment means.
        $addedCards = $this->getAddedPaymentMeans();
        foreach ($addedCards as $key => $label) {
            if (! isset($supportedCards[$key])) {
                $supportedCards[$key] = $label;
            }
        }

        return $supportedCards;
    }

    /**
     * Return grouping mode.
     *
     * @return int
     */
    public function getRegroupMode()
    {
        return $this->getConfigData('regroup_payment_means');
    }
}
