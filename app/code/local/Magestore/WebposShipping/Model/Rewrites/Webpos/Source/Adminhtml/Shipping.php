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

class Magestore_WebposShipping_Model_Rewrites_Webpos_Source_Adminhtml_Shipping extends Magestore_Webpos_Model_Source_Adminhtml_Shipping{


    public function toOptionArray() {
        $collection = Mage::getModel('shipping/config')->getActiveCarriers();

        if (!count($collection))
            return;

        $options = array(
            array('value' => '', 'label' => '--- '.$this->_shippingHelper->__('None').' ---')
        );
        foreach ($collection as $ccode => $carrier) {
            $methods = $carrier->getAllowedMethods();
            if(count($methods) > 0){
                foreach ($methods as $mcode => $method) {
                    if($ccode == "mpshippingcanadapostal"){
                        if (Mage::helper('core')->isModuleEnabled('Webkul_Mpshippingcanadapostal')) {
                            $method = Webkul_Mpshippingcanadapostal_Model_Config_Method::getName($method);
                        }
                    }
                    $title = $carrier->getConfigData('title').' - '.$method;
                    $options[] = array('value' => $ccode.'_'.$mcode, 'label' => $title);
                }
            }
        }
        return $options;
    }

    public function getShippingTitleByCode($findCode){
        $methodTitle = '';
        $collection = Mage::getModel('shipping/config')->getActiveCarriers();
        if(count($collection) > 0) {
            foreach ($collection as $code => $carrier) {
                $methods = $carrier->getAllowedMethods();
                if(count($methods) > 0) {
                    foreach ($methods as $mcode => $method) {
                        $methodCode = $code . '_' . $mcode;
                        if($findCode == $methodCode){
                            if($code == 'webpos_shipping'){
                                $methodTitle = $carrier->getConfigData('title').' - '.$carrier->getConfigData('name');
                                if($mcode != 'storepickup'){
                                    $methodTitle = $carrier->getConfigData('title').' - '.$carrier->getConfigData($mcode.'_name');
                                }
                            }else {
                                if($code == "mpshippingcanadapostal"){
                                    if (Mage::helper('core')->isModuleEnabled('Webkul_Mpshippingcanadapostal')) {
                                        $method = Webkul_Mpshippingcanadapostal_Model_Config_Method::getName($method);
                                    }
                                }
                                $methodTitle = $carrier->getConfigData('title') . ' - ' . $carrier->getConfigData('name');
                            }
                        }
                    }
                }
            }
        }
        return $methodTitle;
    }

    public function getShippingData(){
        $collection = Mage::getModel('shipping/config')->getActiveCarriers();
        $shippingList = array();
        if(count($collection) > 0) {
            foreach ($collection as $code => $carrier) {
                if (!in_array($code, $this->_allowShippings))
                    continue;
                $methods = $carrier->getAllowedMethods();
                if(count($methods) > 0) {
                    foreach ($methods as $mcode => $method) {
                        $isDefault = '0';
                        if ($code == $this->_shippingHelper->getDefaultShippingMethod()) {
                            $isDefault = '1';
                        }
                        $methodCode = $code . '_' . $mcode;
                        if($code == 'webpos_shipping'){
                            $methodTitle = $carrier->getConfigData('title').' - '.$carrier->getConfigData('name');
                            $methodPrice = ($carrier->getConfigData('price') != null) ? $carrier->getConfigData('price') : '0';
                            if($mcode != 'storepickup'){
                                $methodTitle = $carrier->getConfigData('title').' - '.$carrier->getConfigData($mcode.'_name');
                                $methodPrice = ($carrier->getConfigData($mcode.'_price') != null) ? $carrier->getConfigData($mcode.'_price') : '0';
                            }
                            $methodPriceType = '';
                            $methodDescription = '0';
                            $methodSpecificerrmsg = '';
                        }else {
                            $methodTitle = $carrier->getConfigData('title') . ' - ' . $carrier->getConfigData('name');
                            $methodPrice = ($carrier->getConfigData('price') != null) ? $carrier->getConfigData('price') : '0';
                            $methodPriceType = ($carrier->getConfigData('type') != null) ? $carrier->getConfigData('type') : '';
                            $methodDescription = ($carrier->getConfigData('description') != null) ? $carrier->getConfigData('description') : '0';
                            $methodSpecificerrmsg = ($carrier->getConfigData('specificerrmsg') != null) ? $carrier->getConfigData('specificerrmsg') : '';

                            if($code == "mpshippingcanadapostal"){
                                if (Mage::helper('core')->isModuleEnabled('Webkul_Mpshippingcanadapostal')) {
                                    $method = Webkul_Mpshippingcanadapostal_Model_Config_Method::getName($method);
                                    $methodTitle = $carrier->getConfigData('title') . ' - ' .$method;
                                }
                            }
                        }
                        $shippingModel = Mage::getModel('webpos/shipping_shipping');
                        $shippingModel->setCode($methodCode);
                        $shippingModel->setTitle($methodTitle);
                        $shippingModel->setPrice($methodPrice);
                        $shippingModel->setDescription($methodDescription);
                        $shippingModel->setIsDefault($isDefault);
                        $shippingModel->setErrorMessage($methodSpecificerrmsg);
                        $shippingModel->setPriceType($methodPriceType);
                        $shippingList[] = $shippingModel->getData();
                    }
                }
            }
        }
        return $shippingList;
    }
}
