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
    { name: 'creation_time' }
]), {
    appName: 'Filemanager',
    modelName: 'Node',
    idProperty: 'id',
    titleProperty: 'title',
    recordName: 'user file',
    recordsName: 'user files',
    containerProperty: 'container_id',
    containerName: 'user file folder',
    containersName: 'user file folders'
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
     * copy folder/files to a folder
     * 
     * @param items files/folders to copy
     * @param targetPath
     */
    copyNodes: function(items, targetPath) {
        
        var sourceFilenames = new Array(),
            destinationFilenames = new Array(),
            forceOverwrite = false,
            refreshTree = false;
        
        Ext.each(items, function(item) {
            sourceFilenames.push(item.data.path);
            destinationFilenames.push(targetPath + '/' + item.data.name);
            if(item.data.type == 'folder') {
                refreshTree = true;
            }
        }, this);
        
        Ext.MessageBox.wait(_('Please wait'), String.format(_('Copying data .. {0}' ), '' ));
        Tine.Filemanager.copyNodes(sourceFilenames, destinationFilenames, forceOverwrite, 
                (function() {
                    Ext.MessageBox.hide();
                    if(refreshTree) {
                        var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName);
                        var grid = app.getMainScreen().getCenterPanel();
                        grid.currentTreeNode.reload();
                    }
                }).createDelegate(this));
        
        
    },
    
    /**
     * move folder/files to a folder
     * 
     * @param items files/folders to copy
     * @param targetPath
     */
    moveNodes: function(items, targetPath) {
        
        var sourceFilenames = new Array(),
            destinationFilenames = new Array(),
            forceOverwrite = false,
            refreshTree = false;
        
        Ext.each(items, function(item) {
            sourceFilenames.push(item.data.path);
            destinationFilenames.push(targetPath + '/' + item.data.name);
            if(item.data.type == 'folder') {
                refreshTree = true;
            }
        }, this);
        
        Ext.MessageBox.wait(_('Please wait'), String.format(_('Moving data .. {0}' ), '' ));
        Tine.Filemanager.moveNodes(sourceFilenames, destinationFilenames, forceOverwrite, 
                (function() {
                    Ext.MessageBox.hide();
                    var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName);
                    var grid = app.getMainScreen().getCenterPanel();
                    grid.getStore().reload();
                    if(refreshTree) {
                        grid.currentTreeNode.reload();
                    }
                }).createDelegate(this));
        
        
    },
    
    saveRecord : function() {
        // NOOP
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