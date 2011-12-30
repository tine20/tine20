/*
 * Tine 2.0
 * 
 * @package     Projects
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Projects');

Tine.Projects.AddToProjectPanel = Ext.extend(Ext.FormPanel, {
    appName : 'Projects',
    
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

        Tine.Projects.AddToProjectPanel.superclass.initComponent.call(this);
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
        Tine.Projects.AddToProjectPanel.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [ {
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
            this.searchBox.markInvalid(this.app.i18n._('Please choose the Project to add to'));
            valid = false;
        }
        if(this.chooseRoleBox.getValue() == '') {
            this.chooseRoleBox.markInvalid(this.app.i18n._('Please select the Attenders\' role'));
            valid = false;
        }
        
        return valid;
    },
    
    onUpdate: function() {
        if(this.isValid()) {          
            var p = new Tine.Projects.Model.Project({id: this.searchBox.getValue()});
//            var be = new Tine.Projects.recordBackend();
//            var record = Tine.Projects.recordBackend.load(p);
//            Tine.log.debug('RECORD',record);
            Tine.log.debug('RECORD',p);
//            Tine.log.debug('BE',be);
            var window = Tine.Projects.ProjectEditDialog.openWindow({record: p, selectedRecords: Ext.encode(this.attendee), attendeeRole: this.chooseRoleBox.getValue()});
        }
        
        window.on('close', function() {
                this.onCancel();
        },this);
    },
    
    getFormItems : function() {
        this.searchBox = new Tine.Projects.SearchCombo({fieldLabel: this.app.i18n._('Select Project')});
        
        var records = [];
        
        Ext.each(this.app.getRegistry().get('config')['projectAttendeeRole'].value.records, function(el) {
            var label = el.i18nValue ? el.i18nValue : el.value;
            records.push([el.id, label, el.icon]);
        });
        
        this.chooseRoleBox = new Ext.form.ComboBox({
            autoWidth: true,
            mode: 'local',
            fieldLabel: this.app.i18n._('Select Role'),
            valueField: 'id',
            displayField: 'role',
            forceSelection: true,
            itemSelector: 'div.search-item',
            tpl: new Ext.XTemplate(
                '<tpl for="."><div class="search-item" style="border: 1px dotted white">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td width="20%">',                   
                                '<img src="{values.icon}" />',
                            '</td>',
                            '<td width="80%">',
                                '{values.role}',
                            '</td>',
                        '</td></tr>',
                    '</table>',
                '</div></tpl>'
            ),
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: ['id','role','icon'],
                data: records
            })
        });
        
        return {
            border : false,
            frame : true,
            layout : 'form',

            items : [ {
                region : 'center',
                layout : {
                    align: 'stretch',
                    type: 'vbox'
                }

            }, this.searchBox, this.chooseRoleBox]
        };
    }
});

Tine.Projects.AddToProjectPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        title : Tine.Tinebase.appMgr.get('Projects').i18n._('Choose Project'),
        width : 250,
        height : 150,
        contentPanelConstructor : 'Tine.Projects.AddToProjectPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};