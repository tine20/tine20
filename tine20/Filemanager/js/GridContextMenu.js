
/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

    
Tine.Filemanager.GridContextMenu = {    
    /**
     * create tree node
     */
    addNode: function() {
        Tine.log.debug("grid add");
    },

    /**
     * rename tree node
     */

    renameNode: function() {
        if (this.scope.ctxNode) {

            var node = this.scope.ctxNode[0];

            var nodeText = node.data.name;
            if(typeof nodeText == 'object') {
                nodeText = nodeText.name;
            }
            
            Ext.MessageBox.show({
                title: 'Rename ' + this.nodeName,
                msg: String.format(_('Please enter the new name of the {0}:'), this.nodeName),
                buttons: Ext.MessageBox.OKCANCEL,
                value: nodeText,
                fn: function(_btn, _text){
                    if (_btn == 'ok') {
                        if (! _text) {
                            Ext.Msg.alert(String.format(_('Not renamed {0}'), this.nodeName), String.format(_('You have to supply a {0} name!'), this.nodeName));
                            return;
                        }
                        Ext.MessageBox.wait(_('Please wait'), String.format(_('Updating {0} "{1}"'), this.nodeName, nodeText));

                        var params = {
                                method: this.backend + '.rename' + this.backendModel,
                                newName: _text
                        };

                        if (this.backendModel == 'Node') {
                            params.application = this.scope.app.appName || this.scope.appName;                                
                            var filename = node.data.path;
                            params.sourceFilenames = [filename];

                            var targetFilename = "/";
                            var sourceSplitArray = filename.split("/");
                            for (var i=1; i<sourceSplitArray.length-1; i++) {
                                targetFilename += sourceSplitArray[i] + '/'; 
                            }

                            params.destinationFilenames = [targetFilename + _text];
                            params.method = this.backend + '.moveNodes';
                        }

                        Ext.Ajax.request({
                            params: params,
                            scope: this,
                            success: function(_result, _request){
                                var nodeData = Ext.util.JSON.decode(_result.responseText)[0];
                                this.scope.fireEvent('containerrename', nodeData);
                                Ext.MessageBox.hide();

                                // TODO: im event auswerten
                                if (this.backendModel == 'Node') {
                                    var grid = this.scope.app.getMainScreen().getCenterPanel();
                                    grid.getStore().reload();
                                    
                                    var nodeName = nodeData.name;
                                    if(typeof nodeName == 'object') {
                                        nodeName = nodeName.name;
                                    }
                                    
                                    var treeNode = this.scope.app.getMainScreen().getWestPanel().getContainerTreePanel().getNodeById(nodeData.id);                                 
                                    if(treeNode) {
                                        treeNode.setText(nodeName);
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
            var nodes = this.scope.ctxNode;

            var nodeName = "";
            if(nodes && nodes.length) {
                for(var i=0; i<nodes.length; i++) {
                    var currNodeData = nodes[i].data;
                    
                    if(typeof currNodeData.name == 'object') {
                        nodeName += currNodeData.name.name + ', ';    
                    }
                    else {
                        nodeName += currNodeData.name + ', ';  
                    }
                }
                if(nodeName.length > 0) {
                    nodeName = nodeName.substring(0, nodeName.length - 2);
                }
            }
            
            Ext.MessageBox.confirm(_('Confirm'), String.format(this.scope.app.i18n._('Do you really want to delete "{0}"?'), nodeName), function(_btn){
                if ( _btn == 'yes') {
                    Ext.MessageBox.wait(_('Please wait'), String.format(_('Deleting {0} "{1}"' ), this.nodeName , nodeName));
                    
                    var params = {
                        method: this.backend + '.delete' + this.backendModel
                    };
                    
                    if (this.backendModel == 'Node') {
                        
                        var filenames = new Array();
                        if(nodes) {
                             for(var i=0; i<nodes.length; i++) {
                                 filenames.push(nodes[i].data.path);
                             }   
                        }
                        params.application = this.scope.app.appName || this.scope.appName;    
                        params.filenames = filenames;
                        params.method = this.backend + ".deleteNodes";
                    }
                    
                    Ext.Ajax.request({
                        params: params,
                        scope: this,
                        success: function(_result, _request){
                              
                           if(nodes &&  this.backendModel == 'Node') {
                               var treePanel = this.scope.app.getMainScreen().getWestPanel().getContainerTreePanel();
                               for(var i=0; i<nodes.length; i++){
                                    this.scope.fireEvent('containerdelete', nodes[i].data.container_id);                                    
                                    treePanel.fireEvent('containerdelete', nodes[i].data.container_id);

                                    // TODO: in EventHandler auslagern
                                  var treeNode = treePanel.getNodeById(nodes[i].id);
                                  if(treeNode) {
                                      treeNode.parentNode.removeChild(treeNode);
                                  }
                                    
                                    
                                }
                                this.scope.app.getMainScreen().getCenterPanel().getStore().reload();

                           }
                           
                            // TODO: evaluate in event handler
                            if (this.backendModel == 'Node') {
                                this.scope.app.mainScreen.GridPanel.getStore().reload();
                            }
                            
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
        }
    },
    
    /**
     * change tree node color
     */
    changeNodeColor: function(cp, color) {
        Tine.log.debug("grid change color");
        
        
    },
    
    /**
     * manage permissions
     * 
     */
    managePermissions: function() {
        Tine.log.debug("grid manage permissions");
    },
    
    /**
     * reload node
     */
    reloadNode: function() {
        Tine.log.debug("grid reload node");
    },
    
    /**
     * download file
     * 
     * @param {} button
     * @param {} event
     */
    downloadFile: function(button, event) {
        
        var grid = this.scope.app.getMainScreen().getCenterPanel();
        var selectedRows = grid.selectionModel.getSelections();
        
        var fileRow = selectedRows[0];
               
        var downloadPath = fileRow.data.path;
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Filemanager.downloadFile',
                requestType: 'HTTP',
                path: downloadPath
            }
        }).start();
    },
    
    /**
     * is the download context menu option visible / enabled
     * 
     * @param action
     * @param grants
     * @param records
     */
    isDownloadEnabled: function(action, grants, records) {
        for(var i=0; i<records.length; i++) {
            if(records[i].data.type === 'folder') {
                action.hide();
                return;
            }
        }
        action.show();
        
        var grid = this.scope.app.getMainScreen().getCenterPanel();
        var selectedRows = grid.selectionModel.getSelections(); 
        
        if(selectedRows.length > 1) {
            action.setDisabled(true);
        }
        else {
            action.setDisabled(false);
        }
        
    },
    
    /**
     * on pause
     * @param {} button
     * @param {} event
     */
    onPause: function (button, event) {     

        var grid = this.scope.app.getMainScreen().getCenterPanel();
        var gridStore = grid.store;
        gridStore.suspendEvents();
        var selectedRows = this.scope.selectionModel.getSelections(); 
        for(var i=0; i < selectedRows.length; i++) {
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            upload.setPaused(true);
        }       
        gridStore.resumeEvents();
        grid.actionUpdater.updateActions(gridStore);  
    },

    
    /**
     * on resume
     * @param {} button
     * @param {} event
     */
    onResume: function (button, event) {
        
        var grid = this.scope.app.getMainScreen().getCenterPanel();
        var gridStore = grid.store;
        gridStore.suspendEvents();
        var selectedRows = this.scope.selectionModel.getSelections();
        for(var i=0; i < selectedRows.length; i++) {
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            upload.resumeUpload();
        }
        gridStore.resumeEvents();
        grid.actionUpdater.updateActions(gridStore);  
    },
    
    /**
     * checks whether resume button shuold be enabled or disabled
     * 
     * @param action
     * @param grants
     * @param records
     */
    isResumeEnabled: function(action, grants, records) {
        
        for(var i=0; i<records.length; i++) {
            if(records[i].data.type === 'folder') {
                action.hide();
                return;
            }
        }
       
        var grid = this.scope.app.getMainScreen().getCenterPanel();
        var selectedRows = grid.selectionModel.getSelections(); 
        
        for(var i=0; i < selectedRows.length; i++) {
            if(!selectedRows[i].get('status') || (selectedRows[i].get('type ') !== 'folder' &&  selectedRows[i].get('status') !== 'uploading' 
                    &&  selectedRows[i].get('status') !== 'paused' && selectedRows[i].get('status') !== 'pending')) {
                action.hide();
                return;
            }
        }
        
        action.show();
        
       
        for(var i=0; i < selectedRows.length; i++) {
            if(selectedRows[i].get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if(selectedRows[i].get('status') && selectedRows[i].get('status') !== 'paused'){               
                action.setDisabled(true);               
            }
            
        }   
    },
    
    /**
     * checks whether pause button shuold be enabled or disabled
     * 
     * @param action
     * @param grants
     * @param records
     */
    isPauseEnabled: function(action, grants, records) {
        
        for(var i=0; i<records.length; i++) {
            if(records[i].data.type === 'folder') {
                action.hide();
                return;
            }
        }
        
        var grid = this.scope.app.getMainScreen().getCenterPanel();
        var selectedRows = grid.selectionModel.getSelections(); 
       
        for(var i=0; i < selectedRows.length; i++) {
            if(!selectedRows[i].get('status') || (selectedRows[i].get('type ') !== 'folder' && selectedRows[i].get('status') !== 'paused'
                    &&  selectedRows[i].get('status') !== 'uploading' && selectedRows[i].get('status') !== 'pending')) {
                action.hide();
                return;
            }
        }
        
        action.show();
        
        for(var i=0; i < selectedRows.length; i++) {
            if(selectedRows[i].get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if(selectedRows[i].get('status') && selectedRows[i].get('status') !== 'uploading'){
                action.setDisabled(true);
            }
            
        }                 
    }  
};