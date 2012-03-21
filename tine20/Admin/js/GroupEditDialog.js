/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         refactor this (use more methods from Tine.widgets.dialog.EditRecord)
 */

/*global Ext, Tine, Locale*/

Ext.ns('Tine.Admin.Groups');

/**
 * @namespace   Tine.Admin.Groups
 * @class       Tine.Admin.Groups.EditDialog
 * @extends     Tine.widgets.dialog.EditRecord
 */
Tine.Admin.Groups.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    /**
     * var group
     */
    group: null,

    windowNamePrefix: 'groupEditWindow_',
    
    id: 'groupDialog',
    layout: 'fit',
    border: false,
    labelWidth: 120,
    labelAlign: 'top',
    
    handlerApplyChanges: function(button, event, closeWindow) {
        var form = this.getForm();
        
        if (form.isValid()) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Memberships'));
            
            // get group members
            var groupMembers = [];
            this.membersStore.each(function (record) {
                groupMembers.push(record.id);
            });
            
            // update record with form data               
            form.updateRecord(this.group);

            /*********** save group members & form ************/
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveGroup', 
                    groupData: this.group.data,
                    groupMembers: groupMembers
                },
                success: function (response) {
                    /*
                    if(window.opener.Tine.Admin.Groups) {
                        window.opener.Tine.Admin.Groups.Main.reload();
                    }
                    */
                    this.fireEvent('update', Ext.util.JSON.encode(this.group.data));
                    if (closeWindow === true) {
                        //window.close();
                        this.window.close();
                    } else {
                        this.onRecordLoad(response);
                    }
                    Ext.MessageBox.hide();
                },
                failure: function (result, request) {
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save group.'));
                },
                scope: this 
            });
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },
    
    /**
     * function updateRecord
     */
    updateRecord: function (groupData) {
        // if groupData is empty (=array), set to empty object because array won't work!
        if (groupData.length === 0) {
            groupData = {};
        }
        this.group = new Tine.Admin.Model.Group(groupData, groupData.id ? groupData.id : 0);
        
        // tweak, as group members are not in standard form cycle yet
        this.membersStore.loadData(this.group.get('groupMembers'));
    },

    /**
     * function updateToolbarButtons
     * 
     */
    updateToolbarButtons: function (rights) {
    },
    
    /**
     * function getFormContents
     * 
     */
    getFormContents: function () {
        var editGroupDialog = {
            layout: 'border',
            border: false,
            width: 600,
            height: 500,
            items: [{
                region: 'north',
                xtype: 'columnform',
                border: false,
                autoHeight: true,
                items: [[{
                    columnWidth: 1,
                    xtype: 'textfield',
                    fieldLabel: this.translation.gettext('Group Name'), 
                    name: 'name',
                    anchor: '100%',
                    allowBlank: false
                }], [{
                    columnWidth: 1,
                    xtype: 'textarea',
                    name: 'description',
                    fieldLabel: this.translation.gettext('Description'),
                    grow: false,
                    preventScrollbars: false,
                    anchor: '100%',
                    height: 60
                }], [{
                    columnWidth: 0.5,
                    xtype: 'combo',
                    fieldLabel: this.translation.gettext('Visibility'),
                    name: 'visibility',
                    mode: 'local',
                    triggerAction: 'all',
                    allowBlank: false,
                    editable: false,
                    store: [['displayed', this.translation.gettext('Display in addressbook')], ['hidden', this.translation.gettext('Hide from addressbook')]],
                    listeners: {
                        scope: this,
                        select: function (combo, record) {
                            // disable container_id combo if hidden
                            this.getForm().findField('container_id').setDisabled(record.data.field1 === 'hidden');
                            if(record.data.field1 === 'hidden') {
                                this.getForm().findField('container_id').clearInvalid();
                            } else {
                                this.getForm().findField('container_id').isValid();
                            }
                        }
                    }
                }, {
                    columnWidth: 0.5,
                    xtype: 'tinerecordpickercombobox',
                    fieldLabel: this.translation.gettext('Saved in Addressbook'),
                    name: 'container_id',
                    blurOnSelect: true,
                    allowBlank: false,
                    listWidth: 250,
                    recordClass: Tine.Tinebase.Model.Container,
                    recordProxy: Tine.Admin.sharedAddressbookBackend,
                    disabled: this.group.get('visibility') === 'hidden'
                }]]
            }, {
                xtype: 'tinerecordpickergrid',
                title: this.translation.gettext('Group Members'),
                store: this.membersStore,
                region: 'center',
                anchor: '100% 100%',
                showHidden: true
            }]
        };
        
        return editGroupDialog;
    },
    
    initComponent: function () {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.group = this.group ? this.group : new Tine.Admin.Model.Group({}, 0);
        
        if (this.group.id !== 0) {
            Ext.Ajax.request({
                scope: this,
                success: this.onRecordLoad,
                params: {
                    method: 'Admin.getGroup',
                    groupId: this.group.id
                }
            });
        } else {
            this.group = new Tine.Admin.Model.Group(Tine.Admin.Model.Group.getDefaultData(), 0);
        }
                
        this.membersStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Account
        });
        
        this.items = this.getFormContents();
        Tine.Admin.Groups.EditDialog.superclass.initComponent.call(this);
    },
    
    onRender: function (ct, position) {
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);
        
        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [{
            key: [10, 13], // ctrl + return
            ctrl: true,
            fn: this.handlerApplyChanges.createDelegate(this, [true], true),
            scope: this
        }]);

        this.loadMask = new Ext.LoadMask(ct, {msg: String.format(_('Transferring {0}...'), this.translation.gettext('Group'))});
        
        if (this.group.id !== 0) {
            this.loadMask.show();
        } else {
            this.window.setTitle(this.translation.gettext('Add new group'));
            this.getForm().loadRecord(this.group);
        }
    },
    
    onRecordLoad: function (response) {
        this.getForm().findField('name').focus(false, 350);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);

        if (! this.group.id) {
            this.window.setTitle(this.translation.gettext('Add new group'));
        } else {
            this.window.setTitle(String.format(this.translation.gettext('Edit Group "{0}"'), this.group.get('name')));
        }

        this.getForm().loadRecord(this.group);
        this.updateToolbarButtons();
        
        this.loadMask.hide();
    }
});


/**
 * Groups Edit Popup
 */
Tine.Admin.Groups.EditDialog.openWindow = function (config) {
    config.group = config.group ? config.group : new Tine.Admin.Model.Group({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 600,
        name: Tine.Admin.Groups.EditDialog.prototype.windowNamePrefix + config.group.id,
        contentPanelConstructor: 'Tine.Admin.Groups.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
