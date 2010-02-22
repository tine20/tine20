/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Asterisk SipPeer Edit Dialog
 */
Tine.Voipmanager.AsteriskSipPeerEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    
    /**
     * @private
     */
    windowNamePrefix: 'AsteriskSipPeerEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.AsteriskSipPeer,
    recordProxy: Tine.Voipmanager.AsteriskSipPeerBackend, 
    evalGrants: false,
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        
        // TODO add Tine.Voipmanager.CallForwardPanel
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            items:[{
                title: this.app.i18n._('General'),
                border: false,
                frame: true,
                anchor: '100%',
                xtype: 'columnform',
                items: [[{
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Name'),
                    name: 'name',
                    maxLength: 80,
                    anchor: '98%',
                    allowBlank: false
                }, {
                    xtype:'reccombo',
                    name: 'context_id',
                    fieldLabel: this.app.i18n._('Context'),
                    valueField: 'id',
                    displayField: 'name',
                    allowBlank: false,
                    store: new Ext.data.Store({
                        fields: Tine.Voipmanager.Model.AsteriskContext,
                        proxy: Tine.Voipmanager.AsteriskContextBackend,
                        reader: Tine.Voipmanager.AsteriskContextBackend.getReader(),
                        remoteSort: true,
                        sortInfo: {field: 'name', dir: 'ASC'}                        
                    })
                }, {
                    xtype: 'combo',
                    fieldLabel: this.app.i18n._('Type'),
                    name: 'type',
                    mode: 'local',
                    anchor: '98%',
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    value: 'peer',
                    store: [
                        ['friend', this.app.i18n._('friend')], 
                        ['user', this.app.i18n._('user')],
                        ['peer', this.app.i18n._('peer')]
                    ]                 
                }], [{
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Secret'),
                    name: 'secret',
                    maxLength: 80,
                    anchor: '98%',
                    allowBlank: false
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Callerid'),
                    name: 'callerid',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: true
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Mailbox'),
                    name: 'mailbox',
                    maxLength: 50,
                    anchor: '98%',
                    allowBlank: true
                }], [{
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Callgroup'),
                    name: 'callgroup',
                    maxLength: 10,
                    anchor: '98%',
                    allowBlank: true
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Pickup group'),
                    name: 'pickupgroup',
                    maxLength: 10,
                    anchor: '98%',
                    allowBlank: true
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Accountcode'),
                    name: 'accountcode',
                    maxLength: 20,
                    anchor: '98%',
                    allowBlank: true
                }], [{
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Language'),
                    name: 'language',
                    maxLength: 2,
                    anchor: '98%',
                    allowBlank: true
                }, {
                    xtype: 'combo',
                    fieldLabel: this.app.i18n._('NAT'),
                    name: 'nat',
                    mode: 'local',
                    anchor: '98%',
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    value: 'no',
                    store: [['no', this.app.i18n._('off')], ['yes', this.app.i18n._('on')]] 
                }, {
                    xtype: 'combo',
                    fieldLabel: this.app.i18n._('Qualify'),
                    name: 'qualify',
                    mode: 'local',
                    anchor: '98%',
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    value: 'no',
                    store: [['no', this.app.i18n._('off')], ['yes', this.app.i18n._('on')]] 
                }]]
            }]
        };
    }
});

/**
 * Asterisk SipPeer Edit Popup
 */
Tine.Voipmanager.AsteriskSipPeerEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Voipmanager.AsteriskSipPeerEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.AsteriskSipPeerEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};