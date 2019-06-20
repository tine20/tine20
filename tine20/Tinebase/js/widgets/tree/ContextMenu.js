/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @cfg {Tine.widgets.ActionManager} actionMgr
     */
    actionMgr: null,

    /**
     * create new Ext.menu.Menu with actions
     * 
     * @param {} config has the node name, actions, etc.
     * @param [] additional menu plugins
     * @return {}
     */
    getMenu: function(config, plugins) {
        
        this.config = config;
                
        /****************** create ITEMS array ****************/
              
        this.action_add = new Ext.Action({
            text: String.format(i18n._('Add {0}'), this.config.nodeName),
            iconCls: 'action_add',
            handler: this.addNode,
            requiredGrant: 'addGrant',
            scope: this.config
        });
        
        this.action_rename = new Ext.Action({
            text: String.format(i18n._('Rename {0}'), this.config.nodeName),
            iconCls: 'action_rename',
            handler: this.renameNode,
            scope: this.config,
            requiredGrant: 'editGrant',
            allowMultiple: false
        });
        
        this.action_delete = new Ext.Action({
            text: String.format(i18n.ngettext('Delete {0}', 'Delete {0}', 1), this.config.nodeName),
            iconCls: 'action_delete',
            handler: this.deleteNode,
            scope: this.config,
            requiredGrant: 'deleteGrant',
            allowMultiple: true
        });
        
        this.action_grants = new Ext.Action({
            text: String.format(i18n._('Manage {0} Permissions'), this.config.nodeName),
            iconCls: 'action_managePermissions',
            handler: this.managePermissions,
            requiredGrant: 'editGrant',
            scope: this.config
        });
        
        this.action_properties = new Ext.Action({
            text: String.format(i18n._('{0} Properties'), this.config.nodeName),
            iconCls: 'action_manageProperties',
            handler: this.manageProperties,
            requiredGrant: 'readGrant',
            scope: this.config
        });
        
        // TODO is edit grant required?
        this.action_changecolor = new Ext.Action({
            text: String.format(i18n._('Set {0} color'), this.config.nodeName),
            iconCls: 'action_changecolor',
//            requiredGrant: 'editGrant',
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
            text: String.format(i18n._('Reload {0}'), this.config.nodeName),
            iconCls: 'x-tbar-loading',
            handler: this.reloadNode,
            scope: this.config
        });
        
        // TODO move the next 5 to Filemanager!
        this.action_resume = new Ext.Action({
            text: String.format(i18n._('Resume upload'), config.nodeName),
            iconCls: 'action_resume',
            handler: this.onResume,
            scope: this.config,
            actionUpdater: this.isResumeEnabled
        });
        this.action_pause = new Ext.Action({
            text: String.format(i18n._('Pause upload'), config.nodeName),
            iconCls: 'action_pause',
            handler: this.onPause,
            actionUpdater: this.isPauseEnabled,
            scope: this.config
        });
        
        this.action_download = new Ext.Action({
            text: String.format(i18n._('Download'), config.nodeName),
            iconCls: 'action_filemanager_save_all',
            handler: this.downloadFile,
            actionUpdater: this.isDownloadEnabled,
            scope: this.config
        });
        
        this.action_publish = new Ext.Action({
            text: String.format(i18n._('Publish'), config.nodeName),
            iconCls: 'action_publish',
            handler: this.publishFile,
            actionUpdater: true,
            scope: this.config
        });

        var items = [],
            method = '',
            action;

        for (var i=0; i < config.actions.length; i++) {
            action = config.actions[i];

            if (Ext.isString(action)) {
                method = 'action_' + action;
                if (this.actionMgr && this.actionMgr.has(action)) {
                    items.push(this.actionMgr.get(action));
                } else if (this[method]) {
                    items.push(this[method]);
                } else {
                    Tine.log.warn('Tine.widgets.tree.ContextMenu.getMenu: action "' + action + '" is not definded');
                    continue;
                }

            }

            else if (action && action.isAction) {
                items.push(action);
            }

            else if (Ext.isObject(action)) {
                items.push(new Ext.Action(action));
            }

            else {
                Tine.log.warn('Tine.widgets.tree.ContextMenu.getMenu: can\'t cope with action :');
                Tine.log.warn(action);
            }
        }

        /******************* return menu **********************/

        var menuPlugins = [{
            ptype: 'ux.itemregistry',
            key:   'Tinebase-MainContextMenu'
        }];

        if (plugins) {
            menuPlugins = menuPlugins.concat(plugins);
        }
        
        return new Ext.menu.Menu({
            plugins: menuPlugins,
            items: items
        });
    },
    
    /**
     * create tree node
     */
    addNode: function() {
        Ext.MessageBox.prompt(String.format(i18n._('New {0}'), this.nodeName), String.format(i18n._('Please enter the name of the new {0}:'), this.nodeName), function(_btn, _text) {
            if( this.scope.ctxNode && _btn == 'ok') {
                if (! _text) {
                    Ext.Msg.alert(String.format(i18n._('No {0} added'), this.nodeName), String.format(i18n._('You have to supply a {0} name!'), this.nodeName));
                    return;
                }
                Ext.MessageBox.wait(i18n._('Please wait'), String.format(i18n._('Creating {0}...' ), this.nodeName));
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
                    params.modelName = this.scope.app.getMainScreen().getActiveContentType();
                    if(params.modelName == '') params.modelName = this.scope.contextModel;
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

                        if (nodeData.length == 0) {
                            Tine.log.err('Server returned empty node data!');
                            Ext.MessageBox.hide();
                            return;
                        }

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
                title: String.format(i18n._('Rename {0}'), this.nodeName),
                msg: String.format(i18n._('Please enter the new name of the {0}:'), this.nodeName),
                buttons: Ext.MessageBox.OKCANCEL,
                value: Ext.util.Format.htmlDecode(node.attributes.longName || node.text),
                fn: function(_btn, _text){
                    if (_btn == 'ok') {
                        if (! _text) {
                            Ext.Msg.alert(String.format(i18n._('Not renamed {0}'), this.nodeName), String.format(i18n._('You have to supply a {0} name!'), this.nodeName));
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
                                node.setText(Ext.util.Format.htmlEncode(_text));
                                
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
            
            Tine.log.debug('Tine.widgets.tree.ContextMenu::deleteNode()');
            Tine.log.debug(node);
            
            Ext.MessageBox.confirm(i18n._('Confirm'), String.format(i18n._('Do you really want to delete the {0} "{1}"?'), this.nodeName, node.text), function(_btn){
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
            var node = this.scope.ctxNode,
                grantsContainer;
            if(node.attributes.nodeRecord && node.attributes.nodeRecord.data.name) {
                grantsContainer = node.attributes.nodeRecord.data.name;
            } else if(node.attributes.container) {
                grantsContainer = node.attributes.container;
            }
            
            var window = Tine.widgets.container.GrantsDialog.openWindow({
                title: String.format(i18n._('Manage Permissions for {0} "{1}"'), this.nodeName, Ext.util.Format.htmlEncode(grantsContainer.name)),
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
            var node = this.scope.ctxNode,
                grantsContainer,
                ctxNodeName;
            if (node.attributes.nodeRecord && node.attributes.nodeRecord.data.name) {
                grantsContainer = node.attributes.nodeRecord.data.name;
            } else if(node.attributes.container) {
                grantsContainer = node.attributes.container;
            }

            ctxNodeName = grantsContainer.name ? grantsContainer.name : grantsContainer;
            
            var window = Tine.widgets.container.PropertiesDialog.openWindow({
                title: String.format(i18n._('Properties for {0} "{1}"'), this.nodeName, Ext.util.Format.htmlEncode(ctxNodeName)),
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
