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
        appName: 'Tasks',
        containerName: 'to do list',
        containersName: 'to do lists',
        border: false
    });
  </code></pre>
  */
Tine.widgets.container.TreePanel = function(config) {
    Ext.apply(this, config);
    
    if (this.app) {
        this.appName = this.app.appName;
        
        if (this.recordClass) {
            this.containerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
            this.containersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        }
    }
    Tine.widgets.container.TreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.container.TreePanel, Ext.tree.TreePanel, {
 	/**
     * @cfg {string} appName
     * name of application
     */
    appName: '',
	/**
     * @cfg {string} itemName
     * name of containers items
     */
    //itemName: 'item',
    /**
     * @cfg {string} containerName
     * name of container (singular)
     */
	containerName: 'container',
    /**
     * @cfg {string} containerName
     * name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg {array} extraItems
     * additional items to display under all
     */
    extraItems: null,
    /**
     * @cfg {Number} how many chars of the containername to display
     */
    displayLength: 25,
	// presets
	iconCls: 'x-new-application',
	rootVisible: false,
	border: false,
    autoScroll: true,
	
	// holds treenode which got a contextmenu
	ctxNode: null,
	
	// private
	initComponent: function(){
        var translation = new Locale.Gettext();
        translation.textdomain('Tinebase');
		
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
	        text: String.format(translation._('All {0}'), this.containersName),
	        cls: "treemain",
	        containerType: 'all',
	        id: 'all',
	        children: [{
	            text: String.format(translation._('My {0}'), this.containersName),
	            cls: 'file',
	            containerType: Tine.Tinebase.container.TYPE_PERSONAL,
	            id: 'user',
	            leaf: null,
	            owner: Tine.Tinebase.registry.get('currentAccount')
	        }, {
	            text: String.format(translation._('Shared {0}'), this.containersName),
	            cls: 'file',
	            containerType: Tine.Tinebase.container.TYPE_SHARED,
                id: 'shared',
	            children: null,
	            leaf: null,
				owner: null
	        }, {
	            text: String.format(translation._('Other Users {0}'), this.containersName),
	            cls: 'file',
	            containerType: 'otherUsers',
                id: 'otherUsers',
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
            displayLength: this.displayLength,
	        baseParams: {
	            jsonKey: Tine.Tinebase.registry.get('jsonKey'),
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
		
        this.on('click', function(node){
            // note: if node is clicked, it is not selected!
            node.getOwnerTree().selectPath(node.getPath());
            node.expand();
        }, this);
        
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
				    if (owner.accountId == Tine.Tinebase.registry.get('currentAccount').accountId) {
						//console.log('owner clicked his own folder');
						this.contextMenuUserFolder.showAt(event.getXY());
					}
					break;
				case Tine.Tinebase.container.TYPE_SHARED:
//				    if(Tine[this.appName].rights.indexOf('admin') > -1) {
				    if(Tine.Tinebase.common.hasRight('admin', this.appName)) {
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
    
    /**
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                
                /**
                 * gets value of this container filter
                 */
                getValue: function() {
                    var nodeAttributes = scope.getSelectionModel().getSelectedNode().attributes || {};
                    return [
                        {field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                        {field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                        {field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
                    ];
                },
                
                /**
                 * sets the selected container (node) of this tree
                 * 
                 * @param {Array} all filters
                 */
                setValue: function(filters) {
                    for (var i=0; i<filters.length; i++) {
                        if (filters[i].field == 'container_id') {
                            switch (filters[i].operator) {
                                case 'equals':
                                    var parts = filters[i].value.path.replace(/^\//, '').split('/');
                                    var userId, containerId;
                                    switch (parts[0]) {
                                        case 'personal':
                                            userId = parts[1];
                                            containerId = parts[2];
                                            
                                            if (userId == Tine.Tinebase.registry.get('currentAccount').accountId) {
                                                scope.selectPath('/root/all/user/' + containerId);
                                            } else {
                                                scope.selectPath('/root/all/otherUsers/' + containerId);
                                            }
                                            break;
                                        case 'shared':
                                            containerId = parts[1];
                                            scope.selectPath('/root/all/shared/' + containerId);
                                            break;
                                        default:
                                            console.error('no such container type');
                                            break;
                                            
                                    }
                                    break;
                                case 'specialNode':
                                    switch (filters[i].value) {
                                        case 'all':
                                            scope.selectPath('/root/all');
                                            break;
                                        case 'shared':
                                        case 'otherUsers':
                                        case 'internal':
                                            scope.selectPath('/root/all' + filters[i].value);
                                            break;
                                        default:
                                            //throw new 
                                            console.error('no such container_id spechial node');
                                            break;
                                    }
                                    break;
                                case 'personalNode':
                                    if (filters[i].value == Tine.Tinebase.registry.get('currentAccount').accountId) {
                                        scope.selectPath('/root/all/user');
                                    } else {
                                        //scope.expandPath('/root/all/otherUsers');
                                        scope.selectPath('/root/all/otherUsers/' + filters[i].value);
                                    }
                                    break;
                                default:
                                    console.error('no such container_id filter operator');
                                    break;
                            }
                        }
                    }
                    //console.log(filters);
                }
            });
            
            this.on('click', function(node){
                this.filterPlugin.onFilterChange();
            }, this);
        }
        
        return this.filterPlugin;
    },
    
    /**
     * returns object of selected container or null
     */
    getSelectedContainer: function() {
        var container = null;
        
        var node = this.getSelectionModel().getSelectedNode();
        var containerType = node.attributes && node.attributes.containerType;
        if (containerType == 'singleContainer' && node.attributes.container) {
            container = node.attributes.container;
        }
        
        return container;
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
        var translation = new Locale.Gettext();
        translation.textdomain('Tinebase');

        var handler = {
			addContainer: function() {
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
					Ext.MessageBox.confirm(translation._('Confirm'), String.format(translation._('Do you really want to delete the {0} "{1}"?'), this.containerName, node.text), function(_btn){
						if ( _btn == 'yes') {
							Ext.MessageBox.wait(translation._('Please wait'), String.format(translation._('Deleting {0} "{1}"' ), this.containerName , node.text));
							
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
						title: 'Rename ' + this.containerName,
						msg: String.format(translation._('Please enter the new name of the {0}:'), this.containerName),
						buttons: Ext.MessageBox.OKCANCEL,
						value: node.text,
						fn: function(_btn, _text){
							if (_btn == 'ok') {
                                if (! _text) {
                                    Ext.Msg.alert(String.format(translation._('Not renamed {0}'), this.containerName), String.format(translation._('You have to supply a {0} name!'), this.containerName));
                                    return;
                                }
								Ext.MessageBox.wait(translation._('Please wait'), String.format(translation._('Updating {0} "{1}"'), this.containerName, node.text));
								
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
                    var window = new Ext.ux.PopupWindow({
                        url: 'index.php',
                        name: 'TinebaseManageContainerGrants' + node.attributes.container.id,
                        layout: 'fit',
                        modal: true,
                        width: 700,
                        height: 450,
                        title: String.format(_('Manage Permissions for {0} "{1}"'), this.containerName, Ext.util.Format.htmlEncode(node.attributes.container.name)),
                        contentPanelConstructor: 'Tine.widgets.container.grantDialog',
                        contentPanelConstructorConfig: {
                            containerName: this.containerName,
                            grantContainer: node.attributes.container
                        }
                    });
				}
            }
		};
		
		var actions = {
			addContainer: new Ext.Action({
				text: String.format(translation._('Add {0}'), this.containerName),
				iconCls: 'action_add',
				handler: handler.addContainer,
				scope: this
			}),
			deleteContainer: new Ext.Action({
				text: String.format(translation._('Delete {0}'), this.containerName),
				iconCls: 'action_delete',
				handler: handler.deleteContainer,
				scope: this
			}),
			renameContainer: new Ext.Action({
				text: String.format(translation._('Rename {0}'), this.containerName),
				iconCls: 'action_rename',
				handler: handler.renameContainer,
				scope: this
			}),
			grantsContainer: new Ext.Action({
				text: translation._('Manage permissions'),
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
    /**
     * @cfg {Number} how many chars of the containername to display
     */
    displayLength: 25,
    
	/**
     * @private
     */
 	createNode: function(attr) {
        
		// map attributes from Tinebase_Container to attrs from ExtJS
		if (attr.name) {
            if (!attr.account_grants.account_id){
                // temporary workaround, for a Zend_Json::encode problem
                attr.account_grants = Ext.util.JSON.decode(attr.account_grants);
            }
            attr = {
                containerType: 'singleContainer',
                container: attr,
                text: attr.name,
                id: attr.id,
                cls: 'file',
                leaf: true
            };
        } else if (attr.accountDisplayName) {
            attr = {
                containerType: Tine.Tinebase.container.TYPE_PERSONAL,
                text: attr.accountDisplayName,
                id: attr.accountId,
                cls: 'folder',
                leaf: false,
                owner: attr
            };
        }
                
        attr.qtip = Ext.util.Format.htmlEncode(attr.text);
        attr.text = Ext.util.Format.htmlEncode(Ext.util.Format.ellipsis(attr.text, this.displayLength));
        
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
