// @ts-nocheck
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Set jQuery UI datepicker defaults.
        if ($.datepicker) {
            $.datepicker.setDefaults({
                dateFormat: 'yy-mm-dd',
                showOtherMonths: true,
                selectOtherMonths: true,
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-100:c+0' // Only allow past to current date
            });
        }

        // Configure jQuery UI autocomplete.
        if ($.ui && $.ui.autocomplete) {
            $.ui.autocomplete.prototype._renderItem = function (ul, item) {
                var term = String(this.term).split(' ').join('|');
                var re = new RegExp('(' + term + ')', 'gi');
                var text = String(item.label).replace(re, '<strong>$1</strong>');
                return $('<li></li>')
                    .data('item.autocomplete', item)
                    .append($('<div>').html(text))
                    .appendTo(ul);
            };

            // Append autocomplete dropdown styling.
            $('<style>')
                .text('.ui-autocomplete { max-height: 200px; overflow-y: auto; overflow-x: hidden; } ' +
                    '.ui-autocomplete .ui-menu-item { padding: 5px 10px; } ' +
                    '.ui-autocomplete .ui-state-active { background: #0073aa; color: white; }')
                .appendTo('head');
        }

        // Initialize all datepickers.
        $('.datepicker').datepicker();

        // Initialize tooltips.
        if ($.widget && $.widget.bridge && $.ui && $.ui.tooltip) {
            $('.tooltip-help').tooltip({
                position: { my: 'left+10 center', at: 'right center' },
                tooltipClass: 'custom-tooltip-styling'
            });
        }

        // Extend jQuery UI dialog defaults.
        if ($.ui && $.ui.dialog) {
            $.extend($.ui.dialog.prototype.options, {
                dialogClass: 'pcf-jquery-dialog',
                modal: true,
                resizable: false,
                draggable: false,
                autoOpen: false,
                closeText: '×'
            });
        }

        // Extend easing functions.
        if ($.easing) {
            $.extend($.easing, {
                easeOutQuad: function (x, t, b, c, d) {
                    return -c * (t /= d) * (t - 2) + b;
                }
            });
        }
    });
})(jQuery);
