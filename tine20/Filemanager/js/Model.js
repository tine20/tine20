/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager.Model');

require('Tinebase/js/widgets/container/GrantsGrid');

/**
 * @namespace   Tine.Filemanager.Model
 * @class       Tine.Filemanager.Model.Node
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Filemanager.Model.NodeMixin = {
    /**
     * virtual nodes are part of the tree but don't exists / are editable
     *
     * NOTE: only "real" virtual node is node with path "otherUsers". all other nodes exist
     *
     * @returns {boolean}
     */
    isVirtual: function() {
        var _ = window.lodash,
            path = this.get('path'),
            parts = _.trim(path, '/').split('/');

        return _.indexOf(['/', '/personal', '/shared'], path) >= 0 || (parts.length == 2 && parts[0] == 'personal');
    },

    getSystemLink: function() {
        return [Tine.Tinebase.common.getUrl().replace(/\/$/, ''), '#',
            Tine.Tinebase.appMgr.get('Filemanager').getRoute(this.get('path'), this.get('type'))].join('/');
    },

    mixinConfig: {
        before: {
            create(o, meta) {
                // NOTE: custom fields of Tree_Nodes are inherited but mc can't show it
                const parentConfig = Tine.Tinebase.Model.Tree_Node.getModelConfiguration();
                _.difference(parentConfig.fieldKeys, meta.modelConfiguration.fieldKeys).forEach((fieldName) => {
                    const idx = parentConfig.fieldKeys.indexOf(fieldName);
                    meta.modelConfiguration.fieldKeys.splice(idx, 0, fieldName);
                    o.splice(idx, 0, {... Tine.Tinebase.Model.Tree_Node.getField(fieldName)});
                    meta.modelConfiguration.fields[fieldName] = {... parentConfig.fields[fieldName]};
                })
                // @TODO: filtermodel?
            }
        }
    },

    statics: {
        type(path) {
            path = String(path);
            const basename = path.split('/').pop(); // do not use basename() here -> recursion!
            return path.lastIndexOf('/') === path.length-1 || basename.lastIndexOf('.') < Math.max(1, basename.length - 5) ? 'folder' : 'file';
        },
        
        dirname(path) {
            const self = Tine.Filemanager.Model.Node;
            const sanitized = self.sanitize(path).replace(/\/$/, '');
            return sanitized.substr(0, sanitized.lastIndexOf('/') + 1);
        },
        
        basename(path, sep='/') {
            const self = Tine.Filemanager.Model.Node;
            const sanitized = self.sanitize(path).replace(/\/$/, '');
            return sanitized.substr(sanitized.lastIndexOf(sep) + 1);
        },
        
        extension(path) {
            const self = Tine.Filemanager.Model.Node;
            return self.type(path) === 'file' ? self.basename(path,'.') : null;
        },

        pathinfo(path) {
            const self = Tine.Filemanager.Model.Node;
            const basename = self.basename(path);
            const extension = self.extension(path);
            return {
                dirname: self.dirname(path),
                basename: basename,
                extension: extension,
                filename: extension ? basename.substring(0, basename.length - extension.length - 1) : null
            }
        },
        
        sanitize(path) {
            path = String(path);
            const self = Tine.Filemanager.Model.Node;
            let isFolder = path.lastIndexOf('/') === path.length -1;
            path = _.compact(path.split('/')).join('/');
            return '/' + path + (isFolder || self.type(path) === 'folder' ? '/' : '');
        },
        
        getExtension: function(filename) {
            const self = Tine.Filemanager.Model.Node;
            return self.extension(filename);
        },

        registerStyleProvider: function(provider) {
            const ns = Tine.Filemanager.Model.Node;
            ns._styleProviders = ns._styleProviders || [];
            ns._styleProviders.push(provider);
        },

        getStyles: function(node) {
            const ns = Tine.Filemanager.Model.Node;
            return _.uniq(_.compact(_.map(ns._styleProviders || [], (styleProvider) => {
                return styleProvider(node);
            })));
        },

        createFromFile: function(file) {
            return new Tine.Filemanager.Model.Node(Tine.Filemanager.Model.Node.getDefaultData({
                name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
                size: file.size || 0,
                contenttype: file.type ? file.type : file.fileType, // missing if safari and chrome 
            }));
        },
        
        getDefaultData: function (defaults) {
            return _.assign({
                type: 'file',
                size: 0,
                creation_time: new Date(),
                created_by: Tine.Tinebase.registry.get('currentAccount'),
                revision: 0,
                revision_size: 0,
                isIndexed: false
            }, defaults);
        }
    }
};

