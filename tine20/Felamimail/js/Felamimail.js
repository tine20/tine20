/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Felamimail.js 7176 2009-03-05 12:26:08Z p.schuele@metaways.de $
 *
 * @todo        create new file for TreePanel
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
            globalName: '',
            backendId: 'default',
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
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                	console.log(scope);
                	var node = scope.getSelectionModel().getSelectedNode();
                    var nodeAttributes = (node) ? node.attributes : {};
                    return [
                        {field: 'folder',       operator: 'equals', value: nodeAttributes.globalName ? nodeAttributes.globalName : '' },
                        {field: 'backendId',    operator: 'equals', value: nodeAttributes.backendId  ? nodeAttributes.backendId : 'default' }
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
        
        this.filterPlugin.onFilterChange();
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
                    this.getDeleteAction(node)
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
                                folder: parentNode.attributes.globalName + '/' + _text, // ?
                                backendId: node.attributes.backendId
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
                                folder: node.attributes.globalName,
                                backendId: node.attributes.backendId
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
                                method: 'Felamimail.deleteFolder',
                                folder: node.attributes.globalName,
                                backendId: node.attributes.backendId
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
    }
});

Tine.Felamimail.TreeLoader = Ext.extend(Tine.widgets.data.TreeLoader, {
	
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
    	// add globalName to filter
    	this.filter = [
            {field: 'backendId', operator: 'equals', value: node.attributes.backendId},
            {field: 'globalName', operator: 'equals', value: node.attributes.globalName}
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
    	node = {
    		id: attr.localName, // attr.globalName?
    		leaf: (attr.hasChildren != 1),
    		text: attr.localName,
    		globalName: attr.globalName,
    		backendId: attr.backendId,
    		folderNode: true
    	};
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    }
	
});

/**
 * default message backend
 */
Tine.Felamimail.recordBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Felamimail',
    modelName: 'Message',
    recordClass: Tine.Felamimail.Model.Message
});
