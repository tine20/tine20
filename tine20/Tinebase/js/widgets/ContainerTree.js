/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.container');

 /**
  * @class       Tine.containerTreePanel
  * @package     Tine
  * @subpackage  Widgets
  * @extends     Ext.tree.TreePanel
  * @param       {Object} config Configuration options
  * @description
  * <p>Utility class for generating container trees as used in the 
  * apps tree panel</p>
  * <p>This widget handles all container related actions like add/rename/delte 
  * and manager permissions<p>
  * <p>Example usage:</p>
  * <pre><code>
  var taskPanel =  new Tine.containerTreePanel({
        iconCls: 'TasksTreePanel',
        title: 'Tasks',
        itemName: 'Tasks',
        appName: 'Tasks',
        folderName: 'Task Folder',
        border: false
    });
  </code></pre>
  */
 Tine.widgets.container.TreePanel = Ext.extend(Ext.tree.TreePanel, {
 	/**
     * @cfg {string} appName
     * name of application
     */
    appName: '',
	/**
     * @cfg {string} itemName
     * name of containers items
     */
    itemName: 'item',
    /**
     * @cfg {string} folderName
     * name of folders
     */
	folderName: 'folder',
    /**
     * @cfg {array} extraItems
     * additional items to display under all
     */
    extraItems: null,
	// presets
	iconCls: 'x-new-application',
	rootVisible: false,
	border: false,
	
	// holds treenode which got a contextmenu
	ctxNode: null,
	
	// private
	initComponent: function(){
		Tine.widgets.container.TreePanel.superclass.initComponent.call(this);
		this.addEvents(
            /**
             * @event containeradded
             * Fires when a container was added
             * @param {container} the new container
             */
            'containeradd',
            /**
             * @event containerdelete
             * Fires when a container got deleted
             * @param {container} the deleted container
             */
            'containerdelete',
            /**
             * @event containerrename
             * Fires when a container got renamed
             * @param {container} the renamed container
             */
            'containerrename',
			/**
             * @event containerpermissionchange
             * Fires when a container got renamed
             * @param {container} the container whose permissions where changed
             */
            'containerpermissionchange'
		);
			
		var treeRoot = new Ext.tree.TreeNode({
	        text: 'root',
	        draggable:false,
	        allowDrop:false,
	        id:'root'
	    });
	    
	    var initialTree = [{
	        text: 'All ' + this.itemName,
	        cls: "treemain",
	        containerType: 'all',
	        id: 'all',
	        children: [{
	            text: 'My ' + this.itemName,
	            cls: 'file',
	            containerType: Tine.Tinebase.container.TYPE_PERSONAL,
	            id: 'user',
	            leaf: null,
	            owner: Tine.Tinebase.Registry.get('currentAccount')
	        }, {
	            text: 'Shared ' + this.itemName,
	            cls: 'file',
	            containerType: Tine.Tinebase.container.TYPE_SHARED,
	            children: null,
	            leaf: null,
				owner: null
	        }, {
	            text: 'Other Users ' + this.itemName,
	            cls: 'file',
	            containerType: 'otherUsers',
	            children: null,
	            leaf: null,
				owner: null
	        }]
	    }];
	    
        if(this.extraItems !== null) {
            Ext.each(this.extraItems, function(_item){
            	initialTree[0].children.push(_item);
            });
        }
	    
	    this.loader = new Tine.widgets.container.TreeLoader({
	        dataUrl:'index.php',
	        baseParams: {
	            jsonKey: Tine.Tinebase.Registry.get('jsonKey'),
				method: 'Tinebase_Container.getContainer',
				application: this.appName,
				containerType: Tine.Tinebase.container.TYPE_PERSONAL
	        }
	    });
		
		this.loader.on("beforeload", function(loader, node) {
			loader.baseParams.containerType = node.attributes.containerType;
			loader.baseParams.owner = node.attributes.owner ? node.attributes.owner.accountId : null;
	    }, this);
        
		this.initContextMenu();
		
	    this.on('contextmenu', function(node, event){
			this.ctxNode = node;
			var container = node.attributes.container;
			var owner     = node.attributes.owner;
			switch (node.attributes.containerType) {
				case 'singleContainer':
					if (container.account_grants.adminGrant) {
						//console.log('GRANT_ADMIN for this container');
						this.contextMenuSingleContainer.showAt(event.getXY());
					}
					break;
				case Tine.Tinebase.container.TYPE_PERSONAL:
				    if (owner.accountId == Tine.Tinebase.Registry.get('currentAccount').accountId) {
						//console.log('owner clicked his own folder');
						this.contextMenuUserFolder.showAt(event.getXY());
					}
					break;
				case Tine.Tinebase.container.TYPE_SHARED:
				    // anyone is allowd to add shared folders atm.
				    this.contextMenuUserFolder.showAt(event.getXY());
					break;
			}
		}, this);
		
		this.setRootNode(treeRoot);
	   
	    for(var i=0; i<initialTree.length; i++) {
           treeRoot.appendChild( new Ext.tree.AsyncTreeNode(initialTree[i]) );
        }
		
	},
	// private
	afterRender: function() {
		Tine.widgets.container.TreePanel.superclass.afterRender.call(this);
		//console.log(this);
		this.expandPath('/root/all');
		this.selectPath('/root/all');
	},
	// private
	initContextMenu: function() {
		var handler = {
			addContainer: function() {
				Ext.MessageBox.prompt('New ' + this.folderName, 'Please enter the name of the new ' + this.folderName + ':', function(_btn, _text) {
                    if( this.ctxNode && _btn == 'ok') {
						Ext.MessageBox.wait('Please wait', 'Creating ' + this.folderName+ '...');
						var parentNode = this.ctxNode;
						
						Ext.Ajax.request({
		                    params: {
		                        method: 'Tinebase_Container.addContainer',
								application: this.appName,
		                        containerName: _text,
		                        containerType: parentNode.attributes.containerType
		                    },
							scope: this,
		                    success: function(_result, _request){
	                            var container = Ext.util.JSON.decode(_result.responseText);
                                var newNode = this.loader.createNode(container);
	                            parentNode.appendChild(newNode);
								this.fireEvent('containeradd', container);
								Ext.MessageBox.hide();
		                    }
		                });
					    
					}
				}, this);
			},
			deleteContainer: function() {
				if (this.ctxNode) {
					var node = this.ctxNode;
					Ext.MessageBox.confirm('Confirm','Do you really want to delete the ' + this.folderName + ': "' + node.text + '"?', function(_btn){
						if ( _btn == 'yes') {
							Ext.MessageBox.wait('Please wait', 'Deleting ' + this.folderName + ' "' + node.text + '"');
							
							Ext.Ajax.request({
								params: {
									method: 'Tinebase_Container.deleteContainer',
									containerId: node.attributes.container.id
								},
								scope: this,
								success: function(_result, _request){
									if(node.isSelected()) {
                                        this.getSelectionModel().select(node.parentNode);
                                        this.fireEvent('click', node.parentNode);
			                        }
			                        node.remove();
									this.fireEvent('containerdelete', node.attributes.container);
									Ext.MessageBox.hide();
								}
							});
						}
					}, this);
				}
            },
			renameContainer: function() {
				if (this.ctxNode) {
					var node = this.ctxNode;
					Ext.MessageBox.show({
						title: 'Rename ' + this.folderName,
						msg: 'Please enter the new name of the ' + this.folderName + ':',
						buttons: Ext.MessageBox.OKCANCEL,
						value: node.text,
						fn: function(_btn, _text){
							if (_btn == 'ok') {
								Ext.MessageBox.wait('Please wait', 'Updateing ' + this.folderName + ' "' + node.text + '"');
								
								Ext.Ajax.request({
									params: {
										method: 'Tinebase_Container.renameContainer',
										containerId: node.attributes.container.id,
										newName: _text
									},
									scope: this,
									success: function(_result, _request){
										var container = Ext.util.JSON.decode(_result.responseText);
										node.setText(_text);
										this.fireEvent('containerrename', container);
										Ext.MessageBox.hide();
									}
								});
							}
						},
						scope: this,
						prompt: true,
						icon: Ext.MessageBox.QUESTION
					});
				}
            },
			managePermissions: function() {
				if (this.ctxNode) {
					var node = this.ctxNode;
					var win = new Tine.widgets.container.grantDialog({
						folderName: this.folderName,
						grantContainer: node.attributes.container
					});
					win.show();
				    //this.fireEvent('containerpermissionchange', '');
				}
            }
		};
		
		var actions = {
			addContainer: new Ext.Action({
				text: 'add ' + this.folderName,
				iconCls: 'action_add',
				handler: handler.addContainer,
				scope: this
			}),
			deleteContainer: new Ext.Action({
				text: 'delete ' + this.folderName,
				iconCls: 'action_delete',
				handler: handler.deleteContainer,
				scope: this
			}),
			renameContainer: new Ext.Action({
				text: 'rename ' + this.folderName,
				iconCls: 'action_rename',
				handler: handler.renameContainer,
				scope: this
			}),
			grantsContainer: new Ext.Action({
				text: 'manage permissions',
				iconCls: 'action_managePermissions',
				handler: handler.managePermissions,
                scope: this
			})
		};
		
	    this.contextMenuUserFolder = new Ext.menu.Menu({
	        items: [
	            actions.addContainer
	        ]
	    });
	    
	    this.contextMenuSingleContainer= new Ext.menu.Menu({
	        items: [
	            actions.deleteContainer,
	            actions.renameContainer,
	            actions.grantsContainer
	        ]
	    });
	}
});

