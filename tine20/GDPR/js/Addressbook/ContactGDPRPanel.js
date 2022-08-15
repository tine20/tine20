/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.GDPR.Addressbook');

Tine.GDPR.Addressbook.ContactGDPRPanel = Ext.extend(Ext.Panel, {

    layout: 'fit',
    border: false,
    requiredGrant: 'editGrant',

    canonicalName: ['Addressbook',  'EditDialog', 'Contact', 'GDPRPanel'].join(Tine.Tinebase.CanonicalPath.separator),

    initComponent: function () {
        this.app = this.app || Tine.Tinebase.appMgr.get('GDPR');
        this.title = this.app.i18n._('GDPR');

        this.blacklistContactCheckbox = new Ext.form.Checkbox({
            hideLabel: true,
            disabled: true,
            boxLabel: this.app.i18n._('Must not be contacted'),
            listeners: {scope: this, check: this.onBlacklistContactCheck}
        });
        this.blacklistContactDescription = new Ext.form.Label({
            text: this.app.i18n._("This Contact withdrawed usage of his data for any purpose.")
        });

        this.expiryDatePicker = new Ext.ux.form.ClearableDateField({
            fieldLabel: this.app.i18n._('Expiry Date'),
        });

        this.dataIntendedPurposesGrid = new Tine.widgets.grid.QuickaddGridPanel({
            border: true,
            frame: false,
            recordClass: Tine.GDPR.Model.DataIntendedPurposeRecord,
            columns: ['intendedPurpose', 'agreeDate', 'agreeComment', 'withdrawDate', 'withdrawComment'],
            defaultSortInfo: {field: 'agreeDate'},
            columnsConfig: {
                agreeComment: {width: 200},
                withdrawComment: {width: 200}
            },
            quickaddMandatory: 'intendedPurpose',
            validate: true,
            flex: 1,
        });


        this.items = [{
            layout: 'vbox',
            align: 'stretch',
            pack: 'start',
            border: false,
            items: [{
                layout: 'form',
                frame: true,
                width: '100%',
                items: [
                    this.blacklistContactCheckbox,
                    this.blacklistContactDescription,
                    this.expiryDatePicker,
                ]},
                this.dataIntendedPurposesGrid
            ]
        }];

        this.supr().initComponent.call(this);


    },

    onRender: function() {
        this.supr().onRender.apply(this, arguments);

        this.editDialog = this.findParentBy(function(c) {return c instanceof Tine.Addressbook.ContactEditDialog});

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        // NOTE: panel is usually rendered after record was load
        this.onRecordLoad(this.editDialog, this.editDialog.record);
    },

    onBlacklistContactCheck: function(cb, checked) {
        if (checked) {
            this.dataIntendedPurposesGrid.getStore().each(function (r) {
                if (!r.get('withdrawDate')) {
                    r.set('withdrawDate', new Date());

                    if (!r.get('withdrawComment')) {
                        r.set('withdrawComment', this.app.i18n._('Blacklist'));
                    }
                }
            }, this);
        } else {
            this.dataIntendedPurposesGrid.getStore().each(function (r) {
                Ext.each(['withdrawDate', 'withdrawComment'], function(k) {
                    if (r.isModified(k)) {
                        r.set(k, r.modified[k]);
                    }
                }, this);
            }, this);
        }

        this.dataIntendedPurposesGrid.setReadOnly(checked);
    },

    onRecordLoad: function(editDialog, record) {
        var _ = window.lodash,
            evalGrants = editDialog.evalGrants,
            hasRequiredGrant = !evalGrants || _.get(record, 'json.container_id.account_grants' + '.' + this.requiredGrant),
            blacklistContact = !!+record.get('GDPR_Blacklist'),
            expiryDate = record.get('GDPR_DataExpiryDate');
        
        this.blacklistContactCheckbox.setValue(blacklistContact);
        if (expiryDate) {
            this.expiryDatePicker.setValue(expiryDate);
        }
        this.dataIntendedPurposesGrid.setStoreFromArray(record.get('GDPR_DataIntendedPurposeRecord') || []);

        this.setReadOnly(!hasRequiredGrant);

        if (blacklistContact) {
            this.dataIntendedPurposesGrid.setReadOnly(true);
        }
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        this.dataIntendedPurposesGrid.setReadOnly(readOnly);
        this.blacklistContactCheckbox.setDisabled(readOnly);
    },

    onRecordUpdate: function(editDialog, record) {
        record.set('GDPR_DataIntendedPurposeRecord', this.dataIntendedPurposesGrid.getFromStoreAsArray());
        record.set('GDPR_Blacklist', this.blacklistContactCheckbox.getValue());
        record.set('GDPR_DataExpiryDate', this.expiryDatePicker.getValue());
    },

    getCanonicalPathSegment: function () {
        return ['',
            this.app.appName,
            this.canonicalName,
        ].join(Tine.Tinebase.CanonicalPath.separator);
    }

});

Ext.ux.ItemRegistry.registerItem('Tine.Addressbook.editDialog.mainTabPanel', Tine.GDPR.Addressbook.ContactGDPRPanel, 20);