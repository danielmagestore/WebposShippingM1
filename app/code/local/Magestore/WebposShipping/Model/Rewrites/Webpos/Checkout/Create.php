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
class Magestore_WebposShipping_Model_Rewrites_Webpos_Checkout_Create extends Magestore_Webpos_Model_Checkout_Create
{
    /**
     * @param $customerId
     * @param $items
     * @param $payment
     * @param $shipping
     * @param $config
     * @param $couponCode
     */
    public function checkShipping($customerId, $items, $payment, $shipping, $config, $couponCode)
    {
        $session = $this->getSession();
        $session->clear();
        $session->setCurrencyId($config->getCurrencyCode());
        $session->setData('checking_promotion', true);
        $store = Mage::app()->getStore();
        $storeId = $store->getId();
        $session->setStoreId($storeId);

        $storeAddress = $this->getStoreAddressData();
        if ($customerId) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if ($customer->getId()) {
                $session->setCustomerId($customerId);
                $this->getQuote()->setCustomer($customer);
            }
        } else {
            $this->getQuote()->setCustomerIsGuest(true);
            $this->getQuote()->setCustomerEmail($storeAddress['email']);
        }
        $this->setWebPosBillingAddress($payment, $storeAddress);

        $this->initRuleData();
        $this->_processCart($items);
        $this->setWebPosBillingAddress($payment, $storeAddress);
        if (!$this->getQuote()->isVirtual()) {
            $this->setWebPosShippingAddress($shipping, $storeAddress);
            $this->saveShippingMethod($shipping->getMethod());
        }
        $this->_savePaymentData($payment);
        $this->_setCouponCode($couponCode);
        $this->getQuote()->getShippingAddress()->unsCachedItemsAll();
        $this->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
        $this->saveQuote();
        //$this->_removeCurrentQuote();
    }
}
