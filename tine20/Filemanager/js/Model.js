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
    recordName: 'user file',
    recordsName: 'user files',
    containerProperty: 'container_id',
    containerName: 'user file folder',
    containersName: 'user file folders',
    
    isWriteable: function() {
        var grants = this.get('account_grants');
        
        if(!grants) {
            return false;
        }
        
        return this.get('type') == 'file' ? grants.editGrant : grants.addGrant;
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
        
        options.success = function(_result){
            var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName);
            var grid = app.getMainScreen().getCenterPanel();
            grid.currentFolderNode.reload();            
            grid.getStore().reload();
//            this.fireEvent('containeradd', nodeData);
            Ext.MessageBox.hide();
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
                method: this.appName + ".deleteNodes"
        };

        options.params = params;
        
        options.beforeSuccess = function(response) {
            var folder = this.recordReader(response);
            folder.set('client_access_time', new Date());
            return [folder];
        };
        
        options.success = function(_result){
            var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName);
            var grid = app.getMainScreen().getCenterPanel();
            grid.currentFolderNode.reload();            
            grid.getStore().reload();
//            this.fireEvent('containerdelete', nodeData);
            Ext.MessageBox.hide();
        };
        
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
        
        var options = {};
        
        var containsFolder = false,
            reloadParent = false;
        
        if(!params) {
        
            if(!target || !items || items.length < 1) {
                return false;
            }

            var sourceFilenames = new Array(),
            destinationFilenames = new Array(),
            forceOverwrite = false,
            treeInvolved = false,
            targetPath;

            if(target.data) {
                targetPath = target.data.path;
            }
            else {
                targetPath = target.attributes.path;
                treeInvolved = true;
            }

            for(var i=0; i<items.length; i++) {

                var item = items[i];            
                var itemData = item.data;
                if(!itemData) {
                    itemData = item.attributes;
                    reloadParent = true;
                    treeInvolved = true;
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

            var method = "Filemanager.copyNodes";
            if(move) {
                method = "Filemanager.moveNodes";
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
            reloadParent = true;
        }
        
        options.params = params;
              
        options.success = function(response) {
            
            Ext.MessageBox.hide();
           
            var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName),           
                treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel(),
                grid = app.getMainScreen().getCenterPanel();

            
            
            // Tree refresh
            if(treeInvolved) {
                
                // source parent 
                if(move) {                    
                    var nodeToMove = items[0],
                        copiedNode = treePanel.cloneTreeNode(items[0], target),
                        nodeToMoveId = nodeToMove.id;
                    
                    nodeToMove.parentNode.removeChild(nodeToMove);                                      
                    target.appendChild(copiedNode); 
                    copiedNode.setId(nodeToMoveId);    
                } 
                else {
                    var copiedNode = treePanel.cloneTreeNode(items[0], target);
                    target.appendChild(copiedNode);          
                    
                }
            }
             
         // Grid refresh
            grid.getStore().reload();
        };
        
        options.failure =  function(result) {
            var nodeData = Ext.util.JSON.decode(result.response),
                request = Ext.util.JSON.decode(result.request);
            Tine.Filemanager.fileRecordBackend.handleRequestException(nodeData.data, request);
        };
        
        Ext.MessageBox.wait(_('Please wait'), String.format(_('Moving data .. {0}' ), '' ));
        return this.doXHTTPRequest(options);
    },
    
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
	    {label: app.i18n._('user file folder'),filtertype : 'tine.filemanager.pathfiltermodel'
	        , app : app, recordClass : Tine.Filemanager.Model.Node,  defaultValue: '/'} , 
	    {filtertype : 'tinebase.tag', app : app} 
	];
};