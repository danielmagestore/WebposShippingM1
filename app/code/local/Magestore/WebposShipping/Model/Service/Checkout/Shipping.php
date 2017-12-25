<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Webpos
 * @copyright   Copyright (c) 2016 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

class Magestore_WebposShipping_Model_Service_Checkout_Shipping extends Magestore_Webpos_Service_Checkout_Checkout
{
    /**
     *
     * @param string $customerId
     * @param Magestore_Webpos_Api_Cart_BuyRequestInterface[] $items
     * @param Magestore_Webpos_Api_Checkout_PaymentInterface $payment
     * @param \Magestore\Webpos\Api\Data\Checkout\ShippingInterface $shipping
     * @param \Magestore\Webpos\Api\Data\Checkout\ConfigInterface $config
     * @param string $couponCode
     * @param \Magestore\Webpos\Api\Data\Checkout\SessionDataInterface[] $sessionData
     * @return \Magestore\Webpos\Api\Data\Sales\OrderInterface
     * @throws \Exception
     */
    public function checkShipping($customerId, $items, $payment, $shipping, $config, $couponCode = "", $sessionData)
    {
        $message = array();
        $status = Magestore_Webpos_Api_ResponseInterface::STATUS_SUCCESS;
        $checkout = $this->getCheckoutModel();
        $checkout->checkShipping($customerId, $items, $payment, $shipping, $config, $couponCode, $sessionData);
        $checkout->setQuoteId($checkout->getQuote()->getId());
        $data = $this->_getQuoteData(array(Magestore_Webpos_Api_Cart_QuoteDataInitInterface::SHIPPING), $checkout);
        return $this->getResponseData($data, $message, $status);
    }

}