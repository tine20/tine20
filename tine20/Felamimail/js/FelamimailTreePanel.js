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
            backend_id: 'default',
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
    	// only for folder nodes
        if (!node.attributes.folderNode) {
        	// @todo add/edit/remove account
            return;
        } else {
            var menu = new Ext.menu.Menu({
                items: [
                    this.getCreateAction(node),
                    this.getRenameAction(node),
                    this.getDeleteAction(node),
                    this.getRefreshAction(node)
                ]
            });
        }
        menu.showAt(e.getXY());
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
                                backend_id: node.attributes.backend_id
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
                                backend_id: node.attributes.backend_id
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
                                backendId:  node.attributes.backend_id
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
    getRefreshAction: function(node) {
        return {
            text: _('Refresh'),
            iconCls: 'x-tbar-loading',
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
            {field: 'backend_id', operator: 'equals', value: node.attributes.backend_id},
            {field: 'globalname', operator: 'equals', value: node.attributes.globalname}
        ];
    	
    	Tine.Felamimail.TreeLoader.superclass.requestData.call(this, node, callback);
    },
        
    /**
     * @private
     * 
     * @todo what about equal folder names (=id) in different subtrees?
     * @todo generalize this?
     */
    createNode: function(attr) {
    	var node = {
    		id: attr.id,
    		leaf: (attr.has_children != 1),
    		text: attr.localname,
    		globalname: attr.globalname,
    		backend_id: attr.backend_id,
    		folderNode: true
    	};
        //console.log(node);
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    }
	
});
