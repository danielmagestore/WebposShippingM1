<?php
class Webkul_Mpshippingcanadapostal_Model_Carrier_LocalDelivery extends Mage_Shipping_Model_Carrier_Abstract
{
    /* Use group alias */
    protected $_code = 'mpshippingcanadapostal';
	 public function getAllowedMethods()
    {
        return explode(',', $this->getConfigData('allowed_methods'));
    }
	
	public function isTrackingAvailable()
    {
        return true;
    }
	public function isShippingLabelsAvailable()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isWebpos(){
        $isWebpos = false;
        if (Mage::helper('core')->isModuleEnabled('Magestore_WebposShipping')) {
            $helperPermission = Mage::helper('webpos/permission');
            $isWebpos = $helperPermission->validateRequestSession();
        }
        return ($isWebpos)?true:false;
    }

    /**
     * @return mixed
     */
    public function getQuote(){
        $session = Mage::getSingleton('checkout/session');
        if($this->isWebpos()){
            $checkoutService = Mage::getSingleton('magestore_webpos_service_checkout_checkout');
            $orderCreateModel = $checkoutService->getCheckoutModel();
            $quote = $orderCreateModel->getQuote();
            return $quote;
        }else{
            return $session->getQuote();
        }
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request){
        // skip if not enabled
        if(!Mage::getStoreConfig('carriers/'.$this->_code.'/active') || Mage::getStoreConfig('carriers/mp_multi_shipping/active')){ //return false;
        }

        $result = Mage::getModel('shipping/rate_result');
        try {
            $postcode = $this->getQuote()->getShippingAddress()->getPostcode();
            $countrycode = $this->getQuote()->getShippingAddress()->getCountryId();

            switch($countrycode) {
                case 'CA':
                    $dest_country = "_ca";
                    break;
                case 'US':
                    $dest_country = "_us";
                    break;
                default:
                    $dest_country = "_int";
                    break;
            }

            //if($countrycode !='CA' ){return false;}
            $postcode=str_replace('-', '', $postcode);
            $shippostaldetail=array('countrycode'=>$countrycode,'postalcode'=>$postcode);
            $shippingdetail=array();

            foreach($this->getQuote()->getAllVisibleItems() as $item) {

                /* $proid=$item->getProductId();
                $collection=Mage::getModel('marketplace/product')->getCollection()->addFieldToFilter('mageproductid',array('eq'=>$proid));
                foreach($collection as $temp){
                    $partner=$temp->getUserid();
                } */

                $product=Mage::getModel('catalog/product')->load($proid)->getWeight();
                $weight=$product*$item->getQty();

                if(count($shippingdetail)==0){
                    array_push($shippingdetail,array('seller_id'=>$partner,'items_weight'=>$weight,'product_name'=>$item->getName(),'qty'=>$item->getQty(),'item_id'=>$item->getId()));
                }else{
                    $shipinfoflag=true;
                    $index=0;
                    foreach($shippingdetail as $itemship){
                        if($itemship['seller_id']==$partner){
                            $itemship['items_weight']=$itemship['items_weight']+$weight;
                            $itemship['product_name']=$itemship['product_name'].",".$item->getName();
                            $itemship['qty']=$itemship['qty']+$item->getQty();
                            $itemship['item_id']=$itemship['item_id'].",".$item->getId();
                            $shippingdetail[$index]=$itemship;
                            $shipinfoflag=false;
                        }
                        $index++;
                    }
                    if($shipinfoflag==true){
                        array_push($shippingdetail,array('seller_id'=>$partner,'items_weight'=>$weight,'product_name'=>$item->getName(),'qty'=>$item->getQty(),'item_id'=>$item->getId()));
                    }
                }
            }

            $shippingcalcutationdata=$this->getShippingPricedetail($shippingdetail,$shippostaldetail);
            $shippingcalcutation=$shippingcalcutationdata['shippingcalcutation'];
            $shipinfo=$shippingcalcutationdata['shipinfo'];
            /*store shipping in session*/
            $shippingAll=Mage::getSingleton('core/session')->getData('shippinginfo');
            $shippingAll[$this->_code]=$shipinfo;
            Mage::getSingleton('core/session')->setData('shippinginfo',$shippingAll);
            foreach($shippingcalcutation as $methodCode=>$values){
                $rate = Mage::getModel('shipping/rate_result_method');
                $rate->setCarrier('mpshippingcanadapostal');
                $rate->setCarrierTitle($this->getConfigData('title'));
                $rate->setMethod($methodCode);

                $rate->setMethodDescription($values['methodDescription']);

                if ($methodCode == $this->getConfigData('free_method'.$dest_country) && $this->getConfigData('free_shipping_enable')
                    && $this->getConfigData('free_shipping_subtotal') <= $request->getPackageValueWithDiscount()
                ){
                    $rate->setMethodTitle($values['methodtitle']."- Free Shipping");
                    $rate->setMethodTitle($values['methodtitle']."- Free Shipping");
                    $rate->setCost(0,0);
                    $rate->setPrice(0,0);
                } else {
                    $rate->setMethodTitle($values['methodtitle']);
                    $rate->setCost($shipinfo[$methodCode],$methodCode);
                    $rate->setPrice($shipinfo[$methodCode],$methodCode);
                }
                $result->append($rate);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier('mpshippingcanadapostal');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->_parseCpError($e->getMessage()));
            $result->append($error);
        }

        return $result;
    }

