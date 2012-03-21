/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philip Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         refactor this (don't use Ext.getCmp, etc.)
 */

/*global Ext, Tine, Locale*/

Ext.ns('Tine.Admin.Tags');

/**
 * @namespace   Tine.Admin.Tags
 * @class       Tine.Admin.Tags.EditDialog
 * @extends     Tine.widgets.dialog.EditRecord
 */
Tine.Admin.Tags.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    
    /**
     * var tag
     */
    tag: null,

    windowNamePrefix: 'AdminTagEditDialog_',
    id: 'tagDialog',
    layout: 'fit',
    border: false,
    labelWidth: 120,
    labelAlign: 'top',
    
    handlerApplyChanges: function (button, event, closeWindow) {
        var form = this.getForm();
        
        if (form.isValid()) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Tag'));
            
            var tag = this.tag;
            
            // fetch rights
            tag.data.rights = [];
            this.rightsStore.each(function (item) {
                tag.data.rights.push(item.data);
            });
            
            // fetch contexts
            tag.data.contexts = [];
            var anycontext = true;
            var contextPanel = Ext.getCmp('adminSharedTagsContextPanel');
            contextPanel.getRootNode().eachChild(function (node) {
                if (node.attributes.checked) {
                    tag.data.contexts.push(node.id);
                } else {
                    anycontext = false;
                }
            });
            if (anycontext) {
                tag.data.contexts = ['any'];
            }
            
            form.updateRecord(tag);
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveTag', 
                    tagData: tag.data
                },
                success: function (response) {
                    //if(this.window.opener.Tine.Admin.Tags) {
                    //    this.window.opener.Tine.Admin.Tags.Main.reload();
                    //}
                    this.fireEvent('update', Ext.util.JSON.encode(this.tag.data));
                    Ext.MessageBox.hide();
                    if (closeWindow === true) {
                        this.window.close();
                    } else {
                        this.onRecordLoad(response);
                    }
                },
                failure: function (result, request) {
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save tag.'));
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
    updateRecord: function (tagData) {
        // if tagData is empty (=array), set to empty object because array wont work!
        if (tagData.length === 0) {
            tagData = {};
        }
        this.tag = new Tine.Tinebase.Model.Tag(tagData, tagData.id ? tagData.id : 0);
        
        if (! tagData.rights) {
            tagData.rights = [{
                tag_id: '', //todo!
                account_name: 'Anyone',
                account_id: 0,
                account_type: 'anyone',
                view_right: true,
                use_right: true
            }];
        }
        
        this.rightsStore.loadData({
            results:    tagData.rights,
            totalcount: tagData.rights.length
        });
        
        this.anyContext = ! tagData.contexts || tagData.contexts.indexOf('any') > -1;
        this.createTreeNodes(tagData.appList);
        this.getForm().loadRecord(this.tag);
    },

    /**
     * function updateToolbarButtons
     */
    updateToolbarButtons: function (rights) {
       /* if(_rights.editGrant === true) {
            Ext.getCmp('tagDialog').action_saveAndClose.enable();
            Ext.getCmp('tagDialog').action_applyChanges.enable();
        }
       */

    },
    
    createTreeNodes: function (appList) {
        // clear old childs
        var toRemove = [];
        this.rootNode.eachChild(function (node) {
            toRemove.push(node);
        });
        
        for (var i = 0, j = appList.length; i < j; i += 1) {
            // don't duplicate tree nodes on 'apply changes'
            toRemove[i] ? toRemove[i].remove() : null;
            
            var appData = appList[i];
            if (appData.name === 'Tinebase') {
                continue;
            }
            // get translated app title
            var app = Tine.Tinebase.appMgr.get(appData.name),
                appTitle = (app) ? app.getTitle() : appData.name;
            
            this.rootNode.appendChild(new Ext.tree.TreeNode({
                text: appTitle,
                id: appData.id,
                checked: this.anyContext || this.tag.get('contexts').indexOf(appData.id) > -1,
                leaf: true,
                iconCls: 'x-tree-node-leaf-checkbox'
            }));
        }
    },
    /**
     * function display
     */
    getFormContents: function () {

        this.rootNode = new Ext.tree.TreeNode({
            text: this.translation.gettext('Allowed Contexts'),
            expanded: true,
            draggable: false,
            allowDrop: false
        });
        var contextPanel = new Ext.tree.TreePanel({
            title: this.translation.gettext('Context'),
            id: 'adminSharedTagsContextPanel',
            rootVisible: true,
            border: false,
            root: this.rootNode
        });
        // sort nodes in context panel by text property
        var treeSorter = new Ext.tree.TreeSorter(contextPanel, {
            folderSort: true,
            dir: "asc"
        });
        
        this.rightsPanel = new Tine.widgets.account.PickerGridPanel({
            title: this.translation.gettext('Account Rights'),
            store: this.rightsStore,
            recordClass: Tine.Admin.Model.TagRight,
            hasAccountPrefix: true,
            selectType: 'both',
            selectTypeDefault: 'group',
            showHidden: true,
            configColumns: [
                new Ext.ux.grid.CheckColumn({
                    header: this.translation.gettext('View'),
                    dataIndex: 'view_right',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.translation.gettext('Use'),
                    dataIndex: 'use_right',
                    width: 55
                })
            ]
        });

        var editTagDialog = {
            layout: 'border',
            border: false,
            items: [{
                region: 'north',
                xtype: 'columnform',
                border: false,
                autoHeight: true,
                items: [[{
                    columnWidth: 0.3,
                    fieldLabel: this.translation.gettext('Tag Name'), 
                    name: 'name',
                    allowBlank: false,
                    maxLength: 40
                }, {
                    columnWidth: 0.6,
                    name: 'description',
                    fieldLabel: this.translation.gettext('Description'),
                    anchor: '100%',
                    maxLength: 256
                }, {
                    xtype: 'colorfield',
                    columnWidth: 0.1,
                    fieldLabel: this.translation.gettext('Color'),
                    name: 'color'
                }]]
            }, {
                region: 'center',
                xtype: 'tabpanel',
                activeTab: 0,
                deferredRender: false,
                defaults: { autoScroll: true },
                border: true,
                plain: true,                    
                items: [
                    this.rightsPanel, 
                    contextPanel
                ]
            }]
        };
        
        return editTagDialog;
    },
    
    initComponent: function () {
        this.tag = this.tag ? this.tag : new Tine.Tinebase.Model.Tag({}, 0);
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Admin.getTag',
                tagId: this.tag.id
            }
        });
        
        this.rightsStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'account_id',
            fields: Tine.Admin.Model.TagRight
        });
        
        this.items = this.getFormContents();
        Tine.Admin.Tags.EditDialog.superclass.initComponent.call(this);
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

        this.loadMask = new Ext.LoadMask(ct, {msg: String.format(_('Transferring {0}...'), this.translation.gettext('Tag'))});
        this.loadMask.show();
    },
    
    onRecordLoad: function (response) {
        this.getForm().findField('name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.tag.id) {
            this.window.setTitle(this.translation.gettext('Add New Tag'));
        } else {
            this.window.setTitle(String.format(this.translation._('Edit Tag "{0}"'), this.tag.get('name')));
        }
        
        this.loadMask.hide();
    }     
});

/**
 * Admin Tag Edit Popup
 */
Tine.Admin.Tags.EditDialog.openWindow = function (config) {
    config.tag = config.tag ? config.tag : new Tine.Tinebase.Model.Tag({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 650,
        height: 400,
        name: Tine.Admin.Tags.EditDialog.prototype.windowNamePrefix + config.tag.id,
        contentPanelConstructor: 'Tine.Admin.Tags.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
