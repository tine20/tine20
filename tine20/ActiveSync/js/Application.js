/*
 * Tine 2.0
 *
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Ext.ns('Tine.ActiveSync');

/**
 * @namespace   Tine.ActiveSync
 * @class       Tine.ActiveSync.Application
 * @extends     Tine.Tinebase.Application
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.ActiveSync.Application = Ext.extend(Tine.Tinebase.Application, {
    
    hasMainScreen: false,
    
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.gettext('Active Sync');
    },
    
    /**
     * returns additional items for persitentn filter context menu
     * 
     * @todo rework this to be event/hook 
     * 
     * @param {Tine.widgets.persistentfilter.PickerPanel} picker
     * @param {Tine.widgets.persistentfilter.model.PersistentFilter} filter
     */
    getPersistentFilterPickerCtxItems: function(picker, filter) {
        var items = [];
        
        if (picker.app.appName.match(/Addressbook|Calendar|Email|Tasks/)) {
            var devices =  Tine.ActiveSync.getDeviceStore();
            console.log(devices);
            var menuItems = ['<b class="x-menu-title">' + this.i18n._('Select a Device') +'</b>'];
            
            devices.each(function(device) {
                var contentClass = Tine.ActiveSync.Model.getContentClass(picker.app.appName);
                
                menuItems.push({
                    text: Ext.util.Format.htmlEncode(device.getTitle()),
                    checked: device.get([Ext.util.Format.lowercase(contentClass) + 'filter_id']) === filter.id,
                    //iconCls: 'activesync-device-standard',
                    handler: this.setDeviceContentFilter.createDelegate(this, [device, contentClass, filter], true)
                });
            }, this);
            if (! devices.getCount()) {
                menuItems.push({
                    text: this.i18n._('No ActiveSync Device registered'),
                    disabled: true,
                    checked: false,
                    handler: Ext.emptyFn
                });
            }
            
            items.push({
                text: String.format(this.i18n._('Set as {0} Filter'), this.getTitle()),
                iconCls: this.getIconCls(),
                menu: menuItems
            });
        }
        
        return items;
        
    },
    
    /**
     * persitently set filter for device
     * 
     * @param {Ext.Action} btn
     * @param {Ext.EventObject} e
     * @param {Tine.ActiveSync.Model.Device} device
     * @param {} contentClass
     * @param {Tine.widgets.persistentfilter.model.PersistentFilter} filter
     */
    setDeviceContentFilter: function(btn, e, device, contentClass, filter) {
        if (btn.checked) {
            // if btn was checked, we need to reset filter
            Tine.ActiveSync.setDeviceContentFilter(device.id, contentClass, null, function(response) {
                device.set([Ext.util.Format.lowercase(contentClass) + 'filter_id'], null);  
                
                Ext.Msg.alert(this.i18n._('Resetted Sync Filter'), String.format(
                    this.i18n._('{0} filter for device "{1}" is now "{2}"'),
                        this.getTitle(),
                        Ext.util.Format.htmlEncode(device.getTitle()),
                        this.i18n._('resetted')
                ));
                
            }, this);
        } else {
            Tine.ActiveSync.setDeviceContentFilter(device.id, contentClass, filter.id, function(response) {
                device.set([Ext.util.Format.lowercase(contentClass) + 'filter_id'], filter.id); 
                
                Ext.Msg.alert(this.i18n._('Set Sync Filter'), String.format(
                    this.i18n._('{0} filter for device "{1}" is now "{2}"'),
                        this.getTitle(),
                        Ext.util.Format.htmlEncode(device.getTitle()),
                        Ext.util.Format.htmlEncode(filter.get('name'))
                ));
            }, this);
        }
    }
});