/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:$
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
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
    	Tine.Voipmanager.AsteriskVoicemailEditDialog.superclass.updateToolbars.call(this, record, 'id');
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
                title: this.app.i18n._('General'),
                layout: 'border',
                anchor: '100% 100%',
                //layoutOnTabChange: true,
                defaults: {
                    border: true,
                    frame: false
                },
                items:[{
                    region: 'center',
                    autoScroll: true,
                    autoHeight: true,
                    items: [{                
                        layout: 'form',
                        //frame: true,
                        border: false,
                        anchor: '100%',
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
                    }]
                }]
            }, {
                title: this.app.i18n._('Additional'),
                layout: 'border',
                anchor: '100% 100%',
                //layoutOnTabChange: true,
                defaults: {
                    border: true,
                    frame: false
                },
                items: [{
                    region: 'center',
                    autoScroll: true,
                    autoHeight: true,
                    items: [{                
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        items: [{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('tz'),
                                name: 'tz',
                                maxLength: 10,
                                anchor: '98%',
                                allowBlank: true
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('dialout'),
                                name: 'dialout',
                                maxLength: 10,
                                anchor: '98%',
                                allowBlank: true
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('callback'),
                                name: 'callback',
                                maxLength: 10,
                                anchor: '100%',
                                allowBlank: true
                            }]
                        }]
                    },{
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        items: [{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('sayduration'),
                                id: 'sayduration',
                                name: 'sayduration',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                 
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })                              
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'numberfield',
                                fieldLabel: this.app.i18n._('saydurationm'),
                                name: 'saydurationm',
                                maxLength: 4,
                                anchor: '98%',
                                allowBlank: true
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('attach'),
                                name: 'attach',
                                id: 'attach',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                        
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        }]
                    },{                
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        items: [{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('saycid'),
                                name: 'saycid',
                                id: 'saycid',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                                
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        },{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('review'),
                                name: 'review',
                                id: 'review',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                               
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('operator'),
                                name: 'operator',
                                id: 'operator',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                            
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        }]
                    }, {                
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        items: [{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('envelope'),
                                name: 'envelope',
                                id: 'envelope',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                        
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('sendvoicemail'),
                                name: 'sendvoicemail',
                                id: 'sendvoicemail',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                               
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        },{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('delete'),
                                name: 'delete',
                                id: 'delete',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                             
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        }]
                    },{
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        items: [{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('nextaftercmd'),
                                name: 'nextaftercmd',
                                id: 'nextaftercmd',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                             
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        },{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('forcename'),
                                name: 'forcename',
                                id: 'forcename',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                              
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        },{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('forcegreetings'),
                                name: 'forcegreetings',
                                id: 'forcegreetings',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                             
                                        ['1', this.app.i18n._('on')],
                                        ['0', this.app.i18n._('off')]
                                    ]
                                })
                            }]
                        }]
                    },{                
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        items: [{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('hidefromdir'),
                                name: 'hidefromdir',
                                id: 'hidefromdir',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: [                                                                        
                                    ['1', this.app.i18n._('on')],
                                    ['0', this.app.i18n._('off')]
                                ]
                            }]
                        }]
                    }]
                }]  
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