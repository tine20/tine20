/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    evalGrants: false,
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            layout: 'form',
            border: false,
            defaults: {
                anchor: '100%'
            },
            items:[{
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Name'),
                    name: 'name',
                    maxLength: 80,
                    allowBlank: false
                }, {
                    xtype: 'textarea',
                    name: 'description',
                    fieldLabel: this.app.i18n._('Description'),
                    grow: false,
                    preventScrollbars: false,
                    height: 40
                }, {
                    xtype:'reccombo',
                    name: 'software_id',
                    fieldLabel: this.app.i18n._('Software Version'),
                    displayField: 'name',
                    store: new Ext.data.Store({
                        fields: Tine.Voipmanager.Model.SnomSoftware,
                        proxy: Tine.Voipmanager.SnomSoftwareBackend,
                        reader: Tine.Voipmanager.SnomSoftwareBackend.getReader(),
                        remoteSort: true,
                        sortInfo: {field: 'name', dir: 'ASC'}                        
                    }),
                    allowBlank: false
                }, {
                    xtype:'reccombo',
                    name: 'setting_id',
                    fieldLabel: this.app.i18n._('Settings'),
                    displayField: 'name',
                    store: new Ext.data.Store({
                        fields: Tine.Voipmanager.Model.SnomSetting,
                        proxy: Tine.Voipmanager.SnomSettingBackend,
                        reader: Tine.Voipmanager.SnomSettingBackend.getReader(),
                        remoteSort: true,
                        sortInfo: {field: 'name', dir: 'ASC'}                        
                    }),
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: this.app.i18n._('Keylayout'),
                    name: 'keylayout_id',
                    mode: 'local',
                    disabled: true,
                    displayField: 'description',
                    valueField: 'id',
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
        width: 500,
        height: 350,
        name: Tine.Voipmanager.SnomTemplateEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomTemplateEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
