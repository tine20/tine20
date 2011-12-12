/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.widgets', 'Tine.widgets.tree');

/**
 * returns generic tree context menu with
 * - create/add
 * - rename
 * - delete
 * - edit grants
 * 
 * ctxNode class var is required in calling class
 */
Tine.widgets.tree.ContextMenu = {
    
    /**
     * create new Ext.menu.Menu with actions
     * 
     * @param {} config has the node name, actions, etc.
     * @return {}
     */
    getMenu: function(config) {
        
        this.config = config;
                
        /****************** create ITEMS array ****************/
              
        this.action_add = new Ext.Action({
            text: String.format(_('Add')),
            iconCls: 'action_add',
            handler: this.addNode,
            requiredGrant: 'addGrant',
            scope: this.config
        });
        
        this.action_rename = new Ext.Action({
            text: String.format(_('Rename')),
            iconCls: 'action_rename',
            handler: this.renameNode,
            scope: this.config,
            requiredGrant: 'editGrant',
            allowMultiple: false
        });
        
        
        var i18n = new Locale.Gettext();
        i18n.textdomain('Tinebase');
        this.action_delete = new Ext.Action({
            text: String.format(_('Delete')),
            iconCls: 'action_delete',
            handler: this.deleteNode,
            scope: this.config,
            requiredGrant: 'deleteGrant',
            allowMultiple: true
        });
        
        this.action_grants = new Ext.Action({
            text: _('Manage permissions'),
            iconCls: 'action_managePermissions',
            handler: this.managePermissions,
            requiredGrant: 'editGrant',
            scope: this.config
        });
        
        this.action_properties = new Ext.Action({
            text: _('Properties'),
            iconCls: 'action_manageProperties',
            handler: this.manageProperties,
            requiredGrant: 'readGrant',
            scope: this.config
        });
        
        this.action_changecolor = new Ext.Action({     
            text: _('Set color'),
            iconCls: 'action_changecolor',
//            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            menu: new Ext.menu.ColorMenu({
                scope: this,
                listeners: {
                    select: this.changeNodeColor,
                    scope: this.config
                }
            })                                        
        });
        
        this.action_reload = new Ext.Action({
            text: String.format(_('Reload')),
            iconCls: 'x-tbar-loading',
            handler: this.reloadNode,
            scope: this.config
        });
        
        this.action_resume = new Ext.Action({
            text: String.format(_('Resume upload'), config.nodeName),
            iconCls: 'action_resume',
            handler: this.onResume,
            scope: this.config,
            actionUpdater: this.isResumeEnabled
        });
        
        this.action_pause = new Ext.Action({
            text: String.format(_('Pause upload'), config.nodeName),
            iconCls: 'action_pause',
            handler: this.onPause,
            actionUpdater: this.isPauseEnabled,
            scope: this.config
        });
        
        this.action_download = new Ext.Action({
            text: String.format(_('Download'), config.nodeName),
            iconCls: 'action_filemanager_save_all',
            handler: this.downloadFile,
            actionUpdater: this.isDownloadEnabled,
            scope: this.config
        });
        
        var items = [];
        for (var i=0; i < config.actions.length; i++) {
            switch(config.actions[i]) {
                case 'add':
                    items.push(this.action_add);
                    break;
                case 'delete':                    
                    items.push(this.action_delete);
                    break;
                case 'rename':
                    items.push(this.action_rename);
                    break;
                case 'changecolor':
                    items.push(this.action_changecolor);
                    break;
                case 'grants':
                    items.push(this.action_grants);
                    break;
                case 'reload':
                    items.push(this.action_reload);
                    break;
                case 'resume':
                    items.push(this.action_resume);
                    break;
                case 'pause':
                    items.push(this.action_pause);
                    break;
                case 'download':
                    items.push(this.action_download);
                    break;
                default:
                    // add custom actions
                    items.push(new Ext.Action(config.actions[i]));
            }
        }

             
        /******************* return menu **********************/
        
        return new Ext.menu.Menu({
            items: items
        });
    },
    
    /**
     * create tree node
     */
    addNode: function() {
        Ext.MessageBox.prompt(String.format(_('New {0}'), this.nodeName), String.format(_('Please enter the name of the new {0}:'), this.nodeName), function(_btn, _text) {
            if( this.scope.ctxNode && _btn == 'ok') {
                if (! _text) {
                    Ext.Msg.alert(String.format(_('No {0} added'), this.nodeName), String.format(_('You have to supply a {0} name!'), this.nodeName));
                    return;
                }
                Ext.MessageBox.wait(_('Please wait'), String.format(_('Creating {0}...' ), this.nodeName));
                var parentNode = this.scope.ctxNode;
                
                var params = {
                    method: this.backend + '.add' + this.backendModel,
                    name: _text
                };
                
                // TODO try to generalize this and move app specific stuff to app
                
                if (this.backendModel == 'Node') {
                    params.application = this.scope.app.appName || this.scope.appName;                            
                    var filename = parentNode.attributes.nodeRecord.data.path + '/' + _text;
                    params.filename = filename;
                    params.type = 'folder';
                    params.method = this.backend + ".createNode";
                }
                else if (this.backendModel == 'Container') {
                    params.application = this.scope.app.appName || this.scope.appName;
                    params.containerType = Tine.Tinebase.container.path2type(parentNode.attributes.path);
                } 
                else if (this.backendModel == 'Folder') {
                    var parentFolder = Tine.Tinebase.appMgr.get('Felamimail').getFolderStore().getById(parentNode.attributes.folder_id);
                    params.parent = parentFolder.get('globalname');
                    params.accountId = parentFolder.get('account_id');
                }
                
                Ext.Ajax.request({
                    params: params,
                    scope: this,
                    success: function(result, request){
                        var nodeData = Ext.util.JSON.decode(result.responseText);

                        // TODO add + icon if it wasn't expandable before
                        if(nodeData.type == 'folder') {
                            var nodeData = Ext.util.JSON.decode(result.responseText);

                            var app = Tine.Tinebase.appMgr.get(this.scope.app.appName);
                            var newNode = app.getMainScreen().getWestPanel().getContainerTreePanel().createTreeNode(nodeData, parentNode);
                            parentNode.appendChild(newNode);
                        }
                        else {
                            var newNode = this.scope.loader.createNode(nodeData);
                            parentNode.appendChild(newNode);
                        }
                        
                        parentNode.expand();
                        this.scope.fireEvent('containeradd', nodeData);
                                              
                        Ext.MessageBox.hide();
                    },
                    failure: function(result, request) {
                        var nodeData = Ext.util.JSON.decode(result.responseText);
                        
                        var appContext = Tine[this.scope.app.appName];
                        if(appContext && appContext.handleRequestException) {
                            appContext.handleRequestException(nodeData.data);
                        }
                    }
                });
                
            }
        }, this);
    },
    
    /**
     * rename tree node
     */
    renameNode: function() {
        if (this.scope.ctxNode) {
                       
            var node = this.scope.ctxNode;
            Ext.MessageBox.show({
                title: 'Rename ' + this.nodeName,
                msg: String.format(_('Please enter the new name of the {0}:'), this.nodeName),
                buttons: Ext.MessageBox.OKCANCEL,
                value: node.text,
                fn: function(_btn, _text){
                    if (_btn == 'ok') {
                        if (! _text) {
                            Ext.Msg.alert(String.format(_('Not renamed {0}'), this.nodeName), String.format(_('You have to supply a {0} name!'), this.nodeName));
                            return;
                        }
                        
                        var params = {
                            method: this.backend + '.rename' + this.backendModel,
                            newName: _text
                        };
                        
                        params.application = this.scope.app.appName || this.scope.appName;                                

                        if (this.backendModel == 'Node') {
                            var filename = node.attributes.path;
                            params.sourceFilenames = [filename];
                            
                            var targetFilename = "/";
                            var sourceSplitArray = filename.split("/");
                            for (var i=1; i<sourceSplitArray.length-1; i++) {
                                targetFilename += sourceSplitArray[i] + '/'; 
                            }
                            
                            params.destinationFilenames = [targetFilename + _text];
                            params.method = this.backend + '.moveNodes';
                        }
                        
                        // TODO try to generalize this
                        if (this.backendModel == 'Container') {
                        	params.containerId = node.attributes.container.id;
                        } else if (this.backendModel == 'Folder') {
                            var folder = Tine.Tinebase.appMgr.get('Felamimail').getFolderStore().getById(node.attributes.folder_id);
                            params.oldGlobalName = folder.get('globalname');
                            params.accountId = folder.get('account_id');
                        }
                        
                        Ext.Ajax.request({
                            params: params,
                            scope: this,
                            success: function(_result, _request){
                        	
                        		var nodeData = Ext.util.JSON.decode(_result.responseText);                        		
                        		node.setText(_text);
                                
                                this.scope.fireEvent('containerrename', nodeData, node, _text);                               
                                                              
                            },
                            failure: function(result, request) {
                                var nodeData = Ext.util.JSON.decode(result.responseText);
                                
                                var appContext = Tine[this.scope.app.appName];
                                if(appContext && appContext.handleRequestException) {
                                    appContext.handleRequestException(nodeData.data);
                                }
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
    
    /**
     * delete tree node
     */
    deleteNode: function() {
        
        if (this.scope.ctxNode) {
            var node = this.scope.ctxNode;
            
            Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete the {0} "{1}"?'), this.nodeName, node.text), function(_btn){
                if ( _btn == 'yes') {
                    
                    var params = {
                        method: this.backend + '.delete' + this.backendModel
                    };
                    
                    if (this.backendModel == 'Node') {
                                           
                        var filenames = [node.attributes.path];                                              
                        params.application = this.scope.app.appName || this.scope.appName;    
                        params.filenames = filenames;
                        params.method = this.backend + ".deleteNodes";
                    
                    } else if (this.backendModel == 'Container') {
                        params.containerId = node.attributes.container.id;
                    } else if (this.backendModel == 'Folder') {
                        var folder = Tine.Tinebase.appMgr.get('Felamimail').getFolderStore().getById(node.attributes.folder_id);
                        params.folder = folder.get('globalname');
                        params.accountId = folder.get('account_id');
                    } else {
                        // use default json api style
                        params.ids = [node.id];
                        params.method = params.method + 's';
                    }
                    
                    Ext.Ajax.request({
                        params: params,
                        scope: this,
                        success: function(_result, _request){
                              
                            if(node) {
                                if(node.isSelected()) {
                                    this.scope.getSelectionModel().select(node.parentNode);
                                    this.scope.fireEvent('click', node.parentNode, Ext.EventObject.setEvent());
                                }

                                node.remove();
                                if (this.backendModel == 'Container') {
                                    this.scope.fireEvent('containerdelete', node.attributes.container);
                                } else if (this.backendModel == 'Node') {
                                    this.scope.fireEvent('containerdelete', node);
                                } else {
                                    this.scope.fireEvent('containerdelete', node.attributes);
                                }
                            }
                           
                        },
                        failure: function(result, request) {
                            var nodeData = Ext.util.JSON.decode(result.responseText);
                            
                            var appContext = Tine[this.scope.app.appName];
                            if(appContext && appContext.handleRequestException) {
                                appContext.handleRequestException(nodeData.data);
                            }
                        }
                    });
                }
            }, this);
        }
    },
    
    /**
     * change tree node color
     */
    changeNodeColor: function(cp, color) {
        if (this.scope.ctxNode) {
            var node = this.scope.ctxNode;
            node.getUI().addClass("x-tree-node-loading");
                Ext.Ajax.request({
                    params: {
                        method: this.backend + '.set' + this.backendModel + 'Color',
                        containerId: node.attributes.container.id,
                        color: '#' + color
                    },
                    scope: this,
                    success: function(_result, _request){
                        var nodeData = Ext.util.JSON.decode(_result.responseText);
                        node.getUI().colorNode.setStyle({color: nodeData.color});
                        node.attributes.color = nodeData.color;
                        this.scope.fireEvent('containercolorset', nodeData);
                        node.getUI().removeClass("x-tree-node-loading");
                    },
                    failure: function(result, request) {
                        var nodeData = Ext.util.JSON.decode(result.responseText);
                        
                        var appContext = Tine[this.scope.app.appName];
                        if(appContext && appContext.handleRequestException) {
                            appContext.handleRequestException(nodeData.data);
                        }
                    }
                });
        
        }
    },
    
    /**
     * manage permissions
     * 
     */
    managePermissions: function() {

        if (this.scope.ctxNode) {
            var node = this.scope.ctxNode;
                        
            var grantsContainer;
            if(node.attributes.container) {
                grantsContainer = node.attributes.container;
            }
            else if(node.attributes.nodeRecord && node.attributes.nodeRecord.data.name) {
                grantsContainer = node.attributes.nodeRecord.data.name;
            }
            
            var window = Tine.widgets.container.GrantsDialog.openWindow({
                title: String.format(_('Manage Permissions for {0} "{1}"'), this.nodeName, Ext.util.Format.htmlEncode(grantsContainer.name.name)),
                containerName: this.nodeName,
                grantContainer: grantsContainer,
                app: this.scope.app.appName
            });
        }
        
    },
    
    /**
     * manage properties
     * 
     */
    manageProperties: function() {
        if (this.scope.ctxNode) {
            var node = this.scope.ctxNode;
            
            var grantsContainer;
            if(node.attributes.container) {
                grantsContainer = node.attributes.container;
            }
            else if(node.attributes.nodeRecord && node.attributes.nodeRecord.data.name) {
                grantsContainer = node.attributes.nodeRecord.data.name;
            }
            
            var window = Tine.widgets.container.PropertiesDialog.openWindow({
                title: String.format(_('Properties for {0} "{1}"'), this.nodeName, Ext.util.Format.htmlEncode(grantsContainer.name.name)),
                containerName: this.nodeName,
                grantContainer: grantsContainer,
                app: this.scope.app.appName
            });
        }
    },
    
    /**
     * reload node
     */
    reloadNode: function() {
        if (this.scope.ctxNode) {
            var tree = this.scope;
            this.scope.ctxNode.reload(function(node) {
                node.expand();
                node.select();
                // update grid
                tree.filterPlugin.onFilterChange();
            });                    
        }
    }
    
};
