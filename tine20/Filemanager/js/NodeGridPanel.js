/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

require('./nodeActions');
require('./nodeContextMenu');

import upload from './upload';

const { retryAllRejectedPromises } = require('promises-to-retry');
/**
 * File grid panel
 *
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.NodeGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 *
 * <p>Node Grid Panel</p>
 * <p><pre>
 * </pre></p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Filemanager.FileGridPanel
 */
Tine.Filemanager.NodeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * @cfg filesProperty
     * @type String
     */
    filesProperty: 'files',

    /**
     * config values
     * @private
     */
    header: false,
    border: false,
    deferredRender: false,
    autoExpandColumn: 'name',
    showProgress: true,
    enableDD: true,

    recordClass: 'Filemanager.Node',
    listenMessageBus: true,
    hasDetailsPanel: false,
    evalGrants: true,
    // initialLoadAfterRender: false,

    dataSafeEnabled: false,

    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        Ext.applyIf(this.defaultSortInfo, {field: 'name', direction: 'DESC'});
        Ext.applyIf(this.defaultPaging, { start: 0, limit: 500 });
        Ext.applyIf(this.gridConfig, {
            autoExpandColumn: 'name',
            enableFileDialog: false,
            enableDragDrop: true,
            ddGroup: 'fileDDGroup',
            gridType: Ext.grid.EditorGridPanel,
            clicksToEdit: 'auto',
            listeners: {
                scope: this,
                afterrender: this.initDragDrop
            }
        });

        if (this.readOnly || ! this.enableDD) {
            this.gridConfig.enableDragDrop = false;
        }

        this.dataSafeEnabled = !!Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName).length;

        this.recordProxy = this.recordProxy || Tine.Filemanager.nodeBackend;

        this.initCustomCols();
        this.modelConfig = this.recordClass.getModelConfiguration();
        _.assign(this.gridConfig, this.initGenericColumnModel());

        const routeParts = Tine.Tinebase.router.getRoute();
        let defaultPath = Tine.Tinebase.container.getMyFileNodePath();
        if ('Filemanager' === routeParts.shift()) {
            const path = Ext.ux.util.urlCoder.decodeURIComponent(this.recordClass.sanitize(routeParts.join('/')));
            const isFile = this.recordClass.type(path) === 'file';
            defaultPath = isFile ? this.recordClass.dirname(path) : path;
        }

        this.defaultFilters = this.defaultFilters || [
            {field: 'path', operator: 'equals', value: defaultPath}
        ];

        this.plugins = this.plugins || [];

        this.filterToolbar = this.filterToolbar || this.getFilterToolbar();

        this.plugins.push(this.filterToolbar);

        if (!this.readOnly) {
            this.plugins.push({
                ptype: 'ux.browseplugin',
                multiple: true,
                scope: this,
                enableFileDialog: false,
                handler: this.onFilesSelect.createDelegate(this)
            });
        }

        if (this.hasQuickSearchFilterToolbarPlugin) {
            this.filterToolbar.getQuickFilterPlugin().criteriaIgnores.push({field: 'path'});
        }

        
        Tine.Filemanager.NodeGridPanel.superclass.initComponent.call(this);
        this.getStore().on('load', this.onLoad.createDelegate(this));
        this.getGrid().on('beforeedit', this.onBeforeEdit, this);

        // // cope with empty selections - dosn't work. It's confusing if e.g. the delte btn is enabled with no selections
        // this.selectionModel.on('selectionchange', function(sm) {
        //     if (sm.getSelections().length) {
        //         return;
        //     }
        //
        //     var _ = window.lodash,
        //         recordData = _.get(this, 'currentFolderNode.attributes'),
        //         record = recordData ? Tine.Tinebase.data.Record.setFromJson(JSON.stringify(recordData), this.recordClass) : null;
        //
        //     if (record) {
        //         this.actionUpdater.updateActions(record);
        //     }
        // }, this);
    },

    /**
     * get record by data path
     * @param data
     * @returns {string}
     */
    getRecordByData(data) {
        const store = this.getStore();

        return _.find(store.data.items, (node) => {return node.get('path') === data?.path;})
            || _.find(store.data.items, (node) => {return node?.id === data?.id;})
            || _.find(store.data.items, (node) => {return node.get('name') === data?.name;});
    },

    /**
     * check if node path is in current grid panel
     * @param path
     * @returns {boolean}
     */
    isInCurrentGrid(path) {
        const CurrentNodePath = _.get(_.get(this.getFilteredContainers(), '0'), 'path');
        return `${this.getParentPath(path)}/` === CurrentNodePath;
    },

    /**
     * bus notified about record changes
     */
    onRecordChanges: function(data, e) {
        const existingRecord = this.getRecordByData(data);

        if (!existingRecord && e.topic.match(/\.create/)) {
            this.onUpdateGridPanel(data);
        } else if (e.topic.match(/\.update/)) {
            this.onUpdateGridPanel(data);
        } else if (existingRecord && e.topic.match(/\.delete/)) {
            this.store.remove(existingRecord);
        } else {
            if (this.isInCurrentGrid(_.get(data, 'path'))) {
                this.bufferedLoadGridData({
                    removeStrategy: 'keepBuffered'
                });
            }
        }
        // NOTE: grid doesn't update selections itself
        this.actionUpdater.updateActions(this.grid.getSelectionModel(), this.getFilteredContainers());
    },

    /**
     * on update after edit
     *
     * @param {String|Tine.Tinebase.data.Record} record
     * @param {String} mode
     */
    onUpdateGridPanel: function (record, mode) {
        if (!this.rendered) {
            return;
        }
    
        if (record.status === 'failed' || record.status === 'cancelled') {
            this.bufferedLoadGridData({
                removeStrategy: 'keepBuffered'
            });
            
            return;
        }

        const existingRecord = this.getRecordByData(record.data || record);
        record = this.createRecord(JSON.stringify(record), mode);

        Tine.log.debug('Tine.Filemanager.NodeGridPanel::onUpdateRecord() -> record:');
        Tine.log.debug(record, mode);

        if (record && Ext.isFunction(record.copy)) {
            const store = this.getStore();
            let isSelected = false;

            if (existingRecord) {
                const idx = store.indexOf(existingRecord);
                isSelected = this.getGrid().getSelectionModel().isSelected(idx);
                store.removeAt(idx);
                store.insert(idx, [record]);
            } else if (this.isInCurrentGrid(record.get('path'))) {
                store.add([record]);
            } else {
                return;
            }

            // sort new/edited record
            store.remoteSort = false;
            store.sort(
                _.get(store, 'sortInfo.field', this.recordClass.getMeta('titleField')),
                _.get(store, 'sortInfo.direction', 'ASC')
            );
            store.remoteSort = this.storeRemoteSort;

            if (isSelected) {
                this.getGrid().getSelectionModel().selectRow(store.indexOfId(record.id), true);
            }
        }
    },
   
    /**
     * check if record can be edited
     *
     * @param row
     * @returns {boolean}
     */
    onBeforeEdit: function(row) {
        return Tine.Filemanager.nodeActionsMgr.checkConstraints('edit', row.record);
    },

    /**
     * after grid renderd
     */
    initDragDrop: function () {
        if (!this.enableDD) {
            return;
        }

        var grid = this.grid,
            view = grid.getView();

        view.dragZone.onBeforeDrag = this.onBeforeDrag.createDelegate(this);

        this.dropZone = new Ext.dd.DropZone(this.getEl(), {
            ddGroup: 'fileDDGroup',
            onNodeOver: this.onNodeOver.createDelegate(this),
            onNodeDrop: this.onNodeDrop.createDelegate(this),
            onContainerOver: this.onContainerOver.createDelegate(this),
            onContainerDrop: this.onContainerDrop.createDelegate(this),
            getTargetFromEvent: function(e) {
                var idx = view.findRowIndex(e.target),
                    record = grid.getStore().getAt(idx);

                return record;
            }
        });
    },

    /**
     * An empty function by default, but provided so that you can perform a custom action before the initial
     * drag event begins and optionally cancel it.
     * @param {Object} data An object containing arbitrary data to be shared with drop targets
     * @param {Event} e The event object
     * @return {Boolean} isValid True if the drag event is valid, else false to cancel
     */
    onBeforeDrag: function(data, e) {
        var _ = window.lodash,
            // @TODO: rethink: do I need delte on the record or parent?
            requiredGrant = e.ctrlKey || e.altKey ? 'readGrant' : 'editGrant';

        data.nodes = this.selectionModel.getSelections();
        
        return !this.selectionModel.isFilterSelect &&
            _.reduce(this.selectionModel.getSelections(), function(allowed, record) {
                return allowed && !! _.get(record, 'data.account_grants.' + requiredGrant);
            }, true);
    },

    /*
     * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from {@link #getTargetFromEvent} for this node)
     * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
     * @param {Event} e The event
     * @param {Object} data An object containing arbitrary data supplied by the drag source
     * @return {String} status The CSS class that communicates the drop status back to the source so that the underlying {@link Ext.dd.StatusProxy} can be updated
     */
    onNodeOver: function(record, source, e, data) {
        const action = e.ctrlKey || e.altKey ? 'copy' : 'move';
        const targetNode = record;
        const sourceNodes = data.nodes;
        
        const dropAllowed =
            targetNode.get('type') == 'folder'
            && Tine.Filemanager.nodeActionsMgr.checkConstraints(action, targetNode, sourceNodes);
            
        return dropAllowed ?
            'tinebase-dd-drop-ok-' + action :
            Ext.dd.DropZone.prototype.dropNotAllowed;
    },
    
    onContainerOver: function(dd, e, data) {
        const filteredContainers = this.getFilteredContainers();
        const record = Tine.Tinebase.data.Record.setFromJson(_.get(this.getFilteredContainers(),'0'), this.recordClass);
        const dropAllowed = filteredContainers.length === 1
            && _.reduce(data.nodes, (allowed, node) => {return allowed && !this.store.getById(node.id)}, true);
            
        return dropAllowed ? this.onNodeOver(record, dd.source, e, data) :
            Ext.dd.DropZone.prototype.dropNotAllowed;
    },
    
    /**
     * Called when the DropZone determines that a {@link Ext.dd.DragSource} has been dropped onto
     * the drop node.  The default implementation returns false, so it should be overridden to provide the
     * appropriate processing of the drop event and return true so that the drag source's repair action does not run.
     * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from
     * {@link #getTargetFromEvent} for this node)
     * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
     * @param {Event} e The event
     * @param {Object} data An object containing arbitrary data supplied by the drag source
     * @return {Boolean} True if the drop was valid, else false
     */
    onNodeDrop: function(target, dd, e, data) {
        if (Ext.fly(dd.getDragEl()).hasClass('x-dd-drop-nodrop')) {
            return false;
        }
        
        const success = Tine.Filemanager.nodeBackend.copyNodes(data.nodes, target, !(e.ctrlKey || e.altKey)) !== false;
        if(success) {
            this.grid.getStore().remove(data.nodes);
        }
        return success;
    },

    onContainerDrop: function(dd, e, data) {
        const filteredContainers = this.getFilteredContainers();
        const target = Tine.Tinebase.data.Record.setFromJson(_.get(this.getFilteredContainers(),'0'), this.recordClass);

        return filteredContainers.length === 1 ? this.onNodeDrop(target, dd, e, data) : false;
    },

    initCustomCols: function() {
        this.customColumnData = [{
            id: 'tags',
            width: 30
        }, {
            id: 'name',
            width: 100,
            renderer: Ext.ux.PercentRendererWithName,
            editor: Tine.widgets.form.FieldManager.get(this.app, this.recordClass, 'name', Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID, {
                listeners: {
                    show: (field) => {
                        const record = this.selectionModel.getSelected();
                        const value = String(field.getValue());
                        const match = value.match(/\..*/);
                        const end = match && record.get('type') === 'file' ? match.index : value.length;
                        field.selectText(0, end);
                    }
                }
            })
        },{
            id: 'hash',
            width: 40,
        },{
            id: 'size',
            width: 30,
            renderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3)
        },{
            id: 'contenttype',
            width: 50,
            renderer: function(value, metadata, record) {

                var app = Tine.Tinebase.appMgr.get('Filemanager');
                if(record.data.type === 'folder') {
                    return app.i18n._("Folder");
                }
                else {
                    return value;
                }
            }
        }, {
            id: 'creation_time',
            width: 40,
            hidden: false
        }, {
            id: 'created_by',
            width: 50,
            hidden: false
        }, {
            id: 'last_modified_time',
            width: 40,
            hidden: false
        }, {
            id: 'last_modified_by',
            width: 50,
            hidden: false
        }, {
            id: 'revision_size',
            tooltip: this.app.i18n._("Total size of all available revisions"),
            width: 40,
            renderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3)
        }, {
            id: 'isIndexed',
            tooltip: this.app.i18n._("File contents is part of the search index"),
            width: 40,
            renderer: function(value, i, node) {
                return node.get('type') == 'file' ? Tine.Tinebase.common.booleanRenderer(value) : '';
            }
        }];

        this.hideColumns = _.isArray(this.hideColumns) ? this.hideColumns : [];
        if (! Tine.Tinebase.configManager.get('filesystem.modLogActive', 'Tinebase')) {
            this.hideColumns.push('revision_size');
        }

        if (! Tine.Tinebase.configManager.get('filesystem.index_content', 'Tinebase')) {
            this.hideColumns.push('isIndexed');
        }
    },

    /**
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    },

    /**
     * Capture space to toggle document preview
     */
    onKeyDown: function(e) {
        Tine.Filemanager.NodeGridPanel.superclass.onKeyDown.apply(this, arguments);

        const selections = this.selectionModel.getSelections();
        
        // Open preview on space if a node is selected and the node type equals file
        if (e.getKey() == e.SPACE && !(e.getTarget('form') || e.getTarget('input') || e.getTarget('textarea'))) {
            this.action_preview.execute();
            e.stopEvent();
        }
        
        if ((e.getKey() == e.RIGHT || e.getKey() == e.ENTER) && selections.length === 1 && selections[0].get('type') === 'folder') {
            this.expandFolder(selections[0]);
            e.stopEvent();
        }
    },

    /**
     * init ext grid panel
     * @private
     */
    initGrid: function() {
        Tine.Filemanager.NodeGridPanel.superclass.initGrid.call(this);

        if (this.usePagingToolbar) {
           this.initPagingToolbar();
        }
    },

    /**
     * inserts a quota Message when using old Browsers with html4upload
     */
    initPagingToolbar: function() {
        if(!this.pagingToolbar || !this.pagingToolbar.rendered) {
            this.initPagingToolbar.defer(50, this);
            return;
        }

        this.quotaBar = new Ext.Component({
            html: '&nbsp;',
            style: {
                marginTop: '3px',
                width: '100px',
                height: '16px'
            }
        });
        this.pagingToolbar.insert(12, new Ext.Toolbar.Separator());
        this.pagingToolbar.insert(12, this.quotaBar);
        this.pagingToolbar.doLayout();
    },

    /**
     * returns filter toolbar -> supress OR filters
     * @private
     */
    getFilterToolbar: function(config) {
        config = config || {};
        var plugins = [];

        if (this.hasQuickSearchFilterToolbarPlugin) {
            this.quickSearchFilterToolbarPlugin = new Tine.widgets.grid.FilterToolbarQuickFilterPlugin();
            plugins.push(this.quickSearchFilterToolbarPlugin);
        }

        return new Tine.widgets.grid.FilterToolbar(Ext.apply(config, {
            app: this.app,
            recordClass: this.recordClass,
            filterModels: this.recordClass.getFilterModel().concat(this.getCustomfieldFilters()),
            defaultFilter: 'query',
            filters: this.defaultFilters || [],
            plugins: plugins
        }));
    },

    /**
     * returns add action / test
     * 
     * - handle both file and folder upload action
     *
     * @return {Object} add action config
     */
    getAddAction: function (allowFolder) {
        return {
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: allowFolder ? this.app.i18n._('Upload Folder') : this.app.i18n._('Upload File'),
            handler: this.onFilesSelect,
            disabled: true,
            scope: this,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                enableFileDrop: false,
                disable: true,
                allowFolder: allowFolder
            }],
            iconCls: 'action_add',
            actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
                let allowAdd = _.get(filteredContainers, '[0].account_grants.addGrant', false);
                let isVirtual = false;
                let constraints = false;

                try {
                    const filteredContainer = Tine.Tinebase.data.Record.setFromJson(filteredContainers[0], Tine.Filemanager.Model.Node);
                    isVirtual = filteredContainer.isVirtual();

                    constraints = Tine.Filemanager.nodeActionsMgr.checkConstraints('create', filteredContainer, [{type: 'file'}]);

                } catch(e) {}

                action.setDisabled(!allowAdd || isVirtual ||!constraints);
            }.createDelegate(this)
        };
    },

    initLayout: function() {
        Tine.Filemanager.NodeGridPanel.superclass.initLayout.call(this);

        var northPanel = lodash.find(this.items, function (i) {
            return i.region == 'north'
        });

        northPanel.tbar = new Tine.Filemanager.RecursiveFilter({
            gridPanel: this,
            hidden: true
        });
    },

    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * @private
     */
    initActions: function() {
        // generic node actions - work on selections grid/tree nodes
        this.action_createFolder = Tine.Filemanager.nodeActionsMgr.get('createFolder');
        this.action_editFile = Tine.Filemanager.nodeActionsMgr.get('edit');
        this.action_deleteRecord = Tine.Filemanager.nodeActionsMgr.get('delete');
        this.action_download = Tine.Filemanager.nodeActionsMgr.get('download');
        this.action_moveRecord = Tine.Filemanager.nodeActionsMgr.get('move');
        this.action_publish = Tine.Filemanager.nodeActionsMgr.get('publish');
        this.action_systemLink = Tine.Filemanager.nodeActionsMgr.get('systemLink');
        this.action_preview = Tine.Filemanager.nodeActionsMgr.get('preview', {
            initialApp: this.app,
            sm: this.grid.getSelectionModel()
        });

        if (this.dataSafeEnabled) {
            this.action_dataSafe = new Ext.Action({
                text: 'Open Data Safe', // _('Open Data Safe')
                iconCls: 'action_filemanager_data_safe_locked',
                scope: this,
                handler: this.onDataSafeToggle,
                enableToggle: true
            });

            this.postalSubscriptions = [];
            _.each(Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName), (areaLock) => {
                this.postalSubscriptions.push(postal.subscribe({
                    channel: "areaLocks",
                    topic: areaLock + '.*',
                    callback: this.applyDataSafeState.createDelegate(this)
                }));
            });

            this.afterIsRendered().then(() => {this.applyDataSafeState()})  ;
        }

        // grid only actions - work on node which is displayed (this.currentFolderNode)
        // @TODO: fixme - ux problems with filterselect / initialData
        this.action_file_upload = new Ext.Action(this.getAddAction(false));
        this.action_folder_upload = new Ext.Action(this.getAddAction(true));
        this.action_goUpFolder = new Ext.Action({
            allowMultiple: true,
            actionType: 'goUpFolder',
            text: this.app.i18n._('Folder Up'),
            handler: this.onLoadParentFolder,
            iconCls: 'action_filemanager_folder_up',
            disabled: true,
            scope: this,
            actionUpdater: function(action) {
                var _ = window.lodash,
                    path = _.get(this.getFilteredContainers(),'0.path');

                action.setDisabled(path == '/');
            }.createDelegate(this)
        });

        var contextActions = [
            this.action_deleteRecord,
            'rename',
            this.action_moveRecord,
            this.action_download,
            'resume',
            'pause',
            this.action_editFile,
            this.action_publish,
            this.action_systemLink,
            this.action_preview
        ];

        this.contextMenu = Tine.Filemanager.nodeContextMenu.getMenu({
            nodeName: Tine.Filemanager.Model.Node.getRecordName(),
            actions: contextActions,
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        }, [{
            ptype: 'ux.itemregistry',
            key: 'Filemanager-Node-GridPanel-ContextMenu'
        }]);

        this.folderContextMenu = Tine.Filemanager.nodeContextMenu.getMenu({
            nodeName: Tine.Filemanager.Model.Node.getContainerName(),
            actions: [this.action_deleteRecord, 'rename', this.action_moveRecord, this.action_editFile, this.action_publish, this.action_systemLink],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        });

        this.actionUpdater.addActions(this.contextMenu.items);
        this.actionUpdater.addActions(this.folderContextMenu.items);

        var actions = [
            this.action_file_upload,
            this.action_folder_upload,
            this.action_createFolder,
            this.action_goUpFolder,
            this.action_download,
            this.action_deleteRecord,
            this.action_editFile,
            this.action_publish,
            this.action_systemLink,
            this.action_preview
        ];

        this.actionUpdater.addActions(actions);
    },

    onDestroy: function() {
        _.each(this.postalSubscriptions, (subscription) => {subscription.unsubscribe()});
        return this.supr().onDestroy.call(this);
    },
    
    /**
     * fm specific delete handler
     */
    onDeleteRecords: function(btn, e) {
        this.action_deleteRecord.execute();
    },

    /**
     * go up one folder
     *
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
     */
    onLoadParentFolder: function(button, event) {
        let currentFolderNode = _.get(this.getFilteredContainers(),'0');
        this.expandFolder(this.getParentPath(_.get(currentFolderNode, 'path')));
    },

    onDataSafeToggle: function(button, e) {
        button.toggle(!button.pressed);

        const areaLocks = Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName);
        const promises = _.map(areaLocks, (areaLock) => {
            return !button.pressed ?
                Tine.Tinebase.areaLocks.unlock(areaLock) :
                Tine.Tinebase.areaLocks.lock(areaLock);
        });

        this.getEl().mask(button.pressed ? this.app.i18n._('Locking data safe...') : this.app.i18n._('Unlocking data safe...'));
        Promise.allSettled(promises).finally(() => {
            this.getEl().unmask();
        })
    },

    applyDataSafeState: function() {
        var me = this;

        const isLocked = !! Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName, true).length;
        // if state change -> reload
        if (me.action_dataSafe.items.length && isLocked == me.action_dataSafe.items[0].pressed) {
            _.defer(() => {
                me.loadGridData({
                    preserveCursor:     false,
                    preserveSelection:  false,
                    preserveScroller:   false
                }
            )});
        }

        var cls = isLocked ? 'removeClass' : 'addClass';
        me.action_dataSafe.each(function(btn) {btn[cls]('x-type-data-safe')});
        me.action_dataSafe.each(function(btn) {btn.toggle(!isLocked)});
        me.action_dataSafe.setText(isLocked ? me.app.i18n._('Open Data Safe') : me.app.i18n._('Close Data Safe'));
        me.action_dataSafe.setIconClass(isLocked ? 'action_filemanager_data_safe_locked' : 'action_filemanager_data_safe_unlocked')
    },

    /**
     * returns view row class
     */
    getViewRowClass: function(record, index, rowParams, store) {
        let className = Tine.Filemanager.NodeGridPanel.superclass.getViewRowClass.apply(this, arguments);

        if (this.dataSafeEnabled && !!record.get('pin_protected_node')) {
            className += ' x-type-data-safe'
        }
        
        const updatedRecord = _.get(arguments[0], 'json') ?? _.get(arguments[0], 'data');
        if (_.get(updatedRecord,'status') === 'pending') {
            className += ' x-type-data-pending'
        }

        return className;
    },
    
    /**
     * get the right contextMenu
     */
    getContextMenu: function(grid, row, e) {
        var r = this.store.getAt(row),
            type = r ? r.get('type') : null;

        return type === 'folder' ? this.folderContextMenu : this.contextMenu;
    },

    /**
     * get action toolbar
     *
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            var items = [
                this.splitAddButton ?
                    Ext.apply(new Ext.SplitButton(this.action_file_upload), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top',
                        arrowAlign:'right',
                        menu: new Ext.menu.Menu({
                            items: [
                                this.action_folder_upload
                            ],
                            plugins: [{
                                ptype: 'ux.itemregistry',
                                key:   'Tine.widgets.grid.GridPanel.addButton'
                            }, {
                                ptype: 'ux.itemregistry',
                                key:   'Tinebase-MainContextMenu'
                            }]
                        })
                    }) :
                    Ext.apply(new Ext.Button(this.action_file_upload), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),

                Ext.apply(new Ext.Button(this.action_editFile), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                }),
                Ext.apply(new Ext.Button(this.action_deleteRecord), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                }),
                Ext.apply(new Ext.Button(this.action_createFolder), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                }),
                Ext.apply(new Ext.Button(this.action_goUpFolder), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                }),
                Ext.apply(new Ext.Button(this.action_download), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                }),
                Ext.apply(new Ext.Button(this.action_publish), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                }),
                Ext.apply(new Ext.Button(this.action_systemLink), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                }),
                Ext.apply(new Ext.Button(this.action_preview), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                })
            ];

            if (this.dataSafeEnabled) {
                items.push(Ext.apply(new Ext.Button(this.action_dataSafe), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                }));
            }

            this.actionToolbar = new Ext.Toolbar({
                defaults: {height: 55},
                items: [{
                    xtype: 'buttongroup',
                    layout: 'toolbar',
                    buttonAlign: 'left',
                    columns: 8,
                    defaults: {minWidth: 60},
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-GridPanel-ActionToolbar-leftbtngrp'
                    }],
                    items: items
                }, this.getActionToolbarItems()]
            });

            this.actionToolbar.on('resize', this.onActionToolbarResize, this, {buffer: 250});
            this.actionToolbar.on('show', this.onActionToolbarResize, this);

            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            }

            this.actionUpdater.addActions(this.actionToolbar.items);
        }

        return this.actionToolbar;
    },

    /**
     * grid row doubleclick handler
     *
     * @param {Tine.Filemanager.NodeGridPanel} grid
     * @param {} row record
     * @param {Ext.EventObjet} e
     */
    onRowDblClick: Tine.widgets.dialog.AttachmentsGridPanel.prototype.onRowDbClick,

    /**
     * expand folder node
     *
     * @param {Sting | Tine.Filemanager.Model.Node} path
     */
    expandFolder: function (nodeData) {
        if (nodeData?.json?.status === 'pending') {
            return;
        }
        
        const path = _.get(nodeData, 'data.path', nodeData);
        
        this.filterToolbar.filterStore.each(function (filter) {
            var field = filter.get('field');
            if (field === 'path') {
                filter.set('value', '');
                filter.formFields.value.setValue(path);
                this.filterToolbar.onFiltertrigger();
                return false;
            }
        }, this);
    },

    /**
     * on remove handler
     *
     * @param {} button
     * @param {} event
     */
    onRemove: function (button, event) {
        var selectedRows = this.selectionModel.getSelections();
        for (var i = 0; i < selectedRows.length; i += 1) {
            this.store.remove(selectedRows[i]);
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));

            if (upload) {
                upload.setPaused(true);
            }
        }
    },

    /**
     * populate grid store
     *
     * @param {} record
     */
    loadRecord: function (record) {
        if (record && record.get(this.filesProperty)) {
            var files = record.get(this.filesProperty);
            for (var i = 0; i < files.length; i += 1) {
                var file = new Ext.ux.file.Upload.file(files[i]);
                file.set('status', 'complete');
                file.set('nodeRecord', new Tine.Filemanager.Model.Node(file.data));
                this.store.add(file);
            }
        }
    },
    

    /**
     * upload new files and add to store
     *
     * - handle both folder and files
     * 
     * @param {ux.BrowsePlugin} fileSelector
     * @param event
     */
    onFilesSelect: async function (fileSelector, event) {
        const targetNode = _.get(this.getFilteredContainers(), '0');
        const gridStore = this.store;
        let rowIndex = false;
        let nodeRecord = null;
        this.targetFolderPath = targetNode?.attributes ? targetNode.attributes.path : _.get(targetNode, 'path');

        if(event && event.getTarget()) {
            rowIndex = this.getView().findRowIndex(event.getTarget());
        }
        
        if(targetNode.attributes) {
            nodeRecord = targetNode.attributes.nodeRecord;
        }

        if(rowIndex !== false && rowIndex > -1) {
            var newTargetNode = gridStore.getAt(rowIndex);
            if(newTargetNode && newTargetNode.data.type === 'folder') {
                this.targetFolderPath = newTargetNode.data.path;
                nodeRecord = new Tine.Filemanager.Model.Node(newTargetNode.data);
            }
        }

        if(!nodeRecord) {
            nodeRecord = new Tine.Filemanager.Model.Node(targetNode);
        }

        let files = fileSelector.getFileList();
        const folderList = _.uniq(_.map(files, (fo) => {
            return fo.fullPath.replace(/\/[^/]*$/, '');
        }));
        
        if(folderList.includes('') && !Tine.Filemanager.nodeActionsMgr.checkConstraints('create', nodeRecord, [{type: 'file'}])) {
            const app = Tine.Tinebase.appMgr.get('Filemanager');
            Ext.MessageBox.alert(
                    i18n._('Upload Failed'),
                    app.i18n._('It is not permitted to store files in this folder!')
            ).setIcon(Ext.MessageBox.ERROR);

            return;
        }
        
        await upload(this.targetFolderPath, files);
    },

    /**
     * grid on load handler
     *
     * @param store
     * @param records
     * @param options
     */
    onLoad: function(store, records, options){
        const quota = _.get(store, 'reader.jsonData.quota', false);
            
        if (quota) {
            var qhtml = Tine.widgets.grid.QuotaRenderer(quota.effectiveUsage, quota.effectiveQuota, /*use SoftQuota*/ true);
            this.quotaBar.show();
            if (this.quotaBar.rendered) {
                this.quotaBar.update(qhtml);
            } else {
                this.quotaBar.html = qhtml;
            }
        } else {
            this.quotaBar.hide();
        }
    },

    /**
     * gets currently displayed node in case a path filter is set
     * NOTE: this data is unresolved as it comes from filter and not through json convert!
     *
     * @return {Object}
     */
    getFilteredContainers: function () {
        const pathFilter = _.get(_.find(_.get(this, 'store.reader.jsonData.filter', {}), {field: 'path'}), 'value');
        return pathFilter ? [pathFilter] : null;
    },
    
    /**
     * get parent path 
     *
     * @param path
     * @returns {string|*}
     */
    getParentPath: function (path) {
        if (String(path).match(/\/.*\/.+/)) {
            let pathParts = path.split('/');
            pathParts.pop();
            // handle folder path that end with '/' 
            if (path.endsWith('/')) {
                pathParts.pop();
            }
            return pathParts.join('/');
        }
        return '/';
    }
});
