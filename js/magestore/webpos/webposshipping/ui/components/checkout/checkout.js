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
        'ui/components/checkout/checkout',
        'helper/general',
        'webposshipping/model/checkout/shipping'
    ],
    function ($, ko, Component, Helper, ShippingModel) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'webposshipping/ui/checkout/checkout'
            },
            isOnlineCheckout: Helper.isOnlineCheckout,
            updatingShipping: ShippingModel.updatingShipping,
            initialize: function(){
                this._super();
                var self = this;
                Helper.observerEvent('go_to_checkout_page', function(){
                    ShippingModel.autoCheckShipping();
                });
            },
            updateShipping: function(){
                ShippingModel.checkShipping();
            }
        });
    }
);