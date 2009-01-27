/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:$
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Asterisk Meetme Edit Dialog
 */
Tine.Voipmanager.AsteriskMeetmeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    
    /**
     * @private
     */
    windowNamePrefix: 'AsteriskMeetmeEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.AsteriskMeetme,
    recordProxy: Tine.Voipmanager.AsteriskMeetmeBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
        this.onMeetmeUpdate();
    	Tine.Voipmanager.AsteriskMeetmeEditDialog.superclass.updateToolbars.call(this, record, 'id');
    },
    
    /**
     * this gets called when initializing and if a new timeaccount is chosen
     * 
     * @param {} field
     * @param {} timeaccount
     */
    onMeetmeUpdate: function(field, timeaccount) {
        
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() { 
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            items:[{
                    xtype: 'numberfield',
                    fieldLabel: this.app.i18n._('confno'),
                    name: 'confno',
					id: 'confno',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: false
                }, {
                    xtype: 'numberfield',
                    fieldLabel: this.app.i18n._('pin'),
                    name: 'pin',
					id: 'pin',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: false
                },{
                    xtype: 'numberfield',
                    fieldLabel: this.app.i18n._('adminpin'),
                    name: 'adminpin',
					id: 'adminpin',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: false
                }]
        };
    }
});

/**
 * Asterisk Meetme Edit Popup
 */
Tine.Voipmanager.AsteriskMeetmeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Voipmanager.AsteriskMeetmeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.AsteriskMeetmeEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};