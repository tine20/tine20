/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        make 'additional' tab work
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
    recordProxy: Tine.Voipmanager.AsteriskVoicemailBackend,
    evalGrants: false,
    
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
            deferredRender: false,
            items:[{
                title: this.app.i18n._('General'),
                frame: true,
                border: false,
                layout: 'form',
                items: [{
                      xtype: 'textfield',
                      fieldLabel: this.app.i18n._('Mailbox'),
                      name: 'mailbox',
                      maxLength: 11,
                      anchor: '100%',
                      allowBlank: false
                  }, {
                      xtype: 'combo',
                      fieldLabel: this.app.i18n._('Context'),
                      name: 'context_id',
                      displayField: 'name',
                      valueField: 'id',
                      anchor: '100%',
                      triggerAction: 'all',
                      editable: false,
                      forceSelection: true,
                      store: new Ext.data.Store({
                        fields: Tine.Voipmanager.Model.AsteriskContext,
                        proxy: Tine.Voipmanager.AsteriskContextBackend,
                        remoteSort: true,
                        sortInfo: {field: 'name', dir: 'ASC'}
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
            }, {
                title: this.app.i18n._('Additional'),
                frame: true,
                border: false,
                xtype: 'columnform',
                formDefaults: {
                    xtype:'combo',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: .333,
                    mode: 'local',
                        displayField: 'value',
                        valueField: 'id',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        value: '1',
                        store: [                                                                 
                            ['1', this.app.i18n._('on')],
                            ['0', this.app.i18n._('off')]
                        ]
                },
                items: [
                    [{
                        xtype:'textfield',
                        fieldLabel: this.app.i18n._('tz'),
                        name: 'tz',
                        maxLength: 10,
                        editable: true
                    }, {
                        xtype:'textfield',
                        fieldLabel: this.app.i18n._('dialout'),
                        name: 'dialout',
                        maxLength: 10,
                        editable: true
                    }, {
                        xtype:'textfield',
                        fieldLabel: this.app.i18n._('callback'),
                        name: 'callback',
                        maxLength: 10,
                        editable: true
                    }], [{
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('sayduration'),
                        name: 'sayduration'
                    }, {
                        xtype: 'numberfield',
                        fieldLabel: this.app.i18n._('saydurationm'),
                        name: 'saydurationm',
                        maxLength: 4
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('attach'),
                        name: 'attach'
                    }], [{
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('saycid'),
                        name: 'saycid'
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('review'),
                        name: 'review'
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('operator'),
                        name: 'operator'
                    }], [{
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('envelope'),
                        name: 'envelope'
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('sendvoicemail'),
                        name: 'sendvoicemail'
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('delete'),
                        name: 'delete'
                    }], [{
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('nextaftercmd'),
                        name: 'nextaftercmd'
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('forcename'),
                        name: 'forcename'
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('forcegreetings'),
                        name: 'forcegreetings'
                    }], [{
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('hidefromdir'),
                        name: 'hidefromdir'
                    }]
                ]
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