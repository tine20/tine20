/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.GDPR.Addressbook');

require('./DataProvenancePicker');

Tine.GDPR.Addressbook.DataProvenanceDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    labelAlign: 'top',
    layout: 'form',
    frame: false,
    

    canonicalName: ['Addressbook',  'EditDialog', 'Contact', 'DataProvenanceDialog'].join(Tine.Tinebase.CanonicalPath.separator),

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('GDPR');
        var dataProvenanceMandatory = Tine.Tinebase.configManager.get('dataProvenanceADBContactMandatory', 'GDPR');

        this.items = [Tine.widgets.form.RecordPickerManager.get('GDPR', 'DataProvenance', {
            fieldLabel: this.app.i18n._('Data Provenance'),
            allowBlank: dataProvenanceMandatory !== 'yes',
            name: 'GDPR_DataProvenance',
            anchor: '100%',
            value: dataProvenanceMandatory == 'default' ?
                Tine.Tinebase.configManager.get('defaultADBContactDataProvenance', 'GDPR') : ''
        }), {
            xtype: 'textfield',
            fieldLabel: this.app.i18n._('Reason for Editing'),
            name: 'GDPR_DataEditingReason',
            maxLength: 255,
            anchor: '100%',
            emptyText: this.app.i18n._('Please enter the reason for editing this data')
        }];
        this.items = [{layout: 'form', style: 'padding: 5px;', border: false, items: this.items}]
        Tine.GDPR.Addressbook.DataProvenanceDialog.superclass.initComponent.call(this);
    },

    onButtonCancel: Tine.Tinebase.dialog.Dialog.prototype.onButtonApply,

    getEventData: function() {
        this.getForm().isValid();
        return this.getForm().getFieldValues();
    },

    getCanonicalPathSegment: function () {
        return ['',
            this.app.appName,
            this.canonicalName,
        ].join(Tine.Tinebase.CanonicalPath.separator);
    }
});

Tine.GDPR.Addressbook.DataProvenanceDialog.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        title: Tine.Tinebase.appMgr.get('GDPR').i18n._('Please Confirm the Data Provenance'),
        closeAction: 'close',
        modal: true,
        width: 550,
        height: 200,
        layout: 'fit',
        plain: true,
        items: new Tine.GDPR.Addressbook.DataProvenanceDialog(config)
    });

    return window;
};

