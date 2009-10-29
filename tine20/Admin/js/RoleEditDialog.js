/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philip Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         refactor this (don't use Ext.getCmp, etc.)
 */
 
Ext.ns('Tine.Admin.Roles');

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
    id : 'roleDialog',
    labelWidth: 120,
    labelAlign: 'top',
    
    /**
     * check if right is set for application and get the record id
     * @private
     */
    getRightId: function(applicationId, right) {
        
        var id = false;
        var result = null;
        
        this.rightsDataStore.each(function(item){
            if (item.data.application_id == applicationId && item.data.right == right ) {
                result = item.id;
                return;
            }
        });
        
        return result;
    },  
    
     handlerApplyChanges: function(_button, _event, _closeWindow) {
        var form = this.getForm();
        
        if(form.isValid()) {
            // get role members
            var roleGrid = Ext.getCmp('roleMembersGrid');

            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Memberships'));
            
            var roleMembers = [];
            this.membersStore.each(function(_record){
                roleMembers.push(_record.data);
            });

            // get role rights                
            var roleRights = [];
            var rightsStore = Ext.StoreMgr.get('RoleRightsStore');
            
            rightsStore.each(function(_record){
                roleRights.push(_record.data);
            });

            // update form               
            form.updateRecord(this.role);

            /*********** save role members & form ************/
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveRole', 
                    roleData: Ext.util.JSON.encode(this.role.data),
                    roleMembers: Ext.util.JSON.encode(roleMembers),
                    roleRights: Ext.util.JSON.encode(roleRights)
                },
                success: function(response) {
                    this.fireEvent('update', Ext.util.JSON.encode(this.role.data));
                    Ext.MessageBox.hide();
                    if(_closeWindow === true) {
                        this.window.close();
                    } else {
                        this.onRecordLoad(response);
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save role.')); 
                },
                scope: this 
            });
                
            
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },
    
    handlerDelete: function(_button, _event) {
        var roleIds = Ext.util.JSON.encode([this.role.id]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Admin.deleteRoles', 
                roleIds: roleIds
            },
            text: this.translation.gettext('Deleting role...'),
            success: function(_result, _request) {
                if(window.opener.Tine.Admin.Roles) {
                    window.opener.Tine.Admin.Roles.Main.reload();
                }
                window.close();
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occurred while trying to delete the role.')); 
            } 
        });                           
    },
    
    
    /**
     * var rights storage
     */
    rightsDataStore: null,

    updateRecord: function(_roleData) {
        // if roleData is empty (=array), set to empty object because array won't work!
        if (_roleData.length === 0) {
            _roleData = {};
        }
        this.role = new Tine.Tinebase.Model.Role(_roleData, _roleData.id ? _roleData.id : 0);
        
        this.membersStore.loadData(this.role.get('roleMembers'));
        this.rightsDataStore.loadData(this.role.get('roleRights'));
        this.allRights = this.role.get('allRights');
        this.createRightsTreeNodes();
    },

    /**
     * creates the rights tree
     *
     */
    initRightsTree: function() {
        
        this.rightsTreePanel = new Ext.tree.TreePanel({
            id: 'rightsTree',
            iconCls: 'AdminTreePanel',
            rootVisible: false,
            border: false
        });
        
        // set the root node
        var treeRoot = new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        });

        this.rightsTreePanel.setRootNode(treeRoot);
    },
    
    createRightsTreeNodes: function () {
        var treeRoot = this.rightsTreePanel.getRootNode();
        
        var toRemove = [];
        treeRoot.eachChild(function(node){
            toRemove.push(node);
        });
        
        var expandNode = (this.allRights.length > 5) ? false : true;
        
        // add nodes to tree        
        for(var i=0; i<this.allRights.length; i++) {
            // don't duplicate tree nodes on 'apply changes'
            toRemove[i] ? toRemove[i].remove() : null;
            var node = new Ext.tree.TreeNode(this.allRights[i]);
            node.attributes.application_id = this.allRights[i].application_id;
            node.expanded = expandNode;
            treeRoot.appendChild(node);
            
            // append children          
            if ( this.allRights[i].children ) {
            
                for(var j=0; j < this.allRights[i].children.length; j++) {
                
                    var childData = this.allRights[i].children[j];
                    childData.leaf = true;
                    childData.icon = "library/ExtJS/resources/images/default/s.gif";                    
                    
                    // check if right is set
                    childData.checked = !!this.getRightId(this.allRights[i].application_id,childData.right);
                    childData.iconCls = "x-tree-node-leaf-roles";
                    var child = new Ext.tree.TreeNode(childData);
                    child.attributes.right = childData.right;
                    
                    // add onchange handler
                    child.on('checkchange', function(_node, _checked) {
                    
                        // get parents application id
                        var applicationId = _node.parentNode.attributes.application_id;
                    
                        // put it in the storage or remove it                        
                        if ( _checked ) {
                            this.rightsDataStore.add (
                                new Ext.data.Record({
                                    //right: _node.text,
                                    right: _node.attributes.right,
                                    application_id: applicationId
                                })
                            );
                        } else {
                            var rightId = this.getRightId(applicationId,_node.attributes.right);
                            this.rightsDataStore.remove ( this.rightsDataStore.getById(rightId) );                                                                                         
                        }   
                        
                        //console.log ( this.rightsDataStore );
                        
                    },this);
                    
                    node.appendChild(child);                    
                }       
            }
            
        }     
        
        return this.rightsTreePanel;
    },
    
    /**
     * returns form
     * 
     */
    getFormContents: function() {
        
        this.initRightsTree();
        
        this.accountPickerGridPanel = new Tine.widgets.account.PickerGridPanel({
            title: this.translation.gettext('Members'),
            store: this.membersStore,
            anchor: '100% 100%',
            recordClass: Tine.Tinebase.Model.Account,
            selectType: 'both'
        });
        
        var tabPanelRights = {
            title:this.translation.gettext('Rights'),
            layout:'form',
            layoutOnTabChange:true,            
            deferredRender:false,
            autoScroll: true,
            anchor:'100% 100%',
            border:false,
            items:[
                this.rightsTreePanel
            ]
        };
        
        /******* THE edit dialog ********/
        
        var editRoleDialog = {
            layout:'border',
            border:false,
            width: 600,
            height: 500,
            items:[{
                    region: 'north',
                    layout:'column',
                    border: false,
                    autoHeight: true,
                    items:[{
                        columnWidth: 1,
                        layout: 'form',
                        border: false,
                        items:[{
                            xtype:'textfield',
                            fieldLabel: this.translation.gettext('Role Name'), 
                            name:'name',
                            anchor:'100%',
                            allowBlank: false,
                            maxLength: 128
                        }, {
                            xtype:'textarea',
                            name: 'description',
                            fieldLabel: this.translation.gettext('Description'),
                            grow: false,
                            preventScrollbars:false,
                            anchor:'100%',
                            height: 60
                        }]        
                    }]
                },
                new Ext.TabPanel({
                    plain:true,
                    region: 'center',
                    activeTab: 0,
                    id: 'editMainTabPanel',
                    layoutOnTabChange:true,  
                    items:[
                        //tabPanelMembers,
                        this.accountPickerGridPanel,
                        tabPanelRights
                    ]
                })
            ]
        };
        
        return editRoleDialog;
    },
    
    initComponent: function() {
        this.role = this.role ? this.role : new Tine.Tinebase.Model.Role({}, 0);
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        //this.title = title: 'Edit Role ' + ,
        
        Ext.MessageBox.wait(this.translation._('Loading Role...'), this.translation._('Please Wait'));
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
            //fields: [ 'account_name', 'account_id', 'account_type' ]
        });
        //Ext.StoreMgr.add('RoleMembersStore', this.membersStore);        
        
        // init rights store
        this.rightsDataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Admin.Roles.Right
        });
        Ext.StoreMgr.add('RoleRightsStore', this.rightsDataStore);
        //this.rightsDataStore.setDefaultSort('right', 'asc');
        
        this.items = this.getFormContents();
        Tine.Admin.Groups.EditDialog.superclass.initComponent.call(this);
    },
    
    onRecordLoad: function(response) {
        this.getForm().findField('name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.role.id) {
            window.document.title = this.translation.gettext('Add New Role');
        } else {
            window.document.title = String.format(this.translation.gettext('Edit Role "{0}"'), this.role.get('name'));
        }
        
        this.getForm().loadRecord(this.role);
        Ext.MessageBox.hide();
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
        layout: Tine.Admin.Roles.EditDialog.prototype.windowLayout,
        contentPanelConstructor: 'Tine.Admin.Roles.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};