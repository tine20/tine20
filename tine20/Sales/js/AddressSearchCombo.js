/*
 * Tine 2.0
 * Sales combo box and store
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * Address selection combo box
 *
 * @namespace   Tine.Sales
 * @class       Tine.Sales.AddressSearchCombo
 * @extends     Ext.form.ComboBox
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.AddressSearchCombo
 */
Tine.Sales.AddressSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {

    minListWidth: 400,
    sortBy: 'locality',
    recordClass: 'Sales.Model.Document_Address',
    resizable: true,

    checkState: function(editDialog, record) {
        const mc = editDialog?.recordClass?.getModelConfiguration();
        const type = _.get(mc, `fields.${this.fieldName}.config.type`, 'billing');

        const customerField = editDialog.getForm().findField('customer_id') || editDialog.getForm().findField('customer')
        const customer = customerField?.selectedRecord;
        const customer_id = customer?.json?.original_id || customer?.id;

        if (this.customer_id && this.customer_id !== customer_id) {
            // handle customer changes
            this.clearValue();
        }
        if (customer_id && !this.selectedRecord) {
            const typeRecords = customer?.data[type];
            const typeRecord = Ext.isArray(typeRecords) && typeRecords.length ? typeRecords[0] : customer?.data?.postal;
            if (typeRecord) {
                const address = Tine.Tinebase.data.Record.setFromJson(typeRecord, this.recordClass);
                this.setValue(address);
                this.fireEvent('select', this, address);
            }
        }
        this.customer_id = customer_id;

        this.setDisabled(!customer_id);
        if (! customer_id) {
            this.clearValue();
        } else {
            this.lastQuery = null;
            this.additionalFilters = [
                {field: 'customer_id', operator: 'equals', value: customer_id}
            ];
            if (type === 'postal') {
                this.additionalFilters.push({field: 'type', operator: 'equals', value: type });
            } else {
                this.additionalFilters.push({field: 'type', operator: 'not', value: type === 'billing' ? 'delivery' : 'billing' });
            }
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Address', Ext.extend(Tine.Sales.AddressSearchCombo, { recordClass: 'Sales.Model.Address' }));
Tine.widgets.form.RecordPickerManager.register('Sales', 'Document_Address', Tine.Sales.AddressSearchCombo);
