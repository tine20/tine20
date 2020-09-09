/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine*/

Ext.ns('Tine.ActiveSync.syncdevices');

/**
 * @namespace   Tine.ActiveSync.syncdevices
 * @class       Tine.ActiveSync.SyncDeviceEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Sync devices edit dialog</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Admin.SyncDeviceEditDialog
 */
Tine.ActiveSync.SyncDeviceEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'syncdeviceEditWindow_',
    appName: 'ActiveSync',
    recordClass: Tine.ActiveSync.Model.SyncDevice,
    recordProxy: Tine.ActiveSync.syncdevicesBackend,
    evalGrants: false,
    
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function () {
        Tine.ActiveSync.SyncDeviceEditDialog.superclass.onRecordLoad.apply(this, arguments);
    },
    
    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function () {
        Tine.ActiveSync.SyncDeviceEditDialog.superclass.onRecordUpdate.apply(this, arguments);
    },
    
    /**
     * returns dialog
     */
    getFormItems: function () {
        return {
            layout: 'vbox',
            layoutConfig: {
                align: 'stretch',
                pack: 'start'
            },
            border: false,
            items: [{
                xtype: 'columnform',
                border: false,
                autoHeight: true,
                items: [
                    [{
                        columnWidth: 0.33,
                        fieldLabel: this.app.i18n._('Device ID'),
                        name: 'deviceid',
                        allowBlank: false,
                        readOnly: true,
                        maxLength: 40
                    }, {
                        columnWidth: 0.33,
                        fieldLabel: this.app.i18n._('Devicetype'),
                        name: 'devicetype',
                        allowBlank: false,
                        readOnly: true,
                        maxLength: 40
                    }, {
                        columnWidth: 0.333,
                        fieldLabel: this.app.i18n._('Owner'),
                        name: 'owner_id',
                        allowBlank: false,
                        xtype: 'addressbookcontactpicker',
                        userOnly: true,
                        useAccountRecord: true,
                        blurOnSelect: true,
                        selectOnFocus: true,
                        readOnly: true,
                        maxLength: 40
                    }], [{
                        columnWidth: 0.33,
                        fieldLabel: this.app.i18n._('Policy'),
                        name: 'policy_id',
                        readOnly: true,
                        maxLength: 40
                    }, {
                        columnWidth: 0.33,
                        fieldLabel: this.app.i18n._('ActiveSync Version'),
                        name: 'acsversion',
                        readOnly: true,
                        maxLength: 40
                    }, {
                        columnWidth: 0.333,
                        fieldLabel: this.app.i18n._('User agent'),
                        name: 'useragent',
                        readOnly: true,
                        maxLength: 40
                    }], [{
                        columnWidth: 0.33,
                        fieldLabel: this.app.i18n._('Model'),
                        name: 'model',
                        readOnly: true,
                        maxLength: 40
                    }, {
                        columnWidth: 0.33,
                        fieldLabel: this.app.i18n._('IMEI'),
                        name: 'imei',
                        readOnly: true,
                        maxLength: 40
                    }, {
                        columnWidth: 0.333,
                        fieldLabel: this.app.i18n._('Friendly Name'),
                        name: 'friendlyname',
                        readOnly: true,
                        maxLength: 40
                    }], [{
                        columnWidth: 0.33,
                        fieldLabel: this.app.i18n._('OS'),
                        name: 'os',
                        readOnly: true,
                        maxLength: 40
                    }, {
                        columnWidth: 0.33,
                        fieldLabel: this.app.i18n._('OS Language'),
                        name: 'oslanguage',
                        readOnly: true,
                        maxLength: 40
                    }, {
                        columnWidth: 0.333,
                        fieldLabel: this.app.i18n._('Phonenumber'),
                        name: 'phonenumber',
                        readOnly: true,
                        maxLength: 40
                    }]
                ]
            }]
        };
    }
});

/**
 * Container Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.ActiveSync.SyncDeviceEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        name: Tine.ActiveSync.SyncDeviceEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.ActiveSync.SyncDeviceEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
