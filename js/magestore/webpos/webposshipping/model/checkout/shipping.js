/*
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

define(
    [
        'jquery',
        'ko',
        'helper/general',
        'model/checkout/checkout',
        'webposshipping/model/resource-model/magento-rest/checkout/shipping'
    ],
    function ($, ko, Helper, CheckoutModel, ShippingResource) {
        "use strict";

        var ShippingModel = {
            updatingShipping: ko.observable(false),
            autoCheckingShipping: ko.observable(false),
            checkShipping: function(){
                var self = this;
                var deferred = $.Deferred();
                var params = CheckoutModel.getCheckPromotionParams();
                ShippingResource().setPush(true).setLog(false).checkShipping(params,deferred);

                self.updatingShipping(true);
                CheckoutModel.loading(true);
                deferred.done(function (response) {
                    if(response && response.data && response.data.shipping){
                        self.initShipping(response.data.shipping);
                    }
                }).always(function () {
                    self.updatingShipping(false);
                    CheckoutModel.loading(false);
                });
            },
            autoCheckShipping: function(){
                var deferred = $.Deferred();
                var self = this;
                if(self.autoCheckingShipping() == false) {
                    var params = CheckoutModel.getCheckPromotionParams();
                    ShippingResource().setPush(true).setLog(false).checkShipping(params, deferred);
                    self.updatingShipping(true);
                    self.autoCheckingShipping(true);
                    deferred.done(function (response) {
                        if(response && response.data && response.data.shipping){
                            self.initShipping(response.data.shipping);
                        }
                    }).always(function () {
                        self.updatingShipping(false);
                        self.autoCheckingShipping(false);
                    });
                }
                return deferred;
            },
            initShipping: function(shipping){
                if(shipping) {
                    Helper.dispatchEvent('load_shipping_online_after', {
                        items: shipping
                    });
                }
            }
        };
        return ShippingModel;
    }
);