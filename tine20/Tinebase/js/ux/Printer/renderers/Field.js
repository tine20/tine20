
Ext.ux.Printer.FieldRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {

    /**
     * Generates the body HTML for the field
     * @param {Ext.form.Field} field The field to print
     */
    generateBody: function(field) {
        var label = field.fieldLabel || field.ownerCt.title || '';

        return '' +
        '<div class="rp-print-single-details-row">' +
            '<span class="rp-print-single-label">' + label + '</span>' +
            '<span class="rp-print-single-value">' + field.getValue() + '</span>' +
        '</div>';

    }
});

Ext.ux.Printer.registerRenderer('field', Ext.ux.Printer.FieldRenderer);