/**
 * Helper class for {Tine.widgets.container.TreePanel}
 * 
 * @extends {Ext.tree.TreeLoader}
 * @param {Object} attr
 */
Tine.widgets.container.TreeLoader = Ext.extend(Ext.tree.TreeLoader, {
	//private
 	createNode: function(attr)
 	{
		// map attributes from Tinebase_Container to attrs from ExtJS
		if (attr.name) {
            if (!attr.account_grants.accountId){
                // temporary workaround, for a Zend_Json::encode problem
                attr.account_grants = Ext.util.JSON.decode(attr.account_grants);
            }
            attr = {
                containerType: 'singleContainer',
                container: attr,
                text: attr.name,
                cls: 'file',
                leaf: true
            };
        } else if (attr.accountDisplayName) {
            attr = {
                containerType: Tine.Tinebase.container.TYPE_PERSONAL,
                text: attr.accountDisplayName,
                cls: 'folder',
                leaf: false,
                owner: attr
            };
        }

		// apply baseAttrs, nice idea Corey!
        if(this.baseAttrs){
            Ext.applyIf(attr, this.baseAttrs);
        }
        if(this.applyLoader !== false){
            attr.loader = this;
        }
        if(typeof attr.uiProvider == 'string'){
           attr.uiProvider = this.uiProviders[attr.uiProvider] || eval(attr.uiProvider);
        }
        return(attr.leaf ?
                        new Ext.tree.TreeNode(attr) :
                        new Ext.tree.AsyncTreeNode(attr));
    }
 });