// register grants for nodes
Tine.widgets.container.GrantsManager.register('Filemanager_Model_Node', function(container) {
    // TODO get default grants and remove export
    // var grants = Tine.widgets.container.GrantsManager.defaultGrants();
    //grants.push('download', 'publish');
    var grants = ['read', 'add', 'edit', 'delete', 'sync', 'download', 'publish'];

    return grants;
});

Ext.override(Tine.widgets.container.GrantsGrid, {
    downloadGrantTitle: 'Download', // i18n._('Download')
    downloadGrantDescription: 'The grant to download files', // i18n._('The grant to download files')
    publishGrantTitle: 'Publish', // i18n._('Publish')
    publishGrantDescription: 'The grant to create anonymous download links for files', // i18n._('The grant to create anonymous download links for files')
});

// NOTE: atm the activity records are stored as Tinebase_Model_Tree_Node records
Tine.widgets.grid.RendererManager.register('Tinebase', 'Tree_Node', 'revision', function(revision, metadata, record) {
    revision = parseInt(revision, 10);
    var revisionString = Tine.Tinebase.appMgr.get('Filemanager').i18n._('Revision') + " " + revision,
        availableRevisions = record.get('available_revisions');

    // NOTE we have to encode the path here because it might contain quotes or other bad chars
    if (Ext.isArray(availableRevisions) && availableRevisions.indexOf(String(revision)) >= 0) {
       /* if (revision.is_quarantined == '1') {
            return '<img src="images/icon-set/icon_virus.svg" >' + revisionString; @ToDo needs field revision_quarantine
        }*/
        return '<a href="#"; onclick="Tine.Filemanager.downloadFileByEncodedPath(\'' + btoa(record.get('path')) + '\',' + revision
            + '); return false;">' + revisionString + '</a>';

    }else {
        return revisionString;
    }
});

Tine.Filemanager.nodeBackendMixin = {
    
    /**
     * searches all (lightweight) records matching filter
     *
     * @param   {Object} filter
     * @param   {Object} paging
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Object} root:[records], totalcount: number
     */
    searchRecords: function(filter, paging, options) {
        const cb = options.success;
        options.success = async function (response) {
            const path = _.get(_.find(filter, {field: 'path'}), 'value');
            if (path) {
                const virtualNodes = await Tine.Tinebase.uploadManager.getProcessingNodesByPath(path);
                
                _.each(virtualNodes, (nodeData) => {
                    if (!_.find(_.map(response.records, 'data'), {name: nodeData.name})) {
                        response.records.push(new this.recordClass(nodeData));
                    }
                })
                const sort = options?.params?.sort ?? 'name';
                const dir = paging.dir?.toLowerCase() ?? 'asc';
                response.records = _.orderBy(response.records, [`data.${sort}`], [dir]);
            }
    
            cb.apply(cb.scope, arguments);
        }
        return Tine.Tinebase.data.RecordProxy.prototype.searchRecords.apply(this, arguments);
    },
    
    
    /**
     * creating folder
     * 
     * @param name      folder name
     * @param options   additional options
     * @returns
     */
    createFolder: function(name, options) {
        return new Promise((fulfill, reject) => {
            options = options || {};
            _.wrap(_.escape, function(func, text) {
                return '<p>' + func(text) + '</p>';
            });
            
            options.success = (options.success || Ext.emptyFn).createSequence(fulfill);
            options.failure = (options.failure || Ext.emptyFn).createSequence(reject);
            
            var params = {
                    application : this.appName,
                    filename : name,
                    type : 'folder',
                    method : this.appName + ".createNode"  
            };
            
            options.params = params;
            
            options.beforeSuccess = function(response) {
                const folder = this.recordReader(response);
                this.postMessage('create', folder.data);
                return [folder];
            };
            this.doXHTTPRequest(options);
        });
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
     * copy/move folder/files to a folder
     *
     * @param items files/folders to copy
     *
     * @param target
     * @param move
     * @param showConfirmDialog
     * @param params
     */
    copyNodes : function(items, target, move, showConfirmDialog, params) {
        
        var message = '',
            app = Tine.Tinebase.appMgr.get(this.appName);
        
        if(!params) {
        
            if(!target || !items || items.length < 1) {
                return false;
            }
            
            var sourceFilenames = new Array(),
                destinationFilenames = new Array(),
                withOwnGrants = [],
                forceOverwrite = false,
                treeIsTarget = false,
                targetPath = target;
            
            if(target.data) {
                targetPath = target.data.path;
            }
            else if (target.attributes) {
                targetPath = target.attributes.path;
                treeIsTarget = true;
            }
            else if (target.path) {
                targetPath = target.path;
            }

            for(var i=0; i<items.length; i++) {
                var item = items[i];
                var itemData = item.data;
                if(!itemData) {
                    itemData = item.attributes;
                }
                sourceFilenames.push(itemData.path);
                
                var itemName = itemData.name;
                if(typeof itemName == 'object') {
                    itemName = itemName.name;
                }

                destinationFilenames.push(Tine.Filemanager.Model.Node.sanitize(targetPath + (targetPath.match(/\/$/) ? itemName : '')));

                if (itemData.type === 'folder' && itemData.acl_node === itemData.id) {
                    withOwnGrants.push(itemData);
                }
            }
            
            var method = this.appName + ".copyNodes",
                message = app.i18n._('Copying data .. {0}');
            if(move) {
                method = this.appName + ".moveNodes";
                message = app.i18n._('Moving data .. {0}');
            }
            
            params = {
                    application: this.appName,
                    sourceFilenames: sourceFilenames,
                    destinationFilenames: destinationFilenames,
                    forceOverwrite: forceOverwrite,
                    method: method
            };
            
            if (move && withOwnGrants.length && showConfirmDialog) {
                Ext.MessageBox.show({
                    icon: Ext.MessageBox.WARNING,
                    buttons: Ext.MessageBox.OKCANCEL,
                    title: app.i18n._('Confirm Changing of Folder Grants'),
                    msg: app.i18n._("You are about to move a folder which has own grants. These grants will be lost and the folder will inherit its grants from its new parent folder."),
                    fn: function(btn) {
                        if (btn === 'ok') {
                            Tine.Filemanager.nodeBackend.copyNodes(items, target, move, false, params);
                        }
                    }
                });
                return false;
            }
        } else {
            message = app.i18n._('Copying data .. {0}');
            if(params.method == this.appName + '.moveNodes') {
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

                // send updates
                var _ = window.lodash,
                    me = this,
                    recordsData = Ext.util.JSON.decode(result.responseText);

                _.each(recordsData, function(recordData) {
                    me.postMessage('update', recordData);
                });


                // var nodeData = Ext.util.JSON.decode(result.responseText),
                //     treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel(),
                //     grid = app.getMainScreen().getCenterPanel();
                //
                // // Tree refresh
                // if(treeIsTarget) {
                //
                //     for(var i=0; i<items.length; i++) {
                //
                //         var nodeToCopy = items[i];
                //
                //         if(nodeToCopy.data && nodeToCopy.data.type !== 'folder') {
                //             continue;
                //         }
                //
                //         if(move) {
                //             var copiedNode = treePanel.cloneTreeNode(nodeToCopy, target),
                //                 nodeToCopyId = nodeToCopy.id,
                //                 removeNode = treePanel.getNodeById(nodeToCopyId);
                //
                //             if(removeNode && removeNode.parentNode) {
                //                 removeNode.parentNode.removeChild(removeNode);
                //             }
                //
                //             target.appendChild(copiedNode);
                //             copiedNode.setId(nodeData[i].id);
                //         }
                //         else {
                //             var copiedNode = treePanel.cloneTreeNode(nodeToCopy, target);
                //             target.appendChild(copiedNode);
                //             copiedNode.setId(nodeData[i].id);
                //
                //         }
                //     }
                // }
                //
                // // Grid refresh
                // grid.getStore().reload();
            },
            failure: function(response, request) {
                var nodeData = Ext.util.JSON.decode(response.responseText),
                    request = Ext.util.JSON.decode(request.jsonData);
                
                this.loadMask.hide();
                app.getMainScreen().getWestPanel().setDisabled(false);
                app.getMainScreen().getNorthPanel().setDisabled(false);
                
                Tine.Filemanager.nodeBackend.handleRequestException(nodeData.data, request);
            }
        });
    },
    
    /**
     * upload files
     * 
     * @param {} params Request parameters
     * @param [] uploadKeyArray
     * @param Boolean addToGridStore
     */
    createNodes: function (params, uploadKeyArray, addToGridStore) {
        var app = Tine.Tinebase.appMgr.get(this.appName),
            grid = app.getMainScreen().getCenterPanel(),
            me = this,
            gridStore = grid.store;

        params.application = this.appName;
        params.method = this.appName + '.createNodes';
        params.uploadKeyArray = uploadKeyArray;
        params.addToGridStore = addToGridStore;

        var onSuccess = (function (response, request) {

            var nodeData = Ext.util.JSON.decode(response.responseText);

            for (var i = 0; i < this.uploadKeyArray.length; i++) {
                var fileRecord = Tine.Tinebase.uploadManager.upload(this.uploadKeyArray[i]);
                var nodeRecord = Tine.Tinebase.data.Record.setFromJson(nodeData[i], Tine.Filemanager.Model.Node);
                
                if (addToGridStore) {

                    var existingRecordIdx = gridStore.find('name', fileRecord.get('name'));
                    if (existingRecordIdx > -1) {
                        gridStore.removeAt(existingRecordIdx);
                        gridStore.insert(existingRecordIdx, nodeRecord);
                    } else {
                        gridStore.add(nodeRecord);
                    }
                    
                }
                
                fileRecord = Tine.Filemanager.nodeBackend.updateNodeRecord(nodeData[i], fileRecord);
                nodeRecord.fileRecord = fileRecord;
            }

        }).createDelegate({uploadKeyArray: uploadKeyArray, addToGridStore: addToGridStore});

        var onFailure = (function (response, request) {

            var nodeData = Ext.util.JSON.decode(response.responseText),
                request = Ext.util.JSON.decode(request.jsonData);

            nodeData.data.uploadKeyArray = this.uploadKeyArray;
            nodeData.data.addToGridStore = this.addToGridStore;
            Tine.Filemanager.nodeBackend.handleRequestException(nodeData.data, request);

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
        var _ = window.lodash,
            appNS = _.get(Tine, this.appName);
        appNS.handleRequestException(exception, request);
    },
    
    /**
     * updates given record with nodeData from from response
     */
    updateNodeRecord : function(nodeData, nodeRecord) {
        
        for(var field in nodeData) {
            nodeRecord.set(field, nodeData[field]);
        }
        
        return nodeRecord;
    },
    
    statics: {

    }
};

/**
 * @namespace   Tine.Filemanager.Model
 * @class       Tine.Filemanager.Model.DownloadLink
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 */
Tine.Filemanager.Model.DownloadLink = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.modlogFields.concat([
    { name: 'id' },
    { name: 'node_id' },
    { name: 'url' },
    { name: 'expiry_time', type: 'datetime' },
    { name: 'access_count' },
    { name: 'password' }
]), {
    appName: 'Filemanager',
    modelName: 'DownloadLink',
    idProperty: 'id',
    titleProperty: 'url',
    // ngettext('Download Link', 'Download Links', n); gettext('Download Link');
    recordName: 'Download Link',
    recordsName: 'Download Links'
});

/**
 * download link backend
 */
Tine.Filemanager.downloadLinkRecordBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Filemanager',
    modelName: 'DownloadLink',
    recordClass: Tine.Filemanager.Model.DownloadLink
});
