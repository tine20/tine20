
Ext.ux.Printer.CheckRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {

    /**
     * Generates the body HTML for the checkbox
     * @param {Ext.form.Field} field The checkbox to print
     */
    generateBody: function(field) {
        var value = !!+field.getValue() ?  window.i18n._('Yes') : window.i18n._('No');

        return '' +
        '<div class="rp-print-single-details-row">' +
            '<span class="rp-print-single-label">' + (field.boxLabel || field.fieldLabel) + '</span>' +
            '<span class="rp-print-single-value">' + value + '</span>' +
        '</div>';

    }
});

Ext.ux.Printer.registerRenderer('checkbox', Ext.ux.Printer.CheckRenderer);
