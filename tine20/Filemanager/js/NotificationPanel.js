/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

Tine.Filemanager.NotificationPanel = Ext.extend(Ext.Panel, {
    editDialog: null,
    app: null,

    layout: 'fit',
    border: false,

    notificationGrid: null,

    initComponent: function () {
        var _ = window.lodash;

        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
        this.title = this.title || this.app.i18n._('Notifications');

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('save', this.onSave, this);

        var store = new Ext.data.JsonStore({
            fields: ['active', 'summary', 'accountId', 'accountType', 'accountName'],
            idProperty: 'accountId'
        });

        var notificationProps = window.lodash.get(this.editDialog.record, 'data.notificationProps', []);
        var disable = notificationProps === null || notificationProps.length === 0;

        this.notificationGrid = new Tine.Filemanager.NotificationGridPanel({
            store: store,
            readOnly: disable,
            flex: 1,
            editDialog: this.editDialog
        });

        var featureEnabled = _.get(Tine.Tinebase.configManager.get('filesystem'), 'enableNotifications', false);

        var disabled = false;

        if (!featureEnabled) {
            disabled = true;
        } else if (!_.get(this.editDialog, 'record.data.account_grants.adminGrant', false)) {
            disabled = true;
        }

        this.hasOwnNotificationSettings = new Ext.form.Checkbox({
            checked: !disable,
            disabled: disabled,
            boxLabel: this.app.i18n._('This folder has own notification settings'),
            listeners: {scope: this, check: this.onOwnNotificationCheck}
        });

        this.items = [{
            layout: 'vbox',
            align: 'stretch',
            pack: 'start',
            border: false,
            items: [{
                layout: 'form',
                frame: true,
                hideLabels: true,
                width: '100%',
                items: [
                    this.hasOwnNotificationSettings
                ]
            },
                this.notificationGrid
            ]
        }];

        this.supr().initComponent.call(this);
    },

    onOwnNotificationCheck: function (cb, checked) {
        this.notificationGrid.setReadOnly(!checked);

        if (!checked) {
            this.notificationGrid.getStore().removeAll();
        }
    },

    onRecordLoad: function (editDialog, record, ticketFn) {
        this.notificationGrid.getStore().loadData(window.lodash.get(record, 'data.notificationProps', []), false);
    },

    onSave: function (editDialog, record, ticketFn) {
        var _ = window.lodash;

        // Remove properties
        _.get(record, 'data.notificationProps', []);

        var data = [];

        this.notificationGrid.getStore().each(function (record) {
            // prevent to send accountName here
            data.push({
                'active': record.data.active,
                'summary': record.data.summary,
                'accountId': record.data.accountId,
                'accountType': record.data.accountType
            });
        });

        record.set('notificationProps', data);
    }
});