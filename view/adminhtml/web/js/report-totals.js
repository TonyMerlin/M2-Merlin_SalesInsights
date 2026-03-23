define([
    'jquery',
    'uiRegistry',
    'mage/url'
], function ($, registry) {
    'use strict';

    function formatPrice(value) {
        const number = parseFloat(value || 0);
        return new Intl.NumberFormat('en-GB', {
            style: 'currency',
            currency: 'GBP'
        }).format(number);
    }

    function updateTotals($el, totals) {
        $el.find('.col-order_count').text(totals.order_count ?? 0);
        $el.find('.col-qty_ordered').text(parseFloat(totals.qty_ordered ?? 0).toFixed(4));
        $el.find('.col-base_row_total').text(formatPrice(totals.base_row_total));
        $el.find('.col-base_discount_amount').text(formatPrice(totals.base_discount_amount));
        $el.find('.col-base_tax_amount').text(formatPrice(totals.base_tax_amount));
        $el.find('.col-base_row_total_incl_tax').text(formatPrice(totals.base_row_total_incl_tax));
        $el.find('.col-base_net_sales').text(formatPrice(totals.base_net_sales));
    }

    return function (config, element) {
        const $el = $(element);

        registry.async(config.provider)(function (provider) {
            function loadTotals() {
                const params = {
                    filters: provider.params.filters || {}
                };

                $.getJSON(config.totalsUrl, params, function (response) {
                    if (response && response.success && response.totals) {
                        updateTotals($el, response.totals);
                    }
                });
            }

            loadTotals();

            provider.on('params.filters', function () {
                loadTotals();
            });
        });
    };
});
