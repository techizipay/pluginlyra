<?php
/**
 * Copyright © Lyra Network.
 * This file is part of Mi Cuenta Web plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Micuentaweb\Controller\Payment\Rest;

use Lyranetwork\Micuentaweb\Model\ResponseException;
use Magento\Framework\DataObject;

class Check extends \Lyranetwork\Micuentaweb\Controller\Payment\Check
{
    /**
     * @var \Lyranetwork\Micuentaweb\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Lyranetwork\Micuentaweb\Model\Api\Form\ResponseFactory
     */
    protected $micuentawebResponseFactory;

    /**
     * @var \Lyranetwork\Micuentaweb\Helper\Rest
     */
    protected $restHelper;

    /**
     * @var \Magento\Checkout\Model\Type\Onepage
     */
    protected $onepage;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Lyranetwork\Micuentaweb\Controller\Processor\CheckProcessor $checkProcessor
     * @param \Magento\Framework\Controller\Result\RawFactory $rawResultFactory
     * @param \Lyranetwork\Micuentaweb\Helper\Rest $restHelper
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Model\Type\Onepage $onepage
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Lyranetwork\Micuentaweb\Controller\Processor\CheckProcessor $checkProcessor,
        \Magento\Framework\Controller\Result\RawFactory $rawResultFactory,
        \Lyranetwork\Micuentaweb\Helper\Rest $restHelper,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Type\Onepage $onepage
    ) {
        $this->restHelper = $restHelper;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->onepage = $onepage;
        $this->dataHelper = $checkProcessor->getDataHelper();
        $this->storeManager = $checkProcessor->getStoreManager();
        $this->orderFactory = $checkProcessor->getOrderFactory();
        $this->micuentawebResponseFactory = $checkProcessor->getMicuentawebResponseFactory();

        parent::__construct($context, $checkProcessor, $rawResultFactory);
    }

    protected function prepareResponse($params)
    {
        // Check the validity of the request.
        if (! $this->restHelper->checkResponseFormat($params)) {
            $this->dataHelper->log('Invalid response received. Content: ' . json_encode($params), \Psr\Log\LogLevel::ERROR);
            throw new ResponseException('<span style="display:none">KO-Invalid IPN request received.'."\n".'</span>');
        }

        $answer = json_decode($params['kr-answer'], true);
        if (! is_array($answer)) {
            $this->dataHelper->log('Invalid response received. Content: ' . json_encode($params), \Psr\Log\LogLevel::ERROR);
            throw new ResponseException('<span style="display:none">KO-Invalid IPN request received.' . "\n" . '</span>');
        }

        // Wrap payment result to use traditional order creation tunnel.
        $data = $this->restHelper->convertRestResult($answer);

        // Convert REST result to standard form response.
        $response = $this->micuentawebResponseFactory->create(
            [
                'params' => $data,
                'ctx_mode' => null,
                'key_test' => '',
                'key_prod' => '',
                'algo' => null
            ]
        );

        $quoteId = (int) $response->getExtInfo('quote_id');
        if (! $quoteId || ! $this->quoteRepository->get($quoteId)->getId()) {
            $this->dataHelper->log("Quote not found with ID #{$quoteId}.", \Psr\Log\LogLevel::ERROR);
            throw new ResponseException($response->getOutputForGateway('order_not_found'));
        }

        $quote = $this->quoteRepository->get($quoteId);

        // Disable quote.
        if ($quote->getIsActive()) {
            $quote->getPayment()->unsAdditionalInformation(\Lyranetwork\Micuentaweb\Helper\Payment::TOKEN_DATA);
            $quote->getPayment()->unsAdditionalInformation(\Lyranetwork\Micuentaweb\Helper\Payment::TOKEN);

            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
            $this->dataHelper->log("Cleared quote, reserved order ID: #{$quote->getReservedOrderId()}.");
        }

        // Case of failure or expiration when retries are enabled, do nothing before last attempt.
        if (! $response->isAcceptedPayment() && ($answer['orderCycle'] !== 'CLOSED')) {
            $this->dataHelper->log("Payment is not accepted but buyer can try to re-order. Do not create order at this time.
                Quote ID: #{$quoteId}, reserved order ID: #{$quote->getReservedOrderId()}.");
            throw new ResponseException($response->getOutputForGateway('payment_ko_bis'));
        }

        // Token is created before order creation, search order by quote.
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($quote->getReservedOrderId());
        if (! $order->getId()) {
            $this->saveOrderForQuote($quote);

            // Dispatch save order event.
            $result = new DataObject();
            $result->setData('success', true);
            $result->setData('error', false);

            $this->_eventManager->dispatch(
                'checkout_controller_onepage_saveOrder',
                [
                    'result' => $result,
                    'action' => $this
                ]
            );

            // Load newly created order.
            $order->loadByIncrementId($quote->getReservedOrderId());
            if (! $order->getId()) {
                $this->dataHelper->log("Order cannot be created. Quote ID: #{$quoteId}, reserved order ID: #{$quote->getReservedOrderId()}.", \Psr\Log\LogLevel::ERROR);
                throw new ResponseException($response->getOutputForGateway('ko', 'Error when trying to create order.'));
            }

            $this->dataHelper->log("Order #{$order->getIncrementId()} has been created for quote #{$quoteId}.");
        } else {
            $this->dataHelper->log("Found order #{$order->getIncrementId()} for quote #{$quoteId}.");
        }

        // Get store id from order.
        $storeId = $order->getStore()->getId();

        // Init app with correct store ID.
        $this->storeManager->setCurrentStore($storeId);

        // Check the authenticity of the request.
        if (! $this->restHelper->checkResponseHash($params, $this->restHelper->getPrivateKey($storeId))) {
            // Authentication failed.
            $this->dataHelper->log(
                "{$this->dataHelper->getIpAddress()} tries to access micuentaweb/payment_rest/response page without valid signature with parameters: " . json_encode($params),
                \Psr\Log\LogLevel::ERROR
            );

            throw new ResponseException($response->getOutputForGateway('auth_fail'));
        }

        return [
            'response' => $response,
            'order' => $order
        ];
    }

    protected function saveOrderForQuote($quote)
    {
        $this->onepage->setQuote($quote);
        if ($quote->getCustomerId()) {
            $this->onepage->getCustomerSession()->loginById($quote->getCustomerId());
        }

        $this->onepage->saveOrder();
    }
}
