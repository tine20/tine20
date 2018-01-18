
Ext.ux.Printer.ComboRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {

    /**
     * Generates the body HTML for the combo box
     * @param {Ext.form.Field} field The field to print
     */
    generateBody: function(field) {
        return '' +
        '<div class="rp-print-single-details-row">' +
            '<span class="rp-print-single-label">' + field.fieldLabel + '</span>' +
            '<span class="rp-print-single-value">' + field.getRawValue() + '</span>' +
        '</div>';

    }
});

Ext.ux.Printer.registerRenderer('combo', Ext.ux.Printer.ComboRenderer);