    public function _parseCpError($message)
    {
        $types = array(
            'weight' => 'The package exceeds the maximum weight allowed 99KG',
            'length' => 'Maximum length of 999 cm exceeded',
            'height' => 'Maximum height of 999 cm exceeded',
            'width'  => 'Maximum width of 999 cm exceeded',
            'zip-code' => 'The Zip code entered had an invalid format.',
            'postal-code' => 'The Postal code entered had an invalid format.',
            'contract-id' => 'Agreement number not found, The agreement number is required to generate returns.',
            'customs-value-per-unit' => 'Maximum value per unit of 999.99 exceeded'

        );
        foreach($types as $key => $value){
            $pos = strpos($message, $key);
            if($pos!==false){
                $message = $value;
            }
        }
        return $message;
    }

    public function getShippingPricedetail($shippingdetail,$shippostaldetail){
        $shiprate=array();
        $shipdata=array();
        $shippingcalcutation=array();
        $shippinginfo=array();
        $count=0;
        $postcode=$this->getQuote()->getShippingAddress()->getPostcode();
        $countrycode=$this->getQuote()->getShippingAddress()->getCountry();
        /***/
        $helper             = Mage::Helper('mpshippingcanadapostal');
        $rateAdapter        = Mage::getModel('mpshippingcanadapostal/adapter_rating');
        $allowedMethods     = $this->getAllowedMethods();
        //$result             = Mage::getModel('shipping/rate_result');
        $selectedMethod     = unserialize(Mage::getSingleton('customer/session')->getSelectedMethod());
        $isD2PO             = Mage::getSingleton('customer/session')->getIsD2PO();

        if($isD2PO && Mage::app()->getRequest()->getControllerName() != 'cart'){
            $allowedMethods = array('DOM.EP', 'DOM.XP');
        }else {
            $selectedMethod = '';
            Mage::getSingleton('customer/session')->setSelectedMethod($selectedMethod);
        }
        /***/
        foreach ($shippingdetail as $shipdetail) {
            $sellerorigin='';
            $originpostcodes= 'V0A 1B0';
            $origincountrycode="CA";
            if($shipdetail['seller_id']){
                $address=Mage::getModel('customer/customer')->load($shipdetail['seller_id'])->getDefaultShipping();
                $originpostcode= Mage::getModel('customer/address')->load($address)->getPostcode();
                if($originpostcode!=""){
                    $originpostcodes= Mage::getModel('customer/address')->load($address)->getPostcode();
                    $origincountrycode= Mage::getModel('customer/address')->load($address)->getCountryId();
                }
            }
            $parcel = array(
                'weight' => $helper->convertWeight($shipdetail['items_weight'], $this->getConfigData('weight')),
                'origin_postal_code'=>$originpostcodes,
                'origin_postal_country'=>$origincountrycode
            );

            //TODO: If shopping cart weight greater than 99.999 limit break down into multiple parcels
            if($parcel['weight'] > 99.999){
                throw new Exception('Maximum Weight per parcel allowed 99.999KG');
            }

            $destination = array(
                'postal_code'       => $helper->formatPostalCode($postcode),
                'dest_country_id'   => $countrycode
            );

            // Mage Call to the Rate adapter
            $submethod=array();
            $response = $rateAdapter->getRates($parcel, $destination);
            if ($response->{'price-quotes'}->{'price-quote'} && is_array($response->{'price-quotes'}->{'price-quote'})) {
                foreach ($response->{'price-quotes'}->{'price-quote'} as $estimate) {
                    if (in_array($estimate->{'service-code'}, $allowedMethods)) {
                        $methodCode = $estimate->{'service-code'};

                        if(!empty($selectedMethod['code']) && $methodCode == $selectedMethod['code']) {
                            $methodName         = $selectedMethod['title'];
                            $methodDescription  = $selectedMethod['description'];
                        } else{
                            $methodName         = $estimate->{'service-name'};
                            $methodDescription = '';
                        }

                        if($this->getConfigData('estimate_delivery') && $estimate->{'service-standard'}){
                            $methodName .= ' - Est. Delivery ' . $estimate->{'service-standard'}->{'expected-delivery-date'};
                        }

                        if($count==0){
                            $shippingcalcutation[$methodCode]=array('methodcode'=>$methodCode,'methodtitle'=>$methodName,'methodDescription'=>$methodDescription,'cost'=>$this->getMethodPrice($estimate->{'price-details'}->{'due'},$methodCode),'price'=>$this->getMethodPrice($estimate->{'price-details'}->{'due'},$methodCode));
                        }else{
                            foreach($shippingcalcutation as $methodCodetemp=>$value){
                                if($methodCodetemp==$methodCode){
                                    $shippingcalcutation[$methodCode]['cost']=$shippingcalcutation[$methodCode]['cost']+$this->getMethodPrice($estimate->{'price-details'}->{'due'},$methodCode);
                                    $shippingcalcutation[$methodCode]['price']=$shippingcalcutation[$methodCode]['cost']+$this->getMethodPrice($estimate->{'price-details'}->{'due'},$methodCode);
                                }
                            }
                        }

                        array_push($shipdata,array('methodcode'=>$methodCode,'cost'=>$this->getMethodPrice($estimate->{'price-details'}->{'due'},$methodCode)));
                        array_push($submethod,array('method'=>$methodName." (Canada Postal)",'cost'=>$this->getMethodPrice($estimate->{'price-details'}->{'due'},$methodCode),'error'=>0));
                    }
                }
            }

            $count++;
            array_push($shiprate,array('cost'=>$this->getMethodPrice($estimate->{'price-details'}->{'due'},$methodCode)));
            $price=$this->getMethodPrice($estimate->{'price-details'}->{'due'},$methodCode);
            array_push($shippinginfo,array('seller_id'=>$shipdetail['seller_id'],'methodcode'=>$this->_code,'shipping_ammount'=>$price,'product_name'=>$shipdetail['product_name'],'submethod'=>$submethod));
        }
        $datacount=0;
        $shipinfo=array();
        foreach($shipdata as $tempdata){
            $flag=false;
            if($datacount==0){
                $shipinfo[$tempdata['methodcode']]=$tempdata['cost'];
                $datacount++;
            }else{
                foreach($shipinfo as $methodcode=>$cost){
                    if($flag){continue;}
                    if($tempdata['methodcode']==$methodcode){
                        $shipinfo[$tempdata['methodcode']]=$shipinfo[$tempdata['methodcode']]+$tempdata['cost'];
                        $flag=true;
                    }
                }
                if(!$flag){$shipinfo[$tempdata['methodcode']]=$tempdata['cost'];}
            }
        }
        $arr=array();
        return array('shipinfo'=>$shipinfo,'shippingcalcutation'=>$shippingcalcutation,'shippinginfo'=>$shippinginfo,'errormsg'=>$msg);
    }
	
