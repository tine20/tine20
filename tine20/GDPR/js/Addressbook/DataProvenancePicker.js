/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.GDPR.Addressbook');

Tine.GDPR.Addressbook.DataProvenancePicker = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    initComponent: function() {
        this.recordClass = Tine.GDPR.Model.DataProvenance;
        this.recordProxy =  Tine.GDPR.dataprovenanceBackend;

        Tine.GDPR.Addressbook.DataProvenancePicker.superclass.initComponent.call(this);
    },

    // add expired btn
    initList: function() {
        Tine.GDPR.Addressbook.DataProvenancePicker.superclass.initList.apply(this, arguments);

        if (this.pageTb && ! this.showExpired) {
            this.showExpiredBtn = new Ext.Button({
                text: this.app.i18n._('Show expired'),
                iconCls: 'action_showArchived',
                enableToggle: true,
                pressed: this.showExpired,
                scope: this,
                handler: function() {
                    this.showExpired = this.showExpiredBtn.pressed;
                    this.store.load();
                }
            });

            this.pageTb.add('-', this.showExpiredBtn);
            this.pageTb.doLayout();
        }
    },

    // append showExpired value
    onBeforeLoad: function (store, options) {
        Tine.GDPR.Addressbook.DataProvenancePicker.superclass.onBeforeLoad.apply(this, arguments);

        if (this.showExpiredBtn) {
            Ext.each(store.baseParams.filter, function(filter, idx) {
                if (filter.field == 'expiration'){
                    store.baseParams.filter.remove(filter);
                }
            }, this);

            if (! this.showExpiredBtn.pressed) {
                store.baseParams.filter.push({field: "expiration", operator: "after", value: new Date()});
            }
        }
    }
});
Tine.widgets.form.RecordPickerManager.register('GDPR', 'DataProvenance', Tine.GDPR.Addressbook.DataProvenancePicker);