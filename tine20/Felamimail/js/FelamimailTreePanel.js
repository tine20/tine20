/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        show new mails and number of unread mails next to folder name
 * @todo        generalize context menu (items) -> and use it in container tree as well
 * @todo        add context menu
 *              - add/rename folders
 *              - change account settings
 *              - add new accounts
 * @todo        add multiple accounts
 * @todo        add folder model?
 * @todo        save tree state? @see http://examples.extjs.eu/?ex=treestate
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * folder tree panel
 * 
 * @class Tine.Felamimail.TreePanel
 * @extends Ext.tree.TreePanel
 */
Tine.Felamimail.TreePanel = Ext.extend(Ext.tree.TreePanel, {
	
    /**
     * @cfg {application}
     */
    app: null,
    
    /**
     * @cfg {String}
     */
    containerName: 'Folder',
    
    /****** TreePanel config ******/
	rootVisible: true,
	autoScroll: true,
    id: 'felamimail-tree',
    // drag n drop
    enableDrop: true,
    ddGroup: 'mailToTreeDDGroup',
	
    /**
     * init
     */
    initComponent: function() {
    	
        this.loader = new Tine.Felamimail.TreeLoader({
            app: this.app
        });

        // set the root node
        // @todo make multiple accounts/backends possible
        this.root = new Ext.tree.AsyncTreeNode({
            text: 'default',
            globalname: '',
            account_id: 'default',
            draggable: false,
            allowDrop: false,
            expanded: false,
            leaf: false,
            id: '/'
            //iconCls: 'FelamimailMessage'
        });
                
    	Tine.Felamimail.TreePanel.superclass.initComponent.call(this);
        
    	// add handlers
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforenodedrop, this);
	},
    
    /**
     * returns a filter plugin to be used in a grid
     *
     * @todo use folder id here
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                	//console.log(scope);
                	var node = scope.getSelectionModel().getSelectedNode();
                    return [
                        {field: 'folder_id',     operator: 'equals', value: (node) ? node.id : '' }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    },
    
    /***************** event handler *******************/
    
    /**
     * on click handler
     * 
     * - expand + select node
     * - update filter toolbar of grid
     * 
     * @param {} node
     */
    onClick: function(node) {
        node.expand();
        node.select();
        
        if (node.id && node.id != '/') {
            this.filterPlugin.onFilterChange();
        }
    },
    
    /**
     * show context menu for folder tree
     * 
     * items:
     * - create folder
     * - rename folder
     * - delete folder
     * 
     * @param {} node
     * @param {} e
     * 
     * @todo    generalize that (it's basically the same like the container tree menu...)?
     */
    onContextMenu: function(node, e) {
        //console.log(node);
    	// only for folder nodes
        if (!node.attributes.folderNode) {
        	// @todo add/edit/remove account
            return;
        } else {
            
            var menuItems = [
                this.getCreateAction(node),
                this.getRenameAction(node),
                this.getDeleteAction(node),
                this.getRefreshCacheAction(node)
            ];
            
            if (node.attributes.globalname == 'Trash') {
                menuItems.push(this.getEmptyFolderAction(node));
            }
            
            var menu = new Ext.menu.Menu({
                items: menuItems
            });
        }
        menu.showAt(e.getXY());
    },
    
    /**
     * mail got dropped on folder node
     * 
     * @param {Object} dropEvent
     */
    onBeforenodedrop: function(dropEvent) {
        
        var folderId = dropEvent.target.id;
        var ids = [];
        
        for (var i=0; i < dropEvent.data.selections.length; i++) {
            ids.push(dropEvent.data.selections[i].id);
        };
        
        // move messages to folder
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.moveMessages',
                folderId: folderId,
                ids: Ext.util.JSON.encode(ids)
            },
            scope: this,
            success: function(_result, _request){
                // update grid
                this.filterPlugin.onFilterChange();
            }
        });
        
        return true;
    },
    
    /********************** actions *******************/

    /**
     * get create action
     * 
     * @param {Ext.tree.AsyncTreeNode}
     * @return {Object} action item
     */
    getCreateAction: function(node) {
        return {
            text: _('Create Folder'),
            iconCls: 'notes_createdIcon',
            scope: this,
            handler: function() {
                Ext.MessageBox.prompt(String.format(translation._('New {0}'), this.containerName), String.format(translation._('Please enter the name of the new {0}:'), this.containerName), function(_btn, _text) {
                    if( this.ctxNode && _btn == 'ok') {
                        if (! _text) {
                            Ext.Msg.alert(String.format(translation._('No {0} added'), this.containerName), String.format(translation._('You have to supply a {0} name!'), this.containerName));
                            return;
                        }
                        Ext.MessageBox.wait(translation._('Please wait'), String.format(translation._('Creating {0}...' ), this.containerName));
                        var parentNode = this.ctxNode;
                        
                        Ext.Ajax.request({
                            params: {
                                method: 'Felamimail.createFolder',
                                folder: parentNode.attributes.globalname + '/' + _text, // ?
                                account_id: node.attributes.account_id
                            },
                            scope: this,
                            success: function(_result, _request){
                            	/*
                                var container = Ext.util.JSON.decode(_result.responseText);
                                var newNode = this.loader.createNode(container);
                                parentNode.appendChild(newNode);
                                this.fireEvent('containeradd', container);
                                */
                                Ext.MessageBox.hide();
                            }
                        });
                        
                    }
                }, this);
            }
        };
    },
    
    /**
     * get rename action
     * 
     * @param {Ext.tree.AsyncTreeNode}
     * @return {Object} action item
     */
    getRenameAction: function(node) {
        return {
            text: _('Rename Folder'),
            iconCls: 'notes_changedIcon',
            scope: this,
            handler: function() {
            	/*
                Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete the Folder "{0}"?'), node.text), function(_btn){
                    if ( _btn == 'yes') {
                        Ext.MessageBox.wait(_('Please wait'), String.format(_('Deleting Folder "{0}"' ), node.text));
                        
                        Ext.Ajax.request({
                            params: {
                                method: 'Felamimail.deleteFolder',
                                folder: node.attributes.globalname,
                                account_id: node.attributes.account_id
                            },
                            scope: this,
                            success: function(){
                                node.unselect();
                                node.remove();
                                Ext.MessageBox.hide();
                            }
                        });
                    }
                }, this);
                */
            }
        };
    },
    
    /**
     * get delete action
     * 
     * @param {Ext.tree.AsyncTreeNode}
     * @return {Object} action item
     */
    getDeleteAction: function(node) {
        return {
            text: _('Delete Folder'),
            iconCls: 'action_delete',
            scope: this,
            handler: function() {
                Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete the Folder "{0}"?'), node.text), function(_btn){
                    if ( _btn == 'yes') {
                        Ext.MessageBox.wait(_('Please wait'), String.format(_('Deleting Folder "{0}"' ), node.text));
                        
                        Ext.Ajax.request({
                            params: {
                                method:     'Felamimail.deleteFolder',
                                folder:     node.attributes.globalname,
                                accountId:  node.attributes.account_id
                            },
                            scope: this,
                            success: function(){
                                node.unselect();
                                node.remove();
                                Ext.MessageBox.hide();
                            }
                        });
                    }
                }, this);
            }
        };
    },
    
    /**
     * get refresh action
     * 
     * @param {Ext.tree.AsyncTreeNode}
     * @return {Object} action item
     */
    getRefreshCacheAction: function(node) {
        return {
            text: _('Update Cache'),
            iconCls: 'action_update_cache',
            scope: this,
            handler: function() {
                Ext.Ajax.request({
                    params: {
                        method: 'Felamimail.refreshFolder',
                        folderId: node.id
                    },
                    scope: this,
                    success: function(_result, _request){
                        // update grid
                        this.filterPlugin.onFilterChange();
                    }
                });
            }
        };
    },

    /**
     * get empty folder action
     * 
     * @param {Ext.tree.AsyncTreeNode}
     * @return {Object} action item
     */
    getEmptyFolderAction: function(node) {
        return {
            text: _('Empty Folder'),
            iconCls: 'action_folder_emptytrash',
            scope: this,
            handler: function() {
                Ext.Ajax.request({
                    params: {
                        method: 'Felamimail.emptyFolder',
                        folderId: node.id
                    },
                    scope: this,
                    success: function(_result, _request){
                        // update grid
                        this.filterPlugin.onFilterChange();
                    }
                });
            }
        };
    }
});

