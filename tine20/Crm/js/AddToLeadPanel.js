/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.AddToLeadPanel
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

Tine.Crm.AddToLeadPanel = Ext.extend(Ext.FormPanel, {
    appName : 'Crm',
    
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',    
    
    labelAlign : 'top',

    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,
    
    initComponent: function() {
         
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
            
        Tine.log.debug('initComponent: appName: ', this.appName);
        Tine.log.debug('initComponent: app: ', this.app);

        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        
        // get items for this dialog
        this.items = this.getFormItems();

        Tine.Crm.AddToLeadPanel.superclass.initComponent.call(this);
    },
    
    initActions: function() {
        this.action_cancel = new Ext.Action({
            text : this.app.i18n._('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });
        
        this.action_update = new Ext.Action({
            text : this.app.i18n._('OK'),
            minWidth : 70,
            scope : this,
            handler : this.onUpdate,
            iconCls : 'action_saveAndClose'
        });
    },
    
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_update ];
    },  
    
    onRender : function(ct, position) {
        Tine.Crm.AddToLeadPanel.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onSend,
            scope : this
        } ]);

    },
       
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },
    
    isValid: function() {
        
        var valid = true;
        if(this.searchBox.getValue() == '') {
            this.searchBox.markInvalid(this.app.i18n._('Please choose the Lead to add the contacts to'));
            valid = false;
        }
        
        if(this.chooseRoleBox.getValue() == '') {
            this.chooseRoleBox.markInvalid(this.app.i18n._('Please select the attenders\' role'));
            valid = false;
        }
        
        return valid;
    },
    
    onUpdate: function() {
        if(this.isValid()) {          
            var p = new Tine.Crm.Model.Lead({id: this.searchBox.getValue()});
            var window = Tine.Crm.LeadEditDialog.openWindow({record: p, additionalContacts: Ext.encode(this.attendee), additionalContactsRole: this.chooseRoleBox.getValue()});
            window.on('close', function() {
                this.onCancel();
            },this);
        }
    },
    
    getFormItems : function() {
                
        return {
            border : false,
            frame : false,
            layout : 'border',

            items : [ {
                region : 'center',
                border: false,
                frame:  false,
                layout : {
                    align: 'stretch',
                    type: 'vbox'
                },
                items: [{
                    layout:  'form',
                    margins: '10px 10px',
                    border:  false,
                    frame:   false,
                    items: [ 
                        {
                            xtype: 'crmleadpickercombobox',
                            fieldLabel: this.app.i18n._('Lead'),
                            emptyText: this.app.i18n._('Select Lead'),
                            anchor : '100% 100%',
                            ref: '../../../searchBox'
                        }, {
                            fieldLabel: this.app.i18n._('Role'),
                            amptyText: this.app.i18n._('Select Role'),
                            xtype: 'leadcontacttypecombo',
                            ref : '../../../chooseRoleBox',
                            anchor : '100% 100%'
                    }] 
                }]
            }]
        };
    }
});

Tine.Crm.AddToLeadPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        title : String.format(Tine.Tinebase.appMgr.get('Crm').i18n._('Adding {0} Contacts to lead'), config.attendee.length),
        width : 250,
        height : 150,
        contentPanelConstructor : 'Tine.Crm.AddToLeadPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};