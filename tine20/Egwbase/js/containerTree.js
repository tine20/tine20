/*
 * egroupware 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Egw.Egwbase.container');
Egw.Egwbase.container = {
	/**
     * constant for no grants
     */
	GRANT_NONE: 0,
    /**
     * constant for read grant
     */
    GRANT_READ: 1,
    /**
     * constant for add grant
     */
    GRANT_ADD: 2,
    /**
     * constant for edit grant
     */
    GRANT_EDIT: 4,
    /**
     * constant for delete grant
     */
    GRANT_DELETE: 8,
    /**
     * constant for admin grant
     */
    GRANT_ADMIN: 16,
    /**
     * constant for all grants
     */
    GRANT_ANY: 31,
	/** 
	 * type for internal contaier
     * for example the internal addressbook
     */
    TYPE_INTERNAL: 'internal',
    /**
     * type for personal containers
     */
    TYPE_PERSONAL: 'personal',
    /**
     * type for shared container
     */
    TYPE_SHARED: 'shared'
};


 /**
  * @class Egw.containerTreePanel
  * @package     Egw
  * @subpackage  Widgets
  * <p> Utility class for generating container trees as used in the 
  * apps tree panel</p>
  * <p>Example usage:</p>
  * <pre><code>
  var taskPanel =  new Egw.containerTreePanel({
        iconCls: 'TasksTreePanel',
        title: 'Tasks',
        itemName: 'Tasks',
        appName: 'Tasks',
        border: false
    });
  </code></pre>
  */
 Egw.containerTreePanel = Ext.extend(Ext.tree.TreePanel, {
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
	
	iconCls: 'x-new-application',
	rootVisible: false,
	border: false,
	
	//private
	//holds treenode which got a contextmenu
	ctxNode: null,
	initComponent: function(){
		Egw.containerTreePanel.superclass.initComponent.call(this);
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
	            containerType: Egw.Egwbase.container.TYPE_PERSONAL,
	            id: 'user',
	            leaf: null,
	            owner: Egw.Egwbase.Registry.get('currentAccount')
	        }, {
	            text: 'Shared ' + this.itemName,
	            cls: 'file',
	            containerType: Egw.Egwbase.container.TYPE_SHARED,
	            children: null,
	            leaf: null,
				owner: null
	        }, {
	            text: 'Other Users ' + this.itemName,
	            cls: 'file',
	            containerType: 'OtherUsers',
	            children: null,
	            leaf: null,
				owner: null
	        }]
	    }];
	    
	    this.loader = new Egw.containerTreeLoader({
	        dataUrl:'index.php',
	        baseParams: {
	            jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
				method: 'Egwbase.getContainer',
				application: this.appName,
				containerType: Egw.Egwbase.container.TYPE_PERSONAL
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
					if (container.account_grants & Egw.Egwbase.container.GRANT_ADMIN) {
						//console.log('GRANT_ADMIN for this container');
						this.contextMenuSingleContainer.showAt(event.getXY());
					}
					break;
				case Egw.Egwbase.container.TYPE_PERSONAL:
				    if (owner.accountId == Egw.Egwbase.Registry.get('currentAccount').accountId) {
						//console.log('owner clicked his own folder');
						this.contextMenuUserFolder.showAt(event.getXY());
					}
					break;
			}
		}, this);
		
		this.setRootNode(treeRoot);
	   
	    for(var i=0; i<initialTree.length; i++) {
           treeRoot.appendChild( new Ext.tree.AsyncTreeNode(initialTree[i]) );
        }
		
		
	},
	afterRender: function() {
		Egw.containerTreePanel.superclass.afterRender.call(this);
		//console.log(this);
		this.expandPath('/root/all');
	},
	initContextMenu: function() {
		var handler = {
			addContainer: function() {
				Ext.MessageBox.prompt('New ' + this.folderName, 'Please enter the name of the new ' + this.folderName + ':', function(_btn, _text) {
                    if( this.ctxNode && _btn == 'ok') {
						Ext.MessageBox.wait('Please wait', 'Creating ' + this.folderName+ '...');
						var parentNode = this.ctxNode;
						
						Ext.Ajax.request({
		                    params: {
		                        method: 'Egwbase.addContainer',
								application: this.appName,
		                        containerName: _text,
		                        containerType: parentNode.attributes.containerType,
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
									method: 'Egwbase.deleteContainer',
									containerId: node.attributes.container.container_id,
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
										method: 'Egwbase.renameContainer',
										containerId: node.attributes.container.container_id,
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
                this.fireEvent('containerpermissionchange', '');
            },
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
	},
	addContainer: function(container_name) {
		
	}
		
 });

Ext.namespace('Egw.widgets', 'Egw.widgets.container');
Egw.widgets.container.selectionComboBox = Ext.extend(Ext.form.ComboBox, {
	/**
     * @cfg {array}
     * default container
     */
    defaultContainer: false,
	
    allowBlank: false,
    readOnly:true,
	container_id: null,
	
	// private
	initComponent: function(){
		Egw.widgets.container.selectionComboBox.superclass.initComponent.call(this);
        if (this.defaultContainer) {
			this.container_id = this.defaultContainer.container_id;
			this.value = this.defaultContainer.container_name;
		}
		this.onTriggerClick = function(e) {
            var w = new Egw.widgets.container.selectionDialog({
				TriggerField: this
			});
        };
	},
	//private
	getValue: function(){
		return this.container_id;
	}
});

Egw.widgets.container.selectionDialog = Ext.extend(Ext.Component, {
	title: 'please select a container',

	// private
    initComponent: function(){
		Egw.widgets.container.selectionDialog.superclass.initComponent.call(this);

		var w = new Ext.Window({
			title: this.title,
			modal: true,
			width: 375,
			height: 400,
			minWidth: 375,
			minHeight: 400,
			layout: 'fit',
			plain: true,
			bodyStyle: 'padding:5px;',
			buttonAlign: 'center'
		});
		
		var tree = new Egw.containerTreePanel({
			itemName: this.TriggerField.itemName,
			appName: this.TriggerField.appName,
			defaultContainer: this.TriggerField.defaultContainer
		});
		
		tree.on('click', function(_node) {
            if(_node.attributes.containerType == 'singleContainer') {
				
				this.TriggerField.container = _node.attributes.container;
				this.TriggerField.setValue(_node.attributes.text);
                w.hide();
            }
        }, this);
			
		w.add(tree);
		w.show();
	}
});


			
Egw.containerTreeLoader = Ext.extend(Ext.tree.TreeLoader, {
	//private
 	createNode: function(attr)
 	{
		// console.log(attr);
		// map attributes from Egwbase_Container to attrs from ExtJS
		if (attr.container_name) {
            attr = {
                containerType: 'singleContainer',
                container: attr,
                text: attr.container_name,
                cls: 'file',
                leaf: true
            };
        } else if (attr.accountDisplayName) {
            attr = {
                containerType: Egw.Egwbase.container.TYPE_PERSONAL,
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
