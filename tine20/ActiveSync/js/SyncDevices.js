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
 * SyncDevices 'mainScreen'
 * 
 * @static
 */
Tine.ActiveSync.syncdevices.show = function () {
    var app = Tine.Tinebase.appMgr.get('ActiveSync');
    if (! Tine.ActiveSync.syncDevicesGridPanel) {
        Tine.ActiveSync.syncDevicesGridPanel = new Tine.ActiveSync.SyncDevicesGridPanel({
            app: app,
            asAdminModule: true
        });
    } else {
        Tine.ActiveSync.syncDevicesGridPanel.loadGridData.defer(100, Tine.ActiveSync.syncDevicesGridPanel, []);
    }
    
    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.ActiveSync.syncDevicesGridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.ActiveSync.syncDevicesGridPanel.actionToolbar, true);
};

/************** models *****************/
Ext.ns('Tine.ActiveSync.Model');

/**
 * Model of an account
 */
Tine.ActiveSync.Model.SyncDeviceArray = [
    { name: 'id' },
    { name: 'deviceid' },
    { name: 'devicetype' },
    { name: 'owner_id' },
    { name: 'policy_id' },
    //{ name: 'policykey' },
    { name: 'acsversion' },
    { name: 'useragent' },
    { name: 'model' },
    { name: 'imei' },
    { name: 'friendlyname' },
    { name: 'os' },
    { name: 'oslanguage' },
    { name: 'phonenumber' },
    { name: 'pinglifetime' },
    { name: 'pingfolder' },
    { name: 'remotewipe' },
    { name: 'monitor_lastping' },
    { name: 'calendarfilter_id' },
    { name: 'contactsfilter_id' },
    { name: 'emailfilter_id' },
    { name: 'tasksfilter_id' },
    { name: 'lastping', type: 'date', dateFormat: Date.patterns.ISO8601Long }
];

Tine.ActiveSync.Model.SyncDevice = Tine.Tinebase.data.Record.create(Tine.ActiveSync.Model.SyncDeviceArray, {
    appName: 'ActiveSync',
    modelName: 'SyncDevice',
    idProperty: 'id',
    titleProperty: 'deviceid',
    // ngettext('Sync Device', 'Sync Devices', n);
    recordName: 'Sync Device',
    recordsName: 'Sync Devices'
});

/**
 * returns default account data
 * 
 * @namespace Tine.ActiveSync.Model.SyncDevice
 * @static
 * @return {Object} default data
 */
Tine.ActiveSync.Model.SyncDevice.getDefaultData = function () {
    return {};
};

/************** backend *****************/

Tine.ActiveSync.syncdevicesBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'ActiveSync',
    modelName: 'SyncDevice',
    recordClass: Tine.ActiveSync.Model.SyncDevice,
    idProperty: 'id'
});
