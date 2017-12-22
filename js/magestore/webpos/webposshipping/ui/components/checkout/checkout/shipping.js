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
        'ui/components/checkout/checkout/shipping'
    ],
    function ($, ko, Component) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'webposshipping/ui/checkout/checkout/shipping'
            },
            isError: function (data) {
                var isError = false;
                if(data && data.code){
                    var code = data.code;
                    isError = code.endsWith("_error");
                }
                return isError;
            }
        });
    }
);