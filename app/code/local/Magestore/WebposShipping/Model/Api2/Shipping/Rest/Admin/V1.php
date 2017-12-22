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


class Magestore_WebposShipping_Model_Api2_Shipping_Rest_Admin_V1 extends Magestore_Webpos_Model_Api2_Checkout_Abstract
{
    const ACTION_CHECK_SHIPPING = 'check_shipping';

    /**
     * Magestore_Webpos_Model_Api2_Checkout_Abstract constructor.
     */
    public function __construct() {
        $this->_service = Mage::getSingleton('webposshipping/service_checkout_shipping');
        $this->_helper = Mage::helper('webposshipping');
    }

    /**
     * Dispatch actions
     */
    public function dispatch()
    {
        ini_set('display_errors', 1);
        $this->_initStore();

        switch ($this->getActionType()) {
            case self::ACTION_CHECK_SHIPPING:
                $customerId = $this->_processRequestParams(self::CHECKOUT_CUSTOMER_ID);
                $items = $this->_getItemsBuyRequest();
                $config = $this->_getCheckoutConfigData();
                $payment = $this->_getCheckoutPaymentData();
                $shipping = $this->_getCheckoutShippingData();
                $couponCode = $this->_processRequestParams(self::CHECKOUT_COUPON_CODE);
                $sessionData = $this->_processRequestParams(self::CHECKOUT_SESSION_DATA);
                $result = $this->_service->checkShipping($customerId, $items, $payment, $shipping, $config, $couponCode, $sessionData);
                $this->_render($result);
                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_OK);
                break;
        }
    }
}
