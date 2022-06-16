/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

Tine.Filemanager.RevisionPanel = Ext.extend(Ext.form.FieldSet, {

    editDialog: null,
    app: null,

    layout: 'hfit',

    keep: null,

    keepNum: null,
    keepNumInput: null,

    keepMonth: null,
    keepMonthInput: null,

    config: null,
    readOnly: false,

    initComponent: function () {
        var _ = window.lodash;

        this.config = Tine.Tinebase.configManager.get('filesystem');

        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
        this.title = this.title || this.app.i18n._('Revision');

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('save', this.onSave, this);

        this.editDialog.checkStates = this.editDialog.checkStates.createSequence(this.manageFields, this);

        var type = _.get(this.editDialog, 'record.data.type');
        var adminGrant = _.get(this.editDialog, 'record.data.account_grants.adminGrant', false);

        if (type !== 'folder' || !adminGrant) {
            this.readOnly = true;
        }

        this.hasOwnRevisionSettings = new Ext.form.Checkbox({
            checked: false,
            disabled: this.readOnly,
            boxLabel: this.app.i18n._('This folder has own revision settings'),
            listeners: {scope: this, check: this.onOwnRevisionCheck}
        });

        this.items = [{
            items: [this.hasOwnRevisionSettings, [{
                xtype: 'container',
                cls: 'revision-container',
                margins: '0 20 0 0',
                items: [{
                    xtype: 'checkbox',
                    boxLabel: this.app.i18n._('Revision active'),
                    name: 'keep',
                    columnWidth: 1,
                    readOnly: false,
                    disabled: false,
                    ref: '../../keep',
                    listeners: {scope: this, check: this.onKeepCheck}
                }, {
                    xtype: 'container',
                    cls: 'revision-checkbox-field',
                    items: [{
                        xtype: 'checkbox',
                        boxLabel: this.app.i18n._('Limit revision amount to'),
                        name: 'keepNum',
                        columnWidth: 1,
                        readOnly: false,
                        disabled: false,
                        ref: '../../../keepNum',
                        listeners: {scope: this, check: this.onKeepNumCheck}
                    }, {
                        xtype: 'numberfield',
                        ref: '../../../keepNumInput'
                    }]
                }, {
                    xtype: 'container',
                    cls: 'revision-checkbox-field',
                    items: [{
                        xtype: 'checkbox',
                        boxLabel: this.app.i18n._('Hold-back in months'),
                        name: 'keepMonth',
                        columnWidth: 1,
                        readOnly: false,
                        disabled: false,
                        ref: '../../../keepMonth',
                        listeners: {scope: this, check: this.onKeepMonthCheck}
                    }, {
                        xtype: 'numberfield',
                        ref: '../../../keepMonthInput'
                    }]
                }]
            }]]
        }];

        this.supr().initComponent.call(this);

        this.manageFields();
    },

    onKeepCheck: function (cb, checked) {
        this.manageFields();

        if (false === checked) {
            this.keepNum.setValue(false);
            this.keepMonth.setValue(false);
        }
    },

    onKeepNumCheck: function (cb, checked) {
        this.manageFields();

        if (false === checked) {
            this.keepNumInput.setValue(null);
        }
    },

    onKeepMonthCheck: function (cb, checked) {
        this.manageFields();

        if (false === checked) {
            this.keepMonthInput.setValue(null);
        }
    },

    manageFields: function () {
        this.keep.setDisabled(this.readOnly || false === this.hasOwnRevisionSettings.getValue());

        this.keepNum.setDisabled(this.keep.disabled || false === this.keep.getValue());
        this.keepNumInput.setDisabled(this.keep.disabled || false === this.keep.getValue() || false === this.keepNum.getValue());

        this.keepMonth.setDisabled(this.keep.disabled || false === this.keep.getValue());
        this.keepMonthInput.setDisabled(this.keep.disabled || false === this.keep.getValue() || false === this.keepMonth.getValue());
    },

    onOwnRevisionCheck: function (cb, checked) {
        this.manageFields();
    },

    onRecordLoad: function (editDialog, record, ticketFn) {
        var _ = window.lodash;

        this.hasOwnRevisionSettings.setValue(record.id === _.get(record, 'data.revisionProps.nodeId'));
        this.keep.setValue(_.get(record, 'data.revisionProps.keep', _.get(this.config, 'modLogActive', false)));

        if (_.get(record, 'data.revisionProps.keepNum', _.get(this.config, 'numKeepRevisions', false))) {
            this.keepNum.setValue(true);
            this.keepNumInput.setValue(_.get(record, 'data.revisionProps.keepNum', _.get(this.config, 'numKeepRevisions')));
        }

        if (_.get(record, 'data.revisionProps.keepMonth', _.get(this.config, 'monthKeepRevisions', false))) {
            this.keepMonth.setValue(true);
            this.keepMonthInput.setValue(_.get(record, 'data.revisionProps.keepMonth', _.get(this.config, 'monthKeepRevisions')));
        }
    },

    onSave: function (editDialog, record, ticketFn) {
        if (this.readOnly) {
            return;
        }

        var _ = window.lodash;

        // In case there is no own setting, we don't need to persist current values we just empty it
        // When reloaded the server is supposed to send the correct inherited values
        if (false === this.hasOwnRevisionSettings.getValue()) {
            _.set(record, 'data.revisionProps', {});
            return;
        }

        var data = {};

        _.set(data, 'nodeId', record.id);
        _.set(data, 'keep', this.keep.getValue());
        _.set(data, 'keepNum', this.keepNumInput.getValue());
        _.set(data, 'keepMonth', this.keepMonthInput.getValue());

        _.set(record, 'data.revisionProps', data);
    }
});
