/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Bananacode_RedLogistic/js/model/shipping-rates-validator',
        'Bananacode_RedLogistic/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingRatesValidator,
        shippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('redlogistic', shippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('redlogistic', shippingRatesValidationRules);

        return Component;
    }
);
