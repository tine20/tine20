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

Ext.ns('Tine.Admin.Roles');

/**
 * @namespace   Tine.Admin.Roles
 * @class       Tine.Admin.Roles.EditDialog
 * @extends     Tine.widgets.dialog.EditRecord
 */
Tine.Admin.Roles.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {

    /**
     * @cfg {Tine.Tinebase.Model.Role}
     */
    role: null,
    
    /**
     * @property {Object} holds allRights (possible rights)
     */
    allRights: null,
    
    /**
     * @property {Ext.tree.treePanel}
     */
    rightsTreePanel: null,
    
    windowNamePrefix: 'rolesEditWindow_',
    
    layout: 'fit',
    border: false,
    id: 'roleDialog',
    labelWidth: 120,
    labelAlign: 'top',
    
    /**
     * check if right is set for application and get the record id
     * @private
     */
    getRightId: function (applicationId, right) {
        var id = false,
            result = null;
        
        this.rightsDataStore.each(function (item) {
            if (item.data.application_id === applicationId && item.data.right === right) {
                result = item.id;
                return;
            }
        });
        
        return result;
    },  
    
    handlerApplyChanges: function (button, event, closeWindow) {
        var form = this.getForm();
        
        if (form.isValid()) {
            // get role members
            var roleGrid = Ext.getCmp('roleMembersGrid');

            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Memberships'));
            
            var roleMembers = [];
            this.membersStore.each(function (record) {
                roleMembers.push(record.data);
            });

            // get role rights                
            var roleRights = [],
                rightsStore = this.rightsDataStore;
            
            rightsStore.each(function (record) {
                roleRights.push(record.data);
            });

            // update form               
            form.updateRecord(this.role);

            /*********** save role members & form ************/
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveRole', 
                    roleData: this.role.data,
                    roleMembers: roleMembers,
                    roleRights: roleRights
                },
                timeout: 300000, // 5 minutes
                success: function (response) {
                    this.fireEvent('update', Ext.util.JSON.encode(this.role.data));
                    Ext.MessageBox.hide();
                    if (closeWindow === true) {
                        this.window.close();
                    } else {
                        this.onRecordLoad(response);
                    }
                },
                failure: function (result, request) {
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save role.'));
                },
                scope: this 
            });
                
            
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },
    
    handlerDelete: function (button, event) {
        var roleIds = [this.role.id];
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Admin.deleteRoles', 
                roleIds: roleIds
            },
            text: this.translation.gettext('Deleting role...'),
            success: function (result, request) {
                if (window.opener.Tine.Admin.Roles) {
                    window.opener.Tine.Admin.Roles.Main.reload();
                }
                window.close();
            },
            failure: function (result, request) {
                Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occurred while trying to delete the role.'));
            } 
        });
    },

    updateRecord: function (roleData) {
        // if roleData is empty (=array), set to empty object because array won't work!
        if (roleData.length === 0) {
            roleData = {};
        }
        this.role = new Tine.Tinebase.Model.Role(roleData, roleData.id ? roleData.id : 0);
        
        this.membersStore.loadData(this.role.get('roleMembers'));
        this.rightsDataStore.loadData(this.role.get('roleRights'));
        this.allRights = this.role.get('allRights');
        this.createRightsTreeNodes();
    },

    /**
     * creates the rights tree
     *
     */
    initRightsTree: function () {
        this.rightsTreePanel = new Ext.tree.TreePanel({
            title: this.translation.gettext('Rights'),
            autoScroll: true,
            rootVisible: false,
            border: false
        });
        
        // sort nodes by text property
        this.treeSorter = new Ext.tree.TreeSorter(this.rightsTreePanel, {
            folderSort: true,
            dir: "asc"
        });
        
        // set the root node
        var treeRoot = new Ext.tree.TreeNode({
            text: 'root',
            draggable: false,
            allowDrop: false,
            id: 'root'
        });

        this.rightsTreePanel.setRootNode(treeRoot);
    },
    
    /**
     * create nodes for rights tree (apps + app rights)
     * 
     * @return {Ext.tree.TreePanel}
     */
    createRightsTreeNodes: function () {
        var treeRoot = this.rightsTreePanel.getRootNode();
        
        var toRemove = [];
        treeRoot.eachChild(function (node) {
            toRemove.push(node);
        });
        
        var expandNode = (this.allRights.length > 5) ? false : true;
        
        // add nodes to tree        
        for (var i = 0; i < this.allRights.length; i += 1) {
            // don't duplicate tree nodes on 'apply changes'
            var remove = toRemove[i] ? toRemove[i].remove() : null;
            this.allRights[i].text = this.translateAppTitle(this.allRights[i].text);
            var node = new Ext.tree.TreeNode(this.allRights[i]);
            node.attributes.application_id = this.allRights[i].application_id;
            node.expanded = expandNode;
            treeRoot.appendChild(node);
            
            // append children          
            if (this.allRights[i].children) {
                for (var j = 0; j < this.allRights[i].children.length; j += 1) {
                    var childData = this.allRights[i].children[j];
                    childData.leaf = true;
                    
                    // check if right is set
                    childData.checked = !!this.getRightId(this.allRights[i].application_id, childData.right);
                    childData.iconCls = "x-tree-node-leaf-checkbox";
                    var child = new Ext.tree.TreeNode(childData);
                    child.attributes.right = childData.right;
                    
                    child.on('checkchange', function (node, checked) {
                        var applicationId = node.parentNode.attributes.application_id;
                    
                        // put it in the storage or remove it                        
                        if (checked) {
                            this.rightsDataStore.add(
                                new Ext.data.Record({
                                    right: node.attributes.right,
                                    application_id: applicationId
                                })
                            );
                        } else {
                            var rightId = this.getRightId(applicationId, node.attributes.right);
                            this.rightsDataStore.remove(this.rightsDataStore.getById(rightId));
                        }   
                    }, this);
                    
                    node.appendChild(child);
                }       
            }
        }     
        
        return this.rightsTreePanel;
    },
    
    /**
     * translate and return app title
     * 
     * TODO try to generalize this fn as this gets used in Tags.js + Applications.js as well 
     *      -> this could be moved to Tine.Admin.Application after Admin js refactoring
     */
    translateAppTitle: function (appName) {
        var app = Tine.Tinebase.appMgr.get(appName);
        return (app) ? app.getTitle() : appName;
    },
    
    /**
     * returns form
     * 
     */
    getFormContents: function () {
        this.accountPickerGridPanel = new Tine.widgets.account.PickerGridPanel({
            title: this.translation.gettext('Members'),
            store: this.membersStore,
            anchor: '100% 100%',
            groupRecordClass: Tine.Admin.Model.Group,
            selectType: 'both',
            selectAnyone: false,
            selectTypeDefault: 'group',
            showHidden: true
        });
        
        this.initRightsTree();
        
        /******* THE edit dialog ********/
        
        var editRoleDialog = {
            layout: 'border',
            border: false,
            items: [{
                region: 'north',
                layout: 'form',
                border: false,
                autoHeight: true,
                items: [{
                    xtype: 'textfield',
                    fieldLabel: this.translation.gettext('Role Name'), 
                    name: 'name',
                    anchor: '100%',
                    allowBlank: false,
                    maxLength: 128
                }, {
                    xtype: 'textarea',
                    name: 'description',
                    fieldLabel: this.translation.gettext('Description'),
                    grow: false,
                    preventScrollbars: false,
                    anchor: '100%',
                    height: 60
                }]
            }, {
                xtype: 'tabpanel',
                plain: true,
                region: 'center',
                activeTab: 0,
                items: [
                    this.accountPickerGridPanel,
                    this.rightsTreePanel
                ]
            }]
        };
        
        return editRoleDialog;
    },
    
    initComponent: function () {
        this.role = this.role ? this.role : new Tine.Tinebase.Model.Role({}, 0);
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Admin.getRole',
                roleId: this.role.id
            }
        });
        
        // init role members store
        this.membersStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Account
        });
        
        // init rights store
        this.rightsDataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Admin.Roles.Right
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

        this.loadMask = new Ext.LoadMask(ct, {msg: String.format(_('Transferring {0}...'), this.translation.gettext('Role'))});
        this.loadMask.show();
    },
    
    onRecordLoad: function (response) {
        this.getForm().findField('name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.role.id) {
            this.window.setTitle(this.translation.gettext('Add New Role'));
        } else {
            this.window.setTitle(String.format(this.translation.gettext('Edit Role "{0}"'), this.role.get('name')));
        }
        
        this.getForm().loadRecord(this.role);
        
        this.loadMask.hide();
    }
    
});

/**
 * Roles Edit Popup
 */
Tine.Admin.Roles.EditDialog.openWindow = function (config) {
    config.role = config.role ? config.role : new Tine.Tinebase.Model.Role({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 600,
        name: Tine.Admin.Roles.EditDialog.prototype.windowNamePrefix + config.role.id,
        contentPanelConstructor: 'Tine.Admin.Roles.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
