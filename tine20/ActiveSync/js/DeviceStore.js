/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.ActiveSync');

/**
 * @namespace   Tine.ActiveSync
 * @class       Tine.ActiveSync.DeviceStore
 * @extends     Ext.data.ArrayStore
 * 
 * <p>Store for Device Records</p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.ActiveSync.DeviceStore
 */
Tine.ActiveSync.DeviceStore = Ext.extend(Ext.data.ArrayStore, {
    
});


/**
 * @namespace   Tine.ActiveSync
 * 
 * get store of all device records
 * 
 * @static
 * @sigleton
 * @return {DeviceStore}
 */
Tine.ActiveSync.getDeviceStore = function() {
    if (! Tine.ActiveSync.deviceStore) {
        // create store
        Tine.ActiveSync.deviceStore = new Tine.ActiveSync.DeviceStore({
            fields: Tine.ActiveSync.Model.Device.getFieldDefinitions(),
            sortInfo: {field: 'friendlyname', direction: 'ASC'}
        });
        
        var app = Tine.Tinebase.appMgr.get('ActiveSync'),
            registry = app ? app.getRegistry() : null
            recordsData = registry ? registry.get('userDevices') : [];
        
        // populate store
        Ext.each(recordsData, function(data) {
            var r = new Tine.ActiveSync.Model.Device(data);
            Tine.ActiveSync.deviceStore.addSorted(r);
        }, this);
    }
    
    return Tine.ActiveSync.deviceStore;
}