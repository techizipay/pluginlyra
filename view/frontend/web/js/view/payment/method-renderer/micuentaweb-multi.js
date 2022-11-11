/**
 * Copyright © Lyra Network.
 * This file is part of Mi Cuenta Web plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/*browser:true*/
/*global define*/
define(
    [
        'Lyranetwork_Micuentaweb/js/view/payment/method-renderer/micuentaweb-abstract'
    ],
    function(Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Lyranetwork_Micuentaweb/payment/micuentaweb-multi',
                micuentawebMultiOption: window.checkoutConfig.payment.micuentaweb_multi.availableOptions ?
                    window.checkoutConfig.payment.micuentaweb_multi.availableOptions[0]['key'] : null,
                micuentawebCcType: window.checkoutConfig.payment.micuentaweb_multi.availableCcTypes ?
                    window.checkoutConfig.payment.micuentaweb_multi.availableCcTypes[0]['value'] : null
            },

            initObservable: function() {
                this._super();
                this.observe('micuentawebCcType');
                this.observe('micuentawebMultiOption');

                return this;
            },

            getData: function() {
                var data = this._super();

                if (this.getEntryMode() == 2) { // Payment means selection on merchant site.
                    data['additional_data']['micuentaweb_multi_cc_type'] = this.micuentawebCcType();
                }

                data['additional_data']['micuentaweb_multi_option'] = this.micuentawebMultiOption();

                return data;
            },

            showLabel: function() {
                return true;
            },

            getAvailableOptions: function() {
                return window.checkoutConfig.payment.micuentaweb_multi.availableOptions;
            }
        });
    }
);