Tine.Felamimail.TreeLoader = Ext.extend(Tine.widgets.tree.Loader, {
	
    method: 'Felamimail.searchFolders',

    /**
     * @private
     */
    initComponent: function() {
        this.filter = [];
        
        Tine.Felamimail.TreeLoader.superclass.initComponent.call(this);
    },
    
    /**
     * request data
     * 
     * @param {} node
     * @param {} callback
     * @private
     */
    requestData: function(node, callback){
    	// add globalname to filter
    	this.filter = [
            {field: 'account_id', operator: 'equals', value: node.attributes.account_id},
            {field: 'globalname', operator: 'equals', value: node.attributes.globalname}
        ];
    	
    	Tine.Felamimail.TreeLoader.superclass.requestData.call(this, node, callback);
    },
        
    /**
     * @private
     * 
     * @todo try to disable '+' on nodes that don't have children / it looks like that leafs can't be drop targets :(
     * @todo what about equal folder names (=id) in different subtrees?
     * @todo generalize this?
     */
    createNode: function(attr) {
    	var node = {
    		id: attr.id,
    		leaf: false,
    		text: attr.localname,
    		globalname: attr.globalname,
    		account_id: attr.account_id,
    		folderNode: true,
            allowDrop: true
            //expandable: (attr.has_children == '1'),
            //allowChildren: (attr.has_children == 1)
            //childNodes: []
    	};
        //console.log(node);
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    }
	
});