	public function getTrackingInfo($tracking)
    {
        $result = $this->getTracking($tracking);
        if ($result instanceof Mage_Shipping_Model_Tracking_Result) {
            if ($trackings = $result->getAllTrackings()) {
                return $trackings[0];
            }
        } elseif (is_string($result) && !empty($result)) {
            return $result;
        }

        return false;
    }

    /**
     * Get Tracking Link
     *
     * @param $shippingNumber
     * @return Mage_Core_Model_Abstract|Mage_Shipping_Model_Rate_Result|null
     */
    public function getTracking($shippingNumber)
    {
        $resultArray    = array();
        $trackingNumber = 0;

       // $trackingAdapter = Mage::getModel('mpshippingcanadapostal/adapter_tracking');

        $split = explode('-', $shippingNumber);
        $track = $split[0];
        $trackingNumber = $split[0];


       /*  $shipmentAdapter    = Mage::getModel('mpshippingcanadapostal/adapter_shipment');
        $shipment           = $shipmentAdapter->getShipment($track);

        if(isset($shipment->{'shipment-info'}->{'tracking-pin'}))
        {
            $trackingNumber = $shipment->{'shipment-info'}->{'tracking-pin'};
        } */
		
        $resultArray['status'] = '<a href="' . sprintf('http://www.canadapost.ca/cpotools/apps/track/personal/findByTrackNumber?trackingNumber=%s&LOCALE=en', $trackingNumber) . '" target="_blank" >Click here</a> to track your shipment.';
        $track = $trackingNumber;

        if (!$this->_result) {
            $this->_result = Mage::getModel('shipping/tracking_result');
        }


        $tracking = Mage::getModel('shipping/tracking_result_status');
        $tracking->setCarrier('mpshippingcanadapostal');
        $tracking->setCarrierTitle($this->getConfigData('title'));
        $tracking->setTracking($track);

        //$progressData = $trackingAdapter->getTrackingDetail($track);
        $tracking->setProgressDetail($track);
        $tracking->addData($resultArray);

        $this->_result->append($tracking);

        return $this->_result;
    }
	
	
}
 
