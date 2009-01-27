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
 * Snom Template Edit Dialog
 */
Tine.Voipmanager.SnomTemplateEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    
    /**
     * @private
     */
    windowNamePrefix: 'SnomTemplateEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.SnomTemplate,
    recordProxy: Tine.Voipmanager.SnomTemplateBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
        this.onTemplateUpdate();
    	Tine.Voipmanager.SnomTemplateEditDialog.superclass.updateToolbars.call(this, record, 'id');
    },
    
    /**
     * this gets called when initializing and if a new timeaccount is chosen
     * 
     * @param {} field
     * @param {} timeaccount
     */
    onTemplateUpdate: function(field, timeaccount) {
        
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
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Name'),
                    name: 'name',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: false
                }, {
                    xtype: 'textarea',
                    name: 'description',
                    fieldLabel: this.app.i18n._('Description'),
                    grow: false,
                    preventScrollbars: false,
                    anchor: '100%',
                    height: 40
                }, {
                    xtype: 'combo',
                    fieldLabel: this.app.i18n._('Software Version'),
                    name: 'software_id',
                    id: 'software_id',
                    mode: 'local',
                    displayField: 'name',
                    valueField: 'id',
                    anchor: '100%',
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    store: new Ext.data.JsonStore({
                        storeId: 'Voipmanger_EditTemplate_Software',
                        id: 'id',
                        fields: ['id', 'name']
                    })
                }, {
                    xtype: 'combo',
                    fieldLabel: this.app.i18n._('Settings'),
                    name: 'setting_id',
                    id: 'setting_id',
                    mode: 'local',
                    displayField: 'name',
                    valueField: 'id',
                    anchor: '100%',
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    store: new Ext.data.JsonStore({
                        storeId: 'Voipmanger_EditTemplate_Settings',
                        id: 'id',
                        fields: ['id', 'name']
                    })
                }, {
                    xtype: 'combo',
                    fieldLabel: this.app.i18n._('Keylayout'),
                    name: 'keylayout_id',
                    id: 'keylayout_id',
                    mode: 'local',
                    disabled: true,
                    displayField: 'description',
                    valueField: 'id',
                    anchor: '100%',
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    store: new Ext.data.JsonStore({
                        storeId: 'Voipmanger_EditTemplate_Keylayout',
                        id: 'id',
                        fields: ['id', 'model', 'description']
                    })
                }]
        };
    }
});

/**
 * Snom Template Edit Popup
 */
Tine.Voipmanager.SnomTemplateEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Voipmanager.SnomTemplateEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomTemplateEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};