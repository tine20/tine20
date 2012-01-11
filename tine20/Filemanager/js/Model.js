/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager.Model');


/**
 * @namespace   Tine.Filemanager.Model
 * @class       Tine.Filemanager.Model.Node
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Filemanager.Model.Node = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'name' },
    { name: 'path' },
    // TODO add more record fields here
    // tine 2.0 notes + tags
    { name: 'size' },
    { name: 'revision' },
    { name: 'type' },
    { name: 'contenttype' },
    { name: 'description' },
    { name: 'creation_time' },
    { name: 'account_grants' }
]), {
    appName: 'Filemanager',
    modelName: 'Node',
    idProperty: 'id',
    titleProperty: 'title',
    recordName: 'user file', // app.i18n._('user file')
    recordsName: 'user files', // app.i18n._('user files')
    containerProperty: 'container_id',
    containerName: 'user file folder', // app.i18n._('user file folder')
    containersName: 'user file folders', // app.i18n._('user file folders')
    
    /**
     * checks whether creating folders is allowed
     */
    isCreateFolderAllowed: function() {
        var grants = this.get('account_grants');
        
        if(!grants && this.data.id !== 'personal' && this.data.id !== 'shared') {
            return false;
        }
        else if(!grants && (this.data.id === 'personal' 
            || (this.data.id === 'shared' && (Tine.Tinebase.common.hasRight('admin', this.appName) 
                    || Tine.Tinebase.common.hasRight('manage_shared_folders', this.appName))))) {
            return true;
        }
        
        return this.get('type') == 'file' ? grants.editGrant : grants.addGrant;
    },
    
    isDropFilesAllowed: function() {
        var grants = this.get('account_grants');
        if(!grants) {
            return false;
        }
        else if(!grants.addGrant) {
            return false;
        }
        return true;
    },

    isDragable: function() {
        var grants = this.get('account_grants');

        if(!grants) {
            return false;
        }
        
        if(this.get('id') === 'personal' || this.get('id') === 'shared' || this.get('id') === 'otherUsers') {
            return false;
        }
        
        return true;
    }
});



/**
 * default ExampleRecord backend
 */
Tine.Filemanager.fileRecordBackend =  new Tine.Tinebase.data.RecordProxy({
    appName: 'Filemanager',
    modelName: 'Node',
    recordClass: Tine.Filemanager.Model.Node,
    
    /**
     * creating folder
     * 
     * @param name      folder name
     * @param options   additional options
     * @returns
     */
    createFolder: function(name, options) {
        
        options = options || {};
        var params = {
                application : this.appName,                            
                filename : name,
                type : 'folder',
                method : this.appName + ".createNode"  
        };
                    
        options.params = params;
        
        options.beforeSuccess = function(response) {
            var folder = this.recordReader(response);
            folder.set('client_access_time', new Date());
            return [folder];
        };
        
        options.success = function(result){
            var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName);
            var grid = app.getMainScreen().getCenterPanel();
            var nodeData = Ext.util.JSON.decode(result);
            var newNode = app.getMainScreen().getWestPanel().getContainerTreePanel().createTreeNode(nodeData, parentNode);
            
            var parentNode = grid.currentFolderNode;
            if(parentNode) {
                parentNode.appendChild(newNode);
            }
            grid.getStore().reload();
//            this.fireEvent('containeradd', nodeData);
        };
        
        return this.doXHTTPRequest(options);
        
    },
    
    /**
     * deleting file or folder
     * 
     * @param items     files/folders to delete
     * @param options   additional options
     * @returns
     */
    deleteItems: function(items, options) {
        
        options = options || {};
        
        var filenames = new Array();
        var nodeCount = items.length;
        for(var i=0; i<nodeCount; i++) {
            filenames.push(items[i].data.path );
        }

        var params = {
            application: this.appName,                                
            filenames: filenames,
            method: this.appName + ".deleteNodes",
            timeout: 300000 // 5 minutes
        };

        options.params = params;
        
        options.beforeSuccess = function(response) {
            var folder = this.recordReader(response);
            folder.set('client_access_time', new Date());
            return [folder];
        };
        
        options.success = (function(result){
            var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName),
                grid = app.getMainScreen().getCenterPanel(),
                treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel(),
                nodeData = this.items;
            
            for(var i=0; i<nodeData.length; i++) {
                var treeNode = treePanel.getNodeById(nodeData[i].id);
                if(treeNode) {
                    treeNode.parentNode.removeChild(treeNode);
                }              
            }
            
            grid.getStore().remove(nodeData);
            grid.selectionModel.deselectRange(0, grid.getStore().getCount());
//            this.fireEvent('containerdelete', nodeData);

        }).createDelegate({items: items});
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * copy/move folder/files to a folder
     * 
     * @param items files/folders to copy
     * @param targetPath
     * @param move
     */
    
    copyNodes : function(items, target, move, params) {
        
        var containsFolder = false,
            message = '',
            app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName);

        
        if(!params) {
        
            if(!target || !items || items.length < 1) {
                return false;
            }

            var sourceFilenames = new Array(),
            destinationFilenames = new Array(),
            forceOverwrite = false,
            treeIsTarget = false,
            treeIsSource = false,
            targetPath;

            if(target.data) {
                targetPath = target.data.path;
            }
            else {
                targetPath = target.attributes.path;
                treeIsTarget = true;
            }

            for(var i=0; i<items.length; i++) {

                var item = items[i];            
                var itemData = item.data;
                if(!itemData) {
                    itemData = item.attributes;
                    treeIsSource = true;
                }
                sourceFilenames.push(itemData.path);

                var itemName = itemData.name;
                if(typeof itemName == 'object') {
                    itemName = itemName.name;
                }

                destinationFilenames.push(targetPath + '/' + itemName);
                if(itemData.type == 'folder') {
                    containsFolder = true;
                }
            };

            var method = "Filemanager.copyNodes",
                message = app.i18n._('Copying data .. {0}');
            if(move) {
                method = "Filemanager.moveNodes";
                message = app.i18n._('Moving data .. {0}');
            }
 
            params = {
                    application: this.appName,                                
                    sourceFilenames: sourceFilenames,
                    destinationFilenames: destinationFilenames,
                    forceOverwrite: forceOverwrite,
                    method: method
            };

        }
        else {            
            message = app.i18n._('Copying data .. {0}');
            if(params.method == 'Filemanager.moveNodes') {
                message = app.i18n._('Moving data .. {0}');
            }
        }
        
        this.loadMask = new Ext.LoadMask(app.getMainScreen().getCenterPanel().getEl(), {msg: String.format(_('Please wait')) + '. ' + String.format(message, '' )});
        app.getMainScreen().getWestPanel().setDisabled(true);
        app.getMainScreen().getNorthPanel().setDisabled(true);
        this.loadMask.show();
        
        Ext.Ajax.request({
            params: params,
            timeout: 300000, // 5 minutes
            scope: this,
            success: function(result, request){
                
                this.loadMask.hide();
                app.getMainScreen().getWestPanel().setDisabled(false);
                app.getMainScreen().getNorthPanel().setDisabled(false);

                var nodeData = Ext.util.JSON.decode(result.responseText),
                    treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel(),
                    grid = app.getMainScreen().getCenterPanel();
             
                // Tree refresh
                if(treeIsTarget) {
                    
                    for(var i=0; i<items.length; i++) {
                        
                        var nodeToCopy = items[i];
                        
                        if(nodeToCopy.data && nodeToCopy.data.type !== 'folder') {
                            continue;
                        }

                        if(move) {   
                            var copiedNode = treePanel.cloneTreeNode(nodeToCopy, target),
                                nodeToCopyId = nodeToCopy.id,
                                removeNode = treePanel.getNodeById(nodeToCopyId);
                            
                            if(removeNode && removeNode.parentNode) {
                                removeNode.parentNode.removeChild(removeNode);
                            }

                            target.appendChild(copiedNode); 
                            copiedNode.setId(nodeData[i].id);    
                        } 
                        else {
                            var copiedNode = treePanel.cloneTreeNode(nodeToCopy, target);
                            target.appendChild(copiedNode);  
                            copiedNode.setId(nodeData[i].id);

                        }
                    }
                }
               
                                 
                // Grid refresh
                grid.getStore().reload();
            },
            failure: function(response, request) {
                var nodeData = Ext.util.JSON.decode(response.responseText),
                	request = Ext.util.JSON.decode(request.jsonData);
                
                this.loadMask.hide();
                app.getMainScreen().getWestPanel().setDisabled(false);
                app.getMainScreen().getNorthPanel().setDisabled(false);

                Tine.Filemanager.fileRecordBackend.handleRequestException(nodeData.data, request);
            }
        });
               
    },
    
    /**
     * upload file 
     * 
     * @param {} params Request parameters
     * @param String uploadKey
     * @param Boolean addToGridStore 
     */
    createNode: function(params, uploadKey, addToGridStore) {
        var app = Tine.Tinebase.appMgr.get('Filemanager'),
            grid = app.getMainScreen().getCenterPanel(),
            gridStore = grid.getStore();
        
        params.application = 'Filemanager';                              
        params.method = 'Filemanager.createNode';
        params.uploadKey = uploadKey;
        params.addToGridStore = addToGridStore;
        
        var onSuccess = (function(result, request){ 
            
            var nodeData = Ext.util.JSON.decode(response.responseText),
            	fileRecord = Tine.Tinebase.uploadManager.upload(this.uploadKey);  

            if(addToGridStore) {
            	var recordToRemove = gridStore.query('name', fileRecord.get('name'));
            	if(recordToRemove.items[0]) {
            		gridStore.remove(recordToRemove.items[0]);
            	}

            	fileRecord = Tine.Filemanager.fileRecordBackend.updateNodeRecord(nodeData[i], fileRecord);
            	var nodeRecord = new Tine.Filemanager.Model.Node(nodeData[i]);
            	
            	nodeRecord.fileRecord = fileRecord;
            	gridStore.add(nodeRecord);

            }           
        }).createDelegate({uploadKey: uploadKey, addToGridStore: addToGridStore});
        
        var onFailure = (function(response, request) {
            
            var nodeData = Ext.util.JSON.decode(response.responseText),
            request = Ext.util.JSON.decode(request.jsonData);
            nodeData.data.uploadKey = this.uploadKey;
            nodeData.data.addToGridStore = this.addToGridStore;
            Tine.Filemanager.fileRecordBackend.handleRequestException(nodeData.data, request);
            
        }).createDelegate({uploadKey: uploadKey, addToGridStore: addToGridStore});
        
        Ext.Ajax.request({
            params: params,
            timeout: 300000, // 5 minutes
            scope: this,
            success: onSuccess || Ext.emptyFn,
            failure: onFailure || Ext.emptyFn
        });
    },
    
    /**
     * upload files
     * 
     * @param {} params Request parameters
     * @param [] uploadKeyArray
     * @param Boolean addToGridStore 
     */
    createNodes: function(params, uploadKeyArray, addToGridStore) {
        var app = Tine.Tinebase.appMgr.get('Filemanager'),
            grid = app.getMainScreen().getCenterPanel(),
            gridStore = grid.store;
        
        params.application = 'Filemanager';                              
        params.method = 'Filemanager.createNodes';
        params.uploadKeyArray = uploadKeyArray;
        params.addToGridStore = addToGridStore;

        
        var onSuccess = (function(response, request){ 
                       
            var nodeData = Ext.util.JSON.decode(response.responseText);
        	
            for(var i=0; i<this.uploadKeyArray.length; i++) {
                var fileRecord = Tine.Tinebase.uploadManager.upload(this.uploadKeyArray[i]);  
    
                if(addToGridStore) {
        
                	var recordToRemove = gridStore.query('name', fileRecord.get('name'));
                	if(recordToRemove.items[0]) {
                		gridStore.remove(recordToRemove.items[0]);
                	}

                	fileRecord = Tine.Filemanager.fileRecordBackend.updateNodeRecord(nodeData[i], fileRecord);
                	var nodeRecord = new Tine.Filemanager.Model.Node(nodeData[i]);
                	
                	nodeRecord.fileRecord = fileRecord;
                	gridStore.add(nodeRecord);
                    
                }   
            }

        }).createDelegate({uploadKeyArray: uploadKeyArray, addToGridStore: addToGridStore});
        
        var onFailure = (function(response, request) {
            
            var nodeData = Ext.util.JSON.decode(response.responseText),
            request = Ext.util.JSON.decode(request.jsonData);
            nodeData.data.uploadKeyArray = this.uploadKeyArray;
            nodeData.data.addToGridStore = this.addToGridStore;
            Tine.Filemanager.fileRecordBackend.handleRequestException(nodeData.data, request);
            
        }).createDelegate({uploadKeyArray: uploadKeyArray, addToGridStore: addToGridStore});
        
        
        Ext.Ajax.request({
            params: params,
            timeout: 300000, // 5 minutes
            scope: this,
            success: onSuccess || Ext.emptyFn,
            failure: onFailure || Ext.emptyFn
        });
        
        
    },
    
    /**
     * is automatically called in generic GridPanel
     */
    saveRecord : function() {
        // NOOP
    },
    
    /**
     * exception handler for this proxy
     * 
     * @param {Tine.Exception} exception
     */
    handleRequestException: function(exception, request) {
        Tine.Filemanager.handleRequestException(exception, request);
    },
    
    /**
     * updates given record with nodeData from from response
     */
    updateNodeRecord : function(nodeData, nodeRecord) {
    	
    	for(var field in nodeData) { 		
    		nodeRecord.set(field, nodeData[field]);
    	};
    	
    	return nodeRecord;
    }
    

});


/**
 * get filtermodel of contact model
 * 
 * @namespace Tine.Filemanager.Model
 * @static
 * @return {Object} filterModel definition
 */ 
Tine.Filemanager.Model.Node.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Filemanager');
       
	return [ 	
	    {label : _('Quick search'), field : 'query', operators : [ 'contains' ]}, 
	    {label: app.i18n._('Type'), field: 'type'},
	    {label: app.i18n._('Contenttype'), field: 'contenttype'},
        {label: app.i18n._('Creation Time'), field: 'creation_time', valueType: 'date'},
	    {filtertype : 'tine.filemanager.pathfiltermodel', app : app}, 
	    {filtertype : 'tinebase.tag', app : app} 
	];
};