<?php
/**
 * Copyright © Lyra Network.
 * This file is part of Mi Cuenta Web plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Micuentaweb\Model\AdminOrder;

class EmailSender extends \Magento\Sales\Model\AdminOrder\EmailSender
{
    /**
     * Send email about new order.
     *
     * @param Order $order
     * @return bool
     */
    public function send(\Magento\Sales\Model\Order $order)
    {
        $method = $order->getPayment()->getMethodInstance();
        if ($method instanceof \Lyranetwork\Micuentaweb\Model\Method\Micuentaweb && ! $order->getCanSendNewEmailFlag()) {
            return true;
        }

        return parent::send($order);
    }
}
