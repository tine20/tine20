/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <kontakt@michaelspahn.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Crm');

Tine.Crm.ProductPickerCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    initComponent: function() {
        Tine.Crm.ProductPickerCombo.superclass.initComponent.apply(this, arguments);
    },

    onBeforeLoad: function (store, options) {
        Tine.Crm.ProductPickerCombo.superclass.onBeforeLoad.apply(this, arguments);
        store.baseParams.filter.push({field: 'is_active', operator: 'equals', value: true});
    }
});

Tine.widgets.form.RecordPickerManager.register('Crm', 'Lead', Tine.Crm.ProductPickerCombo);