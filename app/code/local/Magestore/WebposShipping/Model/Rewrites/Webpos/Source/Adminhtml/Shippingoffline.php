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

class Magestore_WebposShipping_Model_Rewrites_Webpos_Source_Adminhtml_Shippingoffline extends Magestore_Webpos_Model_Source_Adminhtml_Shippingoffline{

    public function __construct() {
        $this->_allowShippings = array('webpos_shipping','flatrate','freeshipping','mpshippingcanadapostal', 'ups');
    }

    public function toOptionArray() {
        $collection = Mage::getModel('shipping/config')->getActiveCarriers();

        if (!count($collection))
            return;

        $options = array();
        foreach ($collection as $code => $carrier) {
            if (!in_array($code, $this->_allowShippings))
                continue;
            $methods = $carrier->getAllowedMethods();
            if(count($methods) > 0) {
                foreach ($methods as $mcode => $method) {
                    if($code == "mpshippingcanadapostal"){
                        if (Mage::helper('core')->isModuleEnabled('Webkul_Mpshippingcanadapostal')) {
                            $mcode = $method;
                        }
                    }
                    $methodCode = $code.'_'.$mcode;
                    $title = $this->getShippingMethodTitle($carrier, $code, $mcode, $method);
                    if(($code == 'webpos_shipping') && ($mcode != 'storepickup')){
                        $title = $carrier->getConfigData('title') . ' - ' . $carrier->getConfigData($mcode.'_name');
                    }
                    $options[] = array('value' => $methodCode, 'label' => $title);
                }
            }
        }
        return $options;
    }

    public function getOfflineShippingData(){
        $collection = Mage::getModel('shipping/config')->getActiveCarriers();
        $shippingList = array();
        if(count($collection) > 0) {
            foreach ($collection as $code => $carrier) {
                $methods = $carrier->getAllowedMethods();
                if(count($methods) > 0){
                    foreach ($methods as $mcode => $method) {
                        if($code == "mpshippingcanadapostal"){
                            if (Mage::helper('core')->isModuleEnabled('Webkul_Mpshippingcanadapostal')) {
                                $mcode = $method;
                            }
                        }
                        $methodCode = $code.'_'.$mcode;
                        $offlineMethods = Mage::getStoreConfig('webpos/shipping/specificshipping');
                        if (!in_array($code, $this->_allowShippings) || !in_array($methodCode, explode(',', $offlineMethods)))
                            continue;
                        $isDefault = '0';
                        if($methodCode == Mage::getStoreConfig('webpos/shipping/defaultshipping')) {
                            $isDefault = '1';
                        }
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
                        }else{
                            $methodPrice = ($carrier->getConfigData('price') != null) ? $carrier->getConfigData('price') : '0';
                            $methodPriceType = ($carrier->getConfigData('type') != null) ? $carrier->getConfigData('type') : '';
                            $methodDescription = ($carrier->getConfigData('description') != null) ?$carrier->getConfigData('description') : '0';
                            $methodSpecificerrmsg = ($carrier->getConfigData('specificerrmsg') != null) ?$carrier->getConfigData('specificerrmsg') : '';
                            $methodTitle = $this->getShippingMethodTitle($carrier, $code, $mcode, $method);
                        }
                        $shippingData = array();
                        $shippingData['code'] = $methodCode;
                        $shippingData['title'] = $methodTitle;
                        $shippingData['price'] = $methodPrice;
                        $shippingData['description'] = $methodDescription;
                        $shippingData['error_message'] = $methodSpecificerrmsg;
                        $shippingData['price_type'] = $methodPriceType;
                        $shippingData['is_default'] = $isDefault;
                        $shippingList[] = $shippingData;
                    }
                }
            }
        }
        return $shippingList;
    }

    /**
     * @param $carrier
     * @param $ccode
     * @param $mcode
     * @param $mname
     * @return string
     */
    public function getShippingMethodTitle($carrier, $ccode, $mcode, $mname){
        $title = "";
        if($mname){
            $title = $carrier->getConfigData('title') . ' - ' . $mname;
        }
        if($carrier->getConfigData('name')){
            $title = $carrier->getConfigData('title').' - '.$carrier->getConfigData('name');
        }

        if($ccode == "mpshippingcanadapostal"){
            if (Mage::helper('core')->isModuleEnabled('Webkul_Mpshippingcanadapostal')) {
                $mname = Webkul_Mpshippingcanadapostal_Model_Config_Method::getName($mname);
                $title = $carrier->getConfigData('title') . ' - ' .$mname;
            }
        }

        if($ccode == "ups"){
            if (Mage::helper('core')->isModuleEnabled('Mage_Usa')) {
                $ups = Mage::getSingleton('usa/shipping_carrier_ups');
                foreach ($ups->getCode('method') as $k=>$v) {
                    if($k == $mcode){
                        $title = $carrier->getConfigData('title') . ' - ' .Mage::helper('usa')->__($v);
                    }
                }
            }
        }
        return $title;
    }
}
