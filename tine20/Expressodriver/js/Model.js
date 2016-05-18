/*
 * Tine 2.0
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 */
Ext.ns('Tine.Expressodriver.Model');

/**
 * @namespace   Tine.Expressodriver.Model
 * @class       Tine.Expressodriver.Model.Node
 * @extends     Tine.Tinebase.data.Record
 * Node record definition
 *
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 */
Tine.Expressodriver.Model.Node = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.modlogFields.concat([
    { name: 'id' },
    { name: 'name' },
    { name: 'path' },
    { name: 'size' },
    { name: 'revision' },
    { name: 'type' },
    { name: 'contenttype' },
    { name: 'description' },
    { name: 'account_grants' },
    { name: 'description' },
    { name: 'object_id'},

    { name: 'relations' },
    { name: 'customfields' },
    { name: 'notes' },
    { name: 'tags' }
]), {
    appName: 'Expressodriver',
    modelName: 'Node',
    idProperty: 'id',
    titleProperty: 'name',
    recordName: 'File',
    recordsName: 'Files',
    containerName: 'Folder',
    containersName: 'Folders',

    /**
     * checks whether creating folders is allowed
     */
    isCreateFolderAllowed: function() {
        var grants = this.get('account_grants');

        if(!grants) {
            return false;
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

        return true;
    }
});

/**
 * create Node from File
 *
 * @param {File} file
 */
Tine.Expressodriver.Model.Node.createFromFile = function(file) {
    return new Tine.Expressodriver.Model.Node({
        name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
        size: file.size || 0,
        type: 'file',
        contenttype: file.type ? file.type : file.fileType, // missing if safari and chrome
        revision: 0
    });
};

/**
 * default Expressodriver backend
 */
Tine.Expressodriver.fileRecordBackend =  new Tine.Tinebase.data.RecordProxy({
    appName: 'Expressodriver',
    modelName: 'Node',
    recordClass: Tine.Expressodriver.Model.Node,

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
            var app = Tine.Tinebase.appMgr.get(Tine.Expressodriver.fileRecordBackend.appName);
            var grid = app.getMainScreen().getCenterPanel();
            var nodeData = Ext.util.JSON.decode(result);
            var newNode = app.getMainScreen().getWestPanel().getContainerTreePanel().createTreeNode(nodeData, parentNode);

            var parentNode = grid.currentFolderNode;
            if(parentNode) {
                parentNode.appendChild(newNode);
            }
            Ext.MessageBox.hide();
            grid.getStore().reload();
        };

        return this.doXHTTPRequest(options);
    },

    /**
     * is automatically called in generic GridPanel
     */
    saveRecord : function(record, request) {
        if(record.hasOwnProperty('fileRecord')) {
            return;
        } else {
            Tine.Tinebase.data.RecordProxy.prototype.saveRecord.call(this, record, request);
        }
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
            var app = Tine.Tinebase.appMgr.get(Tine.Expressodriver.fileRecordBackend.appName),
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
            grid.pagingToolbar.refresh.enable();
            Ext.MessageBox.hide();

        }).createDelegate({items: items});
        var app = Tine.Tinebase.appMgr.get(this.appName);
        Ext.MessageBox.wait(i18n._('Please wait'), app.i18n._('Deleting nodes...' ));
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
            app = Tine.Tinebase.appMgr.get(Tine.Expressodriver.fileRecordBackend.appName);


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

            var method = "Expressodriver.copyNodes",
                message = app.i18n._('Copying data .. {0}');
            if(move) {
                method = "Expressodriver.moveNodes";
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
            if(params.method == 'Expressodriver.moveNodes') {
                message = app.i18n._('Moving data .. {0}');
            }
        }

        this.loadMask = new Ext.LoadMask(app.getMainScreen().getCenterPanel().getEl(), {msg: String.format(i18n._('Please wait')) + '. ' + String.format(message, '' )});
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

                Tine.Expressodriver.fileRecordBackend.handleRequestException(nodeData.data, request);
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
        var app = Tine.Tinebase.appMgr.get('Expressodriver'),
            grid = app.getMainScreen().getCenterPanel(),
            gridStore = grid.getStore();

        params.application = 'Expressodriver';
        params.method = 'Expressodriver.createNode';
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

                fileRecord = Tine.Expressodriver.fileRecordBackend.updateNodeRecord(nodeData[i], fileRecord);
                var nodeRecord = new Tine.Expressodriver.Model.Node(nodeData[i]);

                nodeRecord.fileRecord = fileRecord;
                gridStore.add(nodeRecord);

            }
        }).createDelegate({uploadKey: uploadKey, addToGridStore: addToGridStore});

        var onFailure = (function(response, request) {

            var nodeData = Ext.util.JSON.decode(response.responseText),
                request = Ext.util.JSON.decode(request.jsonData);

            nodeData.data.uploadKey = this.uploadKey;
            nodeData.data.addToGridStore = this.addToGridStore;
            Tine.Expressodriver.fileRecordBackend.handleRequestException(nodeData.data, request);

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
        var app = Tine.Tinebase.appMgr.get('Expressodriver'),
            grid = app.getMainScreen().getCenterPanel(),
            gridStore = grid.store;

        params.application = 'Expressodriver';
        params.method = 'Expressodriver.createNodes';
        params.uploadKeyArray = uploadKeyArray;
        params.addToGridStore = addToGridStore;


        var onSuccess = (function(response, request){

            var nodeData = Ext.util.JSON.decode(response.responseText);

            for(var i=0; i<this.uploadKeyArray.length; i++) {
                var fileRecord = Tine.Tinebase.uploadManager.upload(this.uploadKeyArray[i]);

                if(addToGridStore) {
                    fileRecord = Tine.Expressodriver.fileRecordBackend.updateNodeRecord(nodeData[i], fileRecord);
                    var nodeRecord = new Tine.Expressodriver.Model.Node(nodeData[i]);

                    nodeRecord.fileRecord = fileRecord;

                    var existingRecordIdx = gridStore.find('name', fileRecord.get('name'));
                    if(existingRecordIdx > -1) {
                        gridStore.removeAt(existingRecordIdx);
                        gridStore.insert(existingRecordIdx, nodeRecord);
                    } else {
                        gridStore.add(nodeRecord);
                    }
                }
            }

        }).createDelegate({uploadKeyArray: uploadKeyArray, addToGridStore: addToGridStore});

        var onFailure = (function(response, request) {

            var nodeData = Ext.util.JSON.decode(response.responseText),
                request = Ext.util.JSON.decode(request.jsonData);

            nodeData.data.uploadKeyArray = this.uploadKeyArray;
            nodeData.data.addToGridStore = this.addToGridStore;
            Tine.Expressodriver.fileRecordBackend.handleRequestException(nodeData.data, request);

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
     * exception handler for this proxy
     *
     * @param {Tine.Exception} exception
     */
    handleRequestException: function(exception, request) {
        Tine.Expressodriver.handleRequestException(exception, request);
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
 * @namespace Tine.Expressodriver.Model
 * @static
 * @return {Object} filterModel definition
 */
Tine.Expressodriver.Model.Node.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Expressodriver');

    return [
        {label : i18n._('Quick Search'), field : 'query', operators : [ 'contains' ]},
        {filtertype : 'tine.expressodriver.pathfiltermodel', app : app}
    ];
};

/**
 *  create model from settinf of Expressodriver
 */
Tine.Expressodriver.Model.Settings = Tine.Tinebase.data.Record.create([
        {name: 'id'},
        {name: 'default'},
        {name: 'adapters'}
    ], {
    appName: 'Expressodriver',
    modelName: 'Settings',
    idProperty: 'id',
    titleProperty: 'title',
    recordName: 'Settings',
    recordsName: 'Settingss',
    containerName: 'Settings',
    containersName: 'Settings',
    getTitle: function() {
        return this.recordName;
    }
});

/**
 * set settings backend
 */
Tine.Expressodriver.settingsBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Expressodriver',
    modelName: 'Settings',
    recordClass: Tine.Expressodriver.Model.Settings
});

/**
 * get randon id
 * @param store
 * @returns {Integer|Number}
 */
Tine.Expressodriver.Model.getRandomUnusedId = function(store) {
    var result;
    do {
        result = Tine.Tinebase.common.getRandomNumber(0, 21474836);
    } while (store.getById(result) != undefined)

    return result;
};