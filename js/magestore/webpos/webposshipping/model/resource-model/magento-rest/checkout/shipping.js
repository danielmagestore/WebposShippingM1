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
 * @package     Magestore_WebposShipping
 * @copyright   Copyright (c) 2016 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

define(
    [
        'model/resource-model/magento-rest/checkout/abstract'
    ],
    function (onlineAbstract) {
        "use strict";

        return onlineAbstract.extend({
            /**
             * Init API routes
             */
            initialize: function () {
                this._super();
                this.apiCheckShippingUrl = "/webpos/checkout/checkShipping";
            },
            /**
             * Get API URL
             * @param key
             * @returns {*}
             */
            getApiUrl: function(key){
                switch(key){
                    case "apiCheckShippingUrl":
                        return this.apiCheckShippingUrl;
                }
            },
            /**
             * API to check shipping price - offline checkout
             * @param params
             * @param deferred
             */
            checkShipping: function(params, deferred){
                var apiUrl = this.getApiUrl("apiCheckShippingUrl");
                this.callApi(apiUrl, params, deferred);
            }
        });
    }
);