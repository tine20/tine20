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
 * Asterisk Voicemail Edit Dialog
 */
Tine.Voipmanager.AsteriskVoicemailEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    
    /**
     * @private
     */
    windowNamePrefix: 'AsteriskVoicemailEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.AsteriskVoicemail,
    recordProxy: Tine.Voipmanager.AsteriskVoicemailtBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
        this.onContextUpdate();
    	Tine.Voipmanager.AsteriskVoicemailEditDialog.superclass.updateToolbars.call(this, record, 'id');
    },
    
    /**
     * this gets called when initializing and if a new timeaccount is chosen
     * 
     * @param {} field
     * @param {} timeaccount
     */
    onVoicemailUpdate: function(field, timeaccount) {
        
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
                  fieldLabel: this.app.i18n._('mailbox'),
                  name: 'mailbox',
                  maxLength: 11,
                  anchor: '100%',
                  allowBlank: false
              }, {
                  xtype: 'combo',
                  fieldLabel: this.app.i18n._('Context'),
                  name: 'context',
                  mode: 'local',
                  displayField: 'name',
                  valueField: 'name',
                  anchor: '100%',
                  triggerAction: 'all',
                  editable: false,
                  forceSelection: true,
                  store: new Ext.data.JsonStore({
                      storeId: 'Voipmanger_EditVoicemail_Context',
                      id: 'id',
                      fields: ['id', 'name']
                  })
              }, {
                  xtype: 'textfield',
                  fieldLabel: this.app.i18n._('Name'),
                  name: 'fullname',
                  maxLength: 150,
                  anchor: '100%',
                  allowBlank: false
              }, {
                  xtype: 'numberfield',
                  fieldLabel: this.app.i18n._('Password'),
                  name: 'password',
                  maxLength: 5,
                  anchor: '100%',
                  allowBlank: false
              }, {
                  xtype: 'textfield',
                  vtype: 'email',
                  fieldLabel: this.app.i18n._('email'),
                  name: 'email',
                  maxLength: 50,
                  anchor: '100%'                        
              }, {
                  xtype: 'textfield',
                  fieldLabel: this.app.i18n._('pager'),
                  name: 'pager',
                  maxLength: 50,
                  anchor: '100%'
              }]
        };
    }
});

/**
 * Asterisk Voicemail Edit Popup
 */
Tine.Voipmanager.AsteriskVoicemailEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Voipmanager.AsteriskVoicemailEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.AsteriskVoicemailEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};