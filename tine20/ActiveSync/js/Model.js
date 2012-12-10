/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Ext.ns('Tine.ActiveSync.Model');


/**
 * @namespace   Tine.ActiveSync.Model
 * @class       Tine.ActiveSync.Model.Device
 * @extends     Tine.Tinebase.data.Record
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * Device record definition
 */
Tine.ActiveSync.Model.Device = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.modlogFields.concat([
    { name: 'id' },
    { name: 'deviceid'},
    { name: 'devicetype' },
    { name: 'policykey' },
    { name: 'owner_id' },
    { name: 'acsversion' },
    { name: 'useragent' },
    { name: 'policy_id' },
    { name: 'pinglifetime', type: 'number' },
    { name: 'remotewipe', type: 'number' },
    { name: 'pingfolder' },
    { name: 'model' },
    { name: 'imei' },
    { name: 'friendlyname' },
    { name: 'os'},
    { name: 'oslanguage' },
    { name: 'phonenumber' }
]), {
    appName: 'ActiveSync',
    modelName: 'Device',
    idProperty: 'id',
    titleProperty: 'friendlyname',
    // ngettext('Device', 'Devices', n); gettext('Devices');
    recordName: 'Device',
    recordsName: 'Devices',
    
    /**
     * returns title of this record
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.get('friendlyname') || this.get('useragent');
    }
});


/**
 * @namespace   Tine.ActiveSync.Model
 * @class       Tine.ActiveSync.Model.Device
 * @extends     Tine.Tinebase.data.Record
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * get content type of app
 * 
 * @static
 * @param {String} appName
 * @return {String}
 */
Tine.ActiveSync.Model.getContentClass = function(appName) {
    switch(appName) {
        case 'Calendar'   : return 'Calendar';
        case 'Addressbook': return 'Contacts';
        case 'Felamimail' : return 'Email';
        case 'Tasks'      : return 'Tasks';
        default: throw new Ext.Error('no contentClass for this app');
    }
};

/**
 * @namespace   Tine.ActiveSync.Model
 * @class       Tine.ActiveSync.Model.DeviceJsonBackend
 * @extends     Tine.Tinebase.data.RecordProxy
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * JSON backend for devices
 */
Tine.ActiveSync.Model.DeviceJsonBackend = Ext.extend(Tine.Tinebase.data.RecordProxy, {
    
    /**
     * Creates a recuring event exception
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @param {Boolean} deleteInstance
     * @param {Boolean} deleteAllFollowing
     * @param {Object} options
     * @return {String} transaction id
     */
    setDeviceContentFilter: function(device, contentClass, filterId) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.setDeviceContentFilter';
        p.deviceId = event.data;
        p.contentClass = contentClass;
        p.filterId = filterId;
        
        return this.doXHTTPRequest(options);
    }
});
