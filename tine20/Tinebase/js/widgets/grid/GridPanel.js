/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * tine 2.0 app grid panel widget
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.GridPanel
 * @extends     Ext.Panel
 * 
 * <p>Application Grid Panel</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new GridPanel
 */
Tine.widgets.grid.GridPanel = function(config) {
    Ext.apply(this, config);
    
    
    this.gridConfig = this.gridConfig || {};
    this.defaultSortInfo = this.defaultSortInfo || {};
    this.defaultPaging = this.defaultPaging || {
        start: 0,
        limit: 50
    };
    
    // autogenerate stateId
    if (this.stateful !== false && ! this.stateId) {
        this.stateId = this.recordClass.getMeta('appName') + '-' + this.recordClass.getMeta('recordName') + '-GridPanel';
    }
        
    Tine.widgets.grid.GridPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.grid.GridPanel, Ext.Panel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    /**
     * @cfg {Object} gridConfig
     * Config object for the Ext.grid.GridPanel
     */
    gridConfig: null,
    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    /**
     * @cfg {Ext.data.DataProxy} recordProxy
     */
    recordProxy: null,
    /**
     * @cfg {Tine.widgets.grid.FilterToolbar} filterToolbar
     */
    filterToolbar: null,
    /**
     * @cfg {Bool} evalGrants
     * should grants of a grant-aware records be evaluated (defaults to true)
     */
    evalGrants: true,
    /**
     * @cfg {Bool} filterSelectionDelete
     * is it allowed to deleteByFilter?
     */
    filterSelectionDelete: false,
    /**
     * @cfg {Object} defaultSortInfo
     */
    defaultSortInfo: null,
    /**
     * @cfg {Object} storeRemoteSort
     */
    storeRemoteSort: true,
    /**
     * @cfg {Bool} usePagingToolbar 
     */
    usePagingToolbar: true,
    /**
     * @cfg {Object} defaultPaging 
     */
    defaultPaging: null,
    /**
     * @cfg {Object} pagingConfig
     * additional paging config
     */
    pagingConfig: null,
    /**
     * @cfg {Tine.widgets.grid.DetailsPanel} detailsPanel
     * if set, it becomes rendered in region south 
     */
    detailsPanel: null,
    /**
     * @cfg {Array} i18nDeleteQuestion 
     * spechialised strings for deleteQuestion
     */
    i18nDeleteQuestion: null,
    /**
     * @cfg {String} i18nAddRecordAction 
     * spechialised strings for add action button
     */
    i18nAddActionText: null,
    /**
     * @cfg {String} i18nEditRecordAction 
     * specialised strings for edit action button
     */
    i18nEditActionText: null,
    /**
     * @cfg {Array} i18nDeleteRecordAction 
     * specialised strings for delete action button
     */
    i18nDeleteActionText: null,
    
    /**
     * @cfg {Object} editDialogConfig 
     * config passed to edit dialog
     */
    editDialogConfig: null,
    
    /**
     * @cfg {String} editDialogClass 
     */
    editDialogClass: null,
    
    /**
     * @cfg {String} i18nEmptyText 
     */
    i18nEmptyText: null,
    
    /**
     * @cfg {String} newRecordIcon 
     * icon for adding new records button
     */
    newRecordIcon: null,

    /**
     * @cfg {Bool} i18nDeleteRecordAction 
     * update details panel if context menu is shown
     */
    updateDetailsPanelOnCtxMenu: true,
    
    /**
     * @cfg {Number} autoRefreshInterval (seconds)
     */
    autoRefreshInterval: 300,
    
    /**
     * @cfg {Bool} hasFavoritesPanel 
     */
    hasFavoritesPanel: true,
    
    /**
     * @cfg {Bool} hasQuickSearchFilterToolbarPlugin 
     */
    hasQuickSearchFilterToolbarPlugin: true,
    
    /**
     * disable 'select all pages' in paging toolbar
     * @cfg {Bool} disableSelectAllPages
     */
    disableSelectAllPages: false,
    
    /**
     * enable if records should be multiple editable
     * @cfg {Bool} multipleEdit
     */
    multipleEdit: false,
    
    /**
     * @property autoRefreshTask
     * @type Ext.util.DelayedTask
     */
    autoRefreshTask: null,
    
    /**
     * @type Bool
     * @property updateOnSelectionChange
     */
    updateOnSelectionChange: true,

    /**
     * @type Bool
     * @property copyEditAction
     * 
     * TODO activate this by default
     */
    copyEditAction: false,

    /**
     * @type Ext.Toolbar
     * @property actionToolbar
     */
    actionToolbar: null,
    
    /**
     * @type Ext.ux.grid.PagingToolbar
     * @property pagingToolbar
     */
    pagingToolbar: null,

    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,
    
    /**
     * @property lastStoreTransactionId 
     * @type String
     */
    lastStoreTransactionId: null,
    
    /**
     * @property editBuffer  - array of ids of records edited since last explicit refresh
     * @type Array of ids
     */
    editBuffer: null,
    
    /**
     * @property deleteQueue - array of ids of records currently being deleted
     * @type Array of ids
     */
    deleteQueue: null,
    
    /**
     * @property selectionModel
     * @type Tine.widgets.grid.FilterSelectionModel
     */
    selectionModel: null,
    
    
    layout: 'border',
    border: false,
    stateful: true,
    
    /**
     * extend standard initComponent chain
     * 
     * @private
     */
    initComponent: function(){
        // init some translations
        this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
        this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
        this.i18nContainerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
        this.i18nContainersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        this.i18nEmptyText = this.i18nEmptyText || String.format(Tine.Tinebase.translation._("No {0} where found. Please try to change your filter-criteria, view-options or the {1} you search in."), this.i18nRecordsName, this.i18nContainersName);
        
        this.editDialogConfig = this.editDialogConfig || {};
        
        this.editBuffer = [];
        this.deleteQueue = [];
        
        // init store
        this.initStore();
        // init (ext) grid
        this.initGrid();
        
        // init actions
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            containerProperty: this.recordClass.getMeta('containerProperty'), 
            evalGrants: this.evalGrants
        });
        this.initActions();
        
        this.initLayout();
        
        // for some reason IE looses split height when outer layout is layouted
        if (Ext.isIE6 || Ext.isIE7) {
            this.on('show', function() {
                if (this.layout.rendered && this.detailsPanel) {
                    var height = this.detailsPanel.getSize().height;
                    this.layout.south.split.setCurrentSize(height);
                }
            }, this);
        }
        
        Tine.widgets.grid.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     * 
     * NOTE: Order of items matters! Ext.Layout.Border.SplitRegion.layout() does not
     *       fence the rendering correctly, as such it's impotant, so have the ftb
     *       defined after all other layout items
     */
    initLayout: function() {
        this.items = [{
            region: 'center',
            xtype: 'panel',
            layout: 'fit',
            border: false,
            tbar: this.pagingToolbar,
            items: this.grid
        }];
        
        // add detail panel
        if (this.detailsPanel) {
            this.items.push({
                region: 'south',
                border: false,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                split: true,
                layout: 'fit',
                height: this.detailsPanel.defaultHeight ? this.detailsPanel.defaultHeight : 125,
                items: this.detailsPanel
                
            });
            this.detailsPanel.doBind(this.grid);
        }
        
        // add filter toolbar
        if (this.filterToolbar) {
            this.items.push({
                region: 'north',
                border: false,
                autoScroll: true,
                items: this.filterToolbar,
                listeners: {
                    scope: this,
                    afterlayout: function(ct) {
                    	ct.suspendEvents();
                        ct.setHeight(Math.min(120, this.filterToolbar.getHeight()));
                        ct.getEl().child('div[class^="x-panel-body"]', true).scrollTop = 1000000;
                        ct.ownerCt.layout.layout();
                        ct.resumeEvents();
                    }
                }
            });
        }

    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        this.action_editInNewWindow = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.i18nEditActionText ? this.i18nEditActionText[0] : String.format(_('Edit {0}'), this.i18nRecordName),
            singularText: this.i18nEditActionText ? this.i18nEditActionText[0] : String.format(_('Edit {0}'), this.i18nRecordName),
            pluralText:  this.i18nEditActionText ? this.i18nEditActionText[1] : String.format(Tine.Tinebase.translation.ngettext('Edit {0}', 'Edit {0}', 1), this.i18nRecordsName),
            disabled: true,
            translationObject: this.i18nEditActionText ? this.app.i18n : Tine.Tinebase.translation,
            actionType: 'edit',
            handler: this.onEditInNewWindow,
            iconCls: 'action_edit',
            scope: this,
            allowMultiple: true
        });
        
        this.action_editCopyInNewWindow = new Ext.Action({
            hidden: ! this.copyEditAction,
            requiredGrant: 'readGrant',
            text: String.format(_('Copy {0}'), this.i18nRecordName),
            disabled: true,
            actionType: 'copy',
            handler: this.onEditInNewWindow,
            iconCls: 'action_editcopy',
            scope: this
        });
        
        this.action_addInNewWindow = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(_('Add {0}'), this.i18nRecordName),
            handler: this.onEditInNewWindow,
            iconCls: (this.newRecordIcon !== null) ? this.newRecordIcon : this.app.appName + 'IconCls',
            scope: this
        });
        
        this.actions_print = new Ext.Action({
            requiredGrant: 'readGrant',
            text: _('Print Page'),
            disabled: false,
            handler: function() {
                Ext.ux.Printer.print(this.getGrid());
            },
            iconCls:'action_print',
            scope: this,
            allowMultiple: true
        });
        
        this.initDeleteAction();
        
        this.action_tagsMassAttach = new Tine.widgets.tags.TagsMassAttachAction({
            hidden:         ! this.recordClass.getField('tags'),
            selectionModel: this.grid.getSelectionModel(),
            recordClass:    this.recordClass,
            updateHandler:  this.loadGridData.createDelegate(this),
            app:            this.app
        });

        this.action_tagsMassDetach = new Tine.widgets.tags.TagsMassDetachAction({
            hidden:         ! this.recordClass.getField('tags'),
            selectionModel: this.grid.getSelectionModel(),
            recordClass:    this.recordClass,
            updateHandler:  this.loadGridData.createDelegate(this),
            app:            this.app
        });        
        
        // add actions to updater
        this.actionUpdater.addActions([
            this.action_addInNewWindow,
            this.action_editInNewWindow,
            this.action_deleteRecord,
            this.action_tagsMassAttach,
            this.action_tagsMassDetach,
            this.action_editCopyInNewWindow
        ]);
        
        // init actionToolbar (needed for correct fitertoolbar init atm -> fixme)
        this.getActionToolbar();
    },
    
    initDeleteAction: function() {
        // note: unprecise plural form here, but this is hard to change
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(Tine.Tinebase.translation.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            pluralText: this.i18nDeleteActionText ? this.i18nDeleteActionText[1] : String.format(Tine.Tinebase.translation.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordsName),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.translation,
            text: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(Tine.Tinebase.translation.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
    },
    
    /**
     * init store
     * @private
     */
    initStore: function() {
        if (this.recordProxy) {
            this.store = new Ext.data.Store({
                fields: this.recordClass,
                proxy: this.recordProxy,
                reader: this.recordProxy.getReader(),
                remoteSort: this.storeRemoteSort,
                sortInfo: this.defaultSortInfo,
                listeners: {
                    scope: this,
                    'update': this.onStoreUpdate,
                    'beforeload': this.onStoreBeforeload,
                    'load': this.onStoreLoad,
                    'beforeloadrecords': this.onStoreBeforeLoadRecords,
                    'loadexception': this.onStoreLoadException
                }
            });
        } else {
            this.store = new Tine.Tinebase.data.RecordStore({
                recordClass: this.recordClass
            });
        }
        
        // init autoRefresh
        this.autoRefreshTask = new Ext.util.DelayedTask(this.loadGridData, this, [{
            removeStrategy: 'keepBuffered',
            autoRefresh: true
        }]);
    },
    
    /**
     * returns view row class
     */
    getViewRowClass: function(record, index, rowParams, store) {
        var noLongerInFilter = record.not_in_filter;
        
        var className = '';
        if (noLongerInFilter) {
            className += 'tine-grid-row-nolongerinfilter';
        }
        return className;    
    },    
    
    /**
     * new entry event -> add new record to store
     * 
     * @param {Object} recordData
     * @return {Boolean}
     */
    onStoreNewEntry: function(recordData) {
        
        var initialData = null;
        if (Ext.isFunction(this.recordClass.getDefaultData)) {
            initialData = Ext.apply(this.recordClass.getDefaultData(), recordData);
        } else {
            initialData = recordData;
        }
        var record = new this.recordClass(initialData);
        this.store.insert(0 , [record]);
        
        if (this.usePagingToolbar) {
            this.pagingToolbar.refresh.disable();
        }
        this.recordProxy.saveRecord(record, {
            scope: this,
            success: function(newRecord) {
                this.store.suspendEvents();
                this.store.remove(record);
                this.store.insert(0 , [newRecord]);
                this.store.resumeEvents();
                
                this.addToEditBuffer(newRecord);
                
                this.loadGridData({
                    removeStrategy: 'keepBuffered'
                });
            }
        });
            
        return true;
    },

    /**
     * header is clicked
     * 
     * @param {Object} grid
     * @param {Number} colIdx
     * @param {Event} e
     * @return {Boolean}
     */
    onHeaderClick: function(grid, colIdx, e) {

        Ext.apply(this.store.lastOptions, {
            preserveCursor:     true,
            preserveSelection:  true, 
            preserveScroller:   true, 
            removeStrategy:     'default'
        });
    },
    
    /**
     * called when the store gets updated, e.g. from editgrid
     */
    onStoreUpdate: function(store, record, operation) {
         
        switch (operation) {
            case Ext.data.Record.EDIT:
            
                this.addToEditBuffer(record);
                if (this.usePagingToolbar) {
                    this.pagingToolbar.refresh.disable();
                }
                
                this.recordProxy.saveRecord(record, {
                    scope: this,
                    success: function(updatedRecord) {
                        store.commitChanges();
                        
                        // update record in store to prevent concurrency problems
                        record.data = updatedRecord.data;
                        
                        this.loadGridData({
                            removeStrategy: 'keepBuffered'
                        });
                    }
                });
                break;
            case Ext.data.Record.COMMIT:
                //nothing to do, as we need to reload the store anyway.
                break;
        }
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        
        // define a transaction
        this.lastStoreTransactionId = options.transactionId = Ext.id();

        options.params = options.params || {};
        // allways start with an empty filter set!
        // this is important for paging and sort header!
        options.params.filter = [];
        
        if (! options.removeStrategy || options.removeStrategy !== 'keepBuffered') {
            this.editBuffer = [];
        }
        
//        options.preserveSelection = options.hasOwnProperty('preserveSelection') ? options.preserveSelection : true;
//        options.preserveScroller = options.hasOwnProperty('preserveScroller') ? options.preserveScroller : true;
        
        // fix nasty paging tb
        Ext.applyIf(options.params, this.defaultPaging);
    },
    
    /**
     * called after a new set of Records has been loaded
     * 
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
        // we always focus the first row so that keynav starts in the grid
        // this resets scroller ;-( -> need a better solution
//        if (this.store.getCount() > 0) {
//            this.grid.getView().focusRow(0);
//        }
        
       
        // restore selection
        if (Ext.isArray(options.preserveSelection)) {
            Ext.each(options.preserveSelection, function(record) {
                var row = this.store.indexOfId(record.id);
                if (row >= 0) {
                    this.grid.getSelectionModel().selectRow(row, true);
                }
            }, this);
        }
        
        // restore scroller
        if (Ext.isNumber(options.preserveScroller)) {
            this.grid.getView().scroller.dom.scrollTop = options.preserveScroller;
        }
        
        // reset autoRefresh
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 1000);
        }
    },
    
    /**
     * on store load exception
     * 
     * @param {Tine.Tinebase.data.RecordProxy} proxy
     * @param {String} type
     * @param {Object} error
     * @param {Object} options
     */
    onStoreLoadException: function(proxy, type, error, options) {
             
        // reset autoRefresh
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 5000);
        }
        
        if (this.usePagingToolbar && this.pagingToolbar.refresh) {
            this.pagingToolbar.refresh.enable();
        }
        
        if (! options.autoRefresh) {
            proxy.handleRequestException(error);
        }
    },
    
    /**
     * onStoreBeforeLoadRecords
     * 
     * @param {Object} o
     * @param {Object} options
     * @param {Boolean} success
     * @param {Ext.data.Store} store
     */
    onStoreBeforeLoadRecords: function(o, options, success, store) {
              
        if (this.lastStoreTransactionId && options.transactionId && this.lastStoreTransactionId !== options.transactionId) {
            Tine.log.debug('cancelling old transaction request.');
            return false;
        }
        
        // save selection -> will be applied onLoad
        if (options.preserveSelection) {
            options.preserveSelection = this.grid.getSelectionModel().getSelections();
        }
        
        // save scroller -> will be applied onLoad
        if (options.preserveScroller) {
            options.preserveScroller = this.grid.getView().scroller.dom.scrollTop;
        }
        
        // apply removeStrategy
        if (! options.removeStrategy || options.removeStrategy === 'default') {
            return true;
        }
        
        var records = [],
            recordsIds = [],
            newRecordCollection = new Ext.util.MixedCollection();
            
        // fill new collection
        Ext.each(o.records, function(record) {
            newRecordCollection.add(record.id, record);
        });
        
        // assemble update & keep
        this.store.each(function(record) {
            var newRecord = newRecordCollection.get(record.id);
            if (newRecord) {
                records.push(newRecord);
                recordsIds.push(newRecord.id);
            } else if (options.removeStrategy === 'keepAll' || (options.removeStrategy === 'keepBuffered' && this.editBuffer.indexOf(record.id) >= 0)) {
                var copiedRecord = record.copy();
                copiedRecord.not_in_filter = true;
                records.push(copiedRecord);
                recordsIds.push(record.id);
            }
        }, this);
        
        // assemble adds
        newRecordCollection.each(function(record, idx) {
            if (recordsIds.indexOf(record.id) == -1 && this.deleteQueue.indexOf(record.id) == -1) {
                var lastRecord = newRecordCollection.itemAt(idx-1);
                var lastRecordIdx = lastRecord ? recordsIds.indexOf(lastRecord.id) : -1;
                records.splice(lastRecordIdx+1, 0, record);
                recordsIds.splice(lastRecordIdx+1, 0, record.id);
            }
        }, this);
        
        o.records = records;
    },
    
    /**
     * perform the initial load of grid data
     */
    initialLoad: function() {
        var defaultFavorite = Tine.widgets.persistentfilter.model.PersistentFilter.getDefaultFavorite(this.app.appName);
        var favoritesPanel  = this.app.getMainScreen() && typeof this.app.getMainScreen().getWestPanel().getFavoritesPanel === 'function' && this.hasFavoritesPanel 
            ? this.app.getMainScreen().getWestPanel().getFavoritesPanel() 
            : null;
        if (defaultFavorite && favoritesPanel) {
            favoritesPanel.selectFilter(defaultFavorite);
        } else {
            this.store.load.defer(10, this.store, [
                typeof this.autoLoad == 'object' ?
                    this.autoLoad : undefined]);
        }
        
        if (this.usePagingToolbar && this.recordProxy) {
            this.pagingToolbar.refresh.disable.defer(10, this.pagingToolbar.refresh);
        }
    },
    
    /**
     * init ext grid panel
     * @private
     */
    initGrid: function() {
        // init sel model
        this.selectionModel = new Tine.widgets.grid.FilterSelectionModel({
            store: this.store
        });
        this.selectionModel.on('selectionchange', function(sm) {
            //Tine.widgets.actionUpdater(sm, this.actions, this.recordClass.getMeta('containerProperty'), !this.evalGrants);
            this.actionUpdater.updateActions(sm);
            this.ctxNode = this.selectionModel.getSelections();
            if (this.updateOnSelectionChange && this.detailsPanel) {
                this.detailsPanel.onDetailsUpdate(sm);
            }
        }, this);
        
        if (this.usePagingToolbar) {
            this.pagingToolbar = new Ext.ux.grid.PagingToolbar(Ext.apply({
                pageSize: this.defaultPaging && this.defaultPaging.limit ? this.defaultPaging.limit : 50,
                store: this.store,
                displayInfo: true,
                displayMsg: Tine.Tinebase.translation._('Displaying records {0} - {1} of {2}').replace(/records/, this.i18nRecordsName),
                emptyMsg: String.format(Tine.Tinebase.translation._("No {0} to display"), this.i18nRecordsName),
                displaySelectionHelper: true,
                sm: this.selectionModel,
                disableSelectAllPages: this.disableSelectAllPages
            }, this.pagingConfig));
            // mark next grid refresh as paging-refresh
            this.pagingToolbar.on('beforechange', function() {
                this.grid.getView().isPagingRefresh = true;
            }, this);
        }
        
        // init view
        var view =  new Ext.grid.GridView({
            getRowClass: this.getViewRowClass,
            autoFill: true,
            forceFit:true,
            ignoreAdd: true,
            emptyText: this.i18nEmptyText,
            onLoad: Ext.grid.GridView.prototype.onLoad.createInterceptor(function() {
                if (this.grid.getView().isPagingRefresh) {
                    this.grid.getView().isPagingRefresh = false;
                    return true;
                }
                
                return false;
            }, this)
        });
        
        // which grid to use?
        var Grid = this.gridConfig.quickaddMandatory ? Ext.ux.grid.QuickaddGridPanel : Ext.grid.GridPanel;
        
        this.gridConfig.store = this.store;
        
        // activate grid header menu for column selection
        this.gridConfig.plugins = this.gridConfig.plugins ? this.gridConfig.plugins : [];
        this.gridConfig.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        this.gridConfig.enableHdMenu = false;
        
        if (this.stateful) {
            this.gridConfig.stateful = true;
            this.gridConfig.stateId  = this.stateId + '-Grid';
        }
        
        this.grid = new Grid(Ext.applyIf(this.gridConfig, {
            border: false,
            store: this.store,
            sm: this.selectionModel,
            view: view
        }));
        
        // init various grid / sm listeners
        this.grid.on('keydown',     this.onKeyDown,         this);
        this.grid.on('rowclick',    this.onRowClick,        this);
        this.grid.on('rowdblclick', this.onRowDblClick,     this);
        this.grid.on('newentry',    this.onStoreNewEntry,   this);
        this.grid.on('headerclick', this.onHeaderClick,   this);
        
        this.grid.on('rowcontextmenu', function(grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if(!selModel.isSelected(row)) {
                // disable preview update if config option is set to false
                this.updateOnSelectionChange = this.updateDetailsPanelOnCtxMenu;
                selModel.selectRow(row);
            }
            
            this.getContextMenu().showAt(e.getXY());
            // reset preview update
            this.updateOnSelectionChange = true;
        }, this);
        
    },
    
    /**
     * executed after outer panel rendering process
     */
    afterRender: function() {
        Tine.widgets.grid.GridPanel.superclass.afterRender.apply(this, arguments);
        this.initialLoad();
    },
    
    /**
     * trigger store load with grid related options
     * 
     * TODO rethink -> preserveCursor and preserveSelection might conflict on page breaks!
     * TODO don't reload details panel when selection is preserved
     * 
     * @param {Object} options
     */
    loadGridData: function(options) {
    	var options = options || {};
    	
        Ext.applyIf(options, {
            callback:           Ext.emptyFn,
            scope:              this,
            params:             {},
            
            preserveCursor:     true, 
            preserveSelection:  true, 
            preserveScroller:   true, 
            removeStrategy:     'default'
        });
        
        if (options.preserveCursor && this.usePagingToolbar) {
            options.params.start = this.pagingToolbar.cursor;
        }
        
        this.store.load(options);
    },
    
    /**
     * get action toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            var additionalItems = this.getActionToolbarItems();
            
            this.actionToolbar = new Ext.Toolbar({
                items: [{
                    xtype: 'buttongroup',
//                    columns: 3 + (Ext.isArray(additionalItems) ? additionalItems.length : 0),
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   this.app.appName + '-GridPanel-ActionToolbar-leftbtngrp'
                    }],
                    items: [
                        Ext.apply(new Ext.SplitButton(this.action_addInNewWindow), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top',
                            arrowAlign:'right',
                            menu: new Ext.menu.Menu({
                                items: [],
                                plugins: [{
                                    ptype: 'ux.itemregistry',
                                    key:   'Tine.widgets.grid.GridPanel.addButton'
                                }]
                            })
                        }),
                        Ext.apply(new Ext.Button(this.action_editInNewWindow), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_deleteRecord), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.actions_print), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        })
                    ].concat(Ext.isArray(additionalItems) ? additionalItems : [])
                }].concat(Ext.isArray(additionalItems) ? [] : [additionalItems])
            });
            
            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            }
        }
        
        return this.actionToolbar;
    },
    
    /**
     * template fn for subclasses to add custom items to action toolbar
     * 
     * @return {Array/Object}
     */
    getActionToolbarItems: function() {
        var items = this.actionToolbarItems || [];
        
        if (! Ext.isEmpty(items)) {
            // legacy handling! subclasses should register all actions when initializing actions
            this.actionUpdater.addActions(items);
        }
        
        return items;
    },
    
    /**
     * returns rows context menu
     * 
     * @return {Ext.menu.Menu}
     */
    getContextMenu: function() {
                
        if (! this.contextMenu) {
            var items = [
                this.action_addInNewWindow,
                this.action_editCopyInNewWindow,
                this.action_editInNewWindow,
                this.action_deleteRecord
            ];
            
            if (! this.action_tagsMassAttach.hidden) {
                items.push('-', this.action_tagsMassAttach, this.action_tagsMassDetach);
            }
            
            // lookup additional items
            items = items.concat(this.getContextMenuItems());

            // New record of another app
            this.newRecordMenu = new Ext.menu.Menu({
                items: [],
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-GridPanel-ContextMenu-New'
                }]
            });
            
            this.newRecordAction = new Ext.Action({
                text: this.app.i18n._('New...'),
                hidden: ! this.newRecordMenu.items.length,
                iconCls: this.app.getIconCls(),
                scope: this,
                menu: this.newRecordMenu
            });
            
            items.push(this.newRecordAction);

            // Add to record of another app            
            this.addToRecordMenu = new Ext.menu.Menu({
                items: [],
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-GridPanel-ContextMenu-Add'
                }]
            });
            
            this.addToRecordAction = new Ext.Action({
                text: this.app.i18n._('Add to...'),
                hidden: ! this.addToRecordMenu.items.length,
                iconCls: this.app.getIconCls(),
                scope: this,
                menu: this.addToRecordMenu
            });            
            
            items.push(this.addToRecordAction);
            
            this.contextMenu = new Ext.menu.Menu({
                items: items,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-GridPanel-ContextMenu'
                }]
            });
        }
        
        return this.contextMenu;
    },
    
    /**
     * template fn for subclasses to add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = this.contextMenuItems || [];
        
       
        
        if (! Ext.isEmpty(items)) {
            // legacy handling! subclasses should register all actions when initializing actions
            this.actionUpdater.addActions(items);
        }
        
        return items;
    },
    
    /**
     * get modlog columns
     * 
     * @return {Array}
     */
    getModlogColumns: function() {
        var result = [
            { id: 'creation_time',      header: _('Creation Time'),         dataIndex: 'creation_time',         renderer: Tine.Tinebase.common.dateRenderer,        hidden: true },
            { id: 'created_by',         header: _('Created By'),            dataIndex: 'created_by',            renderer: Tine.Tinebase.common.usernameRenderer,    hidden: true },
            { id: 'last_modified_time', header: _('Last Modified Time'),    dataIndex: 'last_modified_time',    renderer: Tine.Tinebase.common.dateRenderer,        hidden: true },
            { id: 'last_modified_by',   header: _('Last Modified By'),      dataIndex: 'last_modified_by',      renderer: Tine.Tinebase.common.usernameRenderer,    hidden: true }
        ];
        
        return result;
    },

    /**
     * get custom field columns for column model
     * 
     * @return {Array}
     */
    getCustomfieldColumns: function() {
        var modelName = this.recordClass.getMeta('appName') + '_Model_' + this.recordClass.getMeta('modelName'),
            cfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, modelName),
            result = [];
            
        Ext.each(cfConfigs, function(cfConfig) {
            result.push({
                id: cfConfig.id,
                header: cfConfig.get('definition').label,
                dataIndex: 'customfields',
                renderer: Tine.widgets.customfields.Renderer.get(this.app, cfConfig),
//                renderer: Tine.Tinebase.common.customfieldRenderer.createDelegate(this, [allCfs[i].name], true),
                sortable: false,
                hidden: true
            });
        }, this);
        
        return result;
    },
    
    /**
     * get custom field filter for filter toolbar
     * 
     * @return {Array}
     */
    getCustomfieldFilters: function() {
        var modelName = this.recordClass.getMeta('appName') + '_Model_' + this.recordClass.getMeta('modelName'),
            cfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, modelName),
            result = [];
        Ext.each(cfConfigs, function(cfConfig) {
            result.push({filtertype: 'tinebase.customfield', app: this.app, cfConfig: cfConfig});
        }, this);
        
        return result;
    },
    
    /**
     * returns filter toolbar
     * @private
     */
    getFilterToolbar: function(config) {
        config = config || {};
        var plugins = [];
        if (! Ext.isDefined(this.hasQuickSearchFilterToolbarPlugin) || this.hasQuickSearchFilterToolbarPlugin) {
            this.quickSearchFilterToolbarPlugin = new Tine.widgets.grid.FilterToolbarQuickFilterPlugin();
            plugins.push(this.quickSearchFilterToolbarPlugin);
        }
        
        return new Tine.widgets.grid.FilterPanel(Ext.apply(config, {
            app: this.app,
            recordClass: this.recordClass,
            filterModels: this.recordClass.getFilterModel().concat(this.getCustomfieldFilters()),
            defaultFilter: 'query',
            filters: this.defaultFilters || [],
            plugins: plugins
        }));
    },
    
    /**
     * return store from grid
     * 
     * @return {Ext.data.Store}
     */
    getStore: function() {
        return this.grid.getStore();
    },
    
    /**
     * return view from grid
     * 
     * @return {Ext.grid.GridView}
     */
    getView: function() {
        return this.grid.getView();
    },
    
    /**
     * return grid
     * 
     * @return {Ext.ux.grid.QuickaddGridPanel|Ext.grid.GridPanel}
     */
    getGrid: function() {
        return this.grid;
    },
    
    /**
     * key down handler
     * @private
     */
    onKeyDown: function(e){
        if (e.ctrlKey) {
            switch (e.getKey()) {
                case e.A:
                    // select only current page
                    this.grid.getSelectionModel().selectAll(true);
                    e.preventDefault();
                    break;
                case e.E:
                    if (this.action_editInNewWindow && !this.action_editInNewWindow.isDisabled()) {
                        this.onEditInNewWindow.call(this, {
                            actionType: 'edit'
                        });
                        e.preventDefault();
                    }
                    break;
                case e.N:
                    if (this.action_addInNewWindow && !this.action_addInNewWindow.isDisabled()) {
                        this.onEditInNewWindow.call(this, {
                            actionType: 'add'
                        });
                        e.preventDefault();
                    }
                    break;
                case e.F:
                    if (this.filterToolbar && this.hasQuickSearchFilterToolbarPlugin) {
                        e.preventDefault();
                        this.filterToolbar.getQuickFilterPlugin().quickFilter.focus();
                    }
                    break;
            }
        } else {
            if ([e.BACKSPACE, e.DELETE].indexOf(e.getKey()) !== -1) {
                if (!this.grid.editing && !this.grid.adding && !this.action_deleteRecord.isDisabled()) {
                    this.onDeleteRecords.call(this);
                    e.preventDefault();
                }
            }
        }
    },
    
    /**
     * row click handler
     * 
     */
    onRowClick: function(grid, row, e) {
        /* TODO check if we need this in IE
        // hack to get percentage editor working
        var cell = Ext.get(grid.getView().getCell(row,1));
        var dom = cell.child('div:last');
        while (cell.first()) {
            cell = cell.first();
            cell.on('click', function(e){
                e.stopPropagation();
                grid.fireEvent('celldblclick', grid, row, 1, e);
            });
        }
        */
        
        // fix selection of one record if shift/ctrl key is not pressed any longer
        if(e.button === 0 && !e.shiftKey && !e.ctrlKey) {
            var sm = grid.getSelectionModel();
            
            if (sm.getCount() == 1 && sm.isSelected(row)) {
                return;
            }
            
            sm.clearSelections();
            sm.selectRow(row, false);
            grid.view.focusRow(row);
        }
    },

    /**
     * row doubleclick handler
     * 
     * @param {} grid
     * @param {} row
     * @param {} e
     */
    onRowDblClick: function(grid, row, e) {
        this.onEditInNewWindow.call(this, {actionType: 'edit'});
    }, 
    
    /**
     * generic edit in new window handler
     */
    onEditInNewWindow: function(button, event) {
        var record; 
        if (button.actionType == 'edit' || button.actionType == 'copy') {
            if (! this.action_editInNewWindow || this.action_editInNewWindow.isDisabled()) {
                // if edit action is disabled or not available, we also don't open a new window
                return false;
            }
            var selectedRows = this.grid.getSelectionModel().getSelections();
            var selectedRecords = [];
            
            Ext.each(selectedRows,function(el){
                selectedRecords.push(el.data);
            });
            
            record = selectedRows[0];
        } else {
            record = new this.recordClass(this.recordClass.getDefaultData(), 0);
        }
        
        var useMultiple = ((this.selectionModel.getCount() > 1) && (this.multipleEdit) && (button.actionType == 'edit')),
            selectedRecords = [];
        
        if (useMultiple && ! this.selectionModel.isFilterSelect ) {
            Ext.each(this.selectionModel.getSelections(), function(record) {
                selectedRecords.push(record.data);
            }, this );
        }
        var editDialogClass = this.editDialogClass || Tine[this.app.appName][this.recordClass.getMeta('modelName') + 'EditDialog'],
            config = null,
            popupWindow = editDialogClass.openWindow(Ext.copyTo(
            this.editDialogConfig || {}, {
                /* multi edit stuff: NOTE: due to cross window restrictions, we can only pass strings here  */
                useMultiple: useMultiple,
                selectedRecords: Ext.encode(selectedRecords),
                selectionFilter: Ext.encode(this.selectionModel.getSelectionFilter()),
                /* end multi edit stuff */
                record: editDialogClass.prototype.mode == 'local' ? Ext.encode(record.data) : record,
                copyRecord: (button.actionType == 'copy'),
                listeners: {
                    scope: this,
                    'update': ((this.selectionModel.getCount() > 1) && (this.multipleEdit)) ? this.onUpdateMultipleRecords : this.onUpdateRecord
                }
            }, 'useMultiple,selectedRecords,selectionFilter,record,listeners,copyRecord')
        );
    },
    
    onUpdateMultipleRecords: function() {
        this.store.reload();
    },
    
    /**
     * on update after edit
     * 
     * @param {String|Tine.Tinebase.data.Record} record
     */
    onUpdateRecord: function(record, mode) {

        if (Ext.isString(record) && this.recordProxy) {
            record = this.recordProxy.recordReader({responseText: record});
        } else if (record && Ext.isFunction(record.copy)) {
            record = record.copy();
        }
        
        if (record && Ext.isFunction(record.copy)) {
            var idx = this.getStore().indexOfId(record.id);
            if (idx >=0) {
                var isSelected = this.getGrid().getSelectionModel().isSelected(idx);
                this.getStore().removeAt(idx);
                this.getStore().insert(idx, [record]);
                
                if (isSelected) {
                    this.getGrid().getSelectionModel().selectRow(idx, true);
                }
            } else {
                this.getStore().add([record]);
            }
            this.addToEditBuffer(record);
        }
        
        if (mode == 'local') {
            this.onStoreUpdate(this.getStore(), record, Ext.data.Record.EDIT);
        } else {
            this.loadGridData({
                removeStrategy: 'keepBuffered'
            });
        }
    },
    
    /**
     * add record to edit buffer
     * 
     * @param {String|Tine.Tinebase.data.Record} record
     */
    addToEditBuffer: function(record) {

        var recordData = (Ext.isString(record)) ? Ext.decode(record) : record.data,
            id = recordData[this.recordClass.getMeta('idProperty')];
        
        if (this.editBuffer.indexOf(id) === -1) {
            this.editBuffer.push(id);
        }
    },
    
    /**
     * generic delete handler
     */
    onDeleteRecords: function(btn, e) {
        var sm = this.grid.getSelectionModel();
        
        if (sm.isFilterSelect && ! this.filterSelectionDelete) {
            Ext.MessageBox.show({
                title: _('Not Allowed'), 
                msg: _('You are not allowed to delete all pages at once'),
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.INFO
            });
            
            return;
        }
        var records = sm.getSelections();
        
        if (Tine[this.app.appName].registry.containsKey('preferences') 
            && Tine[this.app.appName].registry.get('preferences').containsKey('confirmDelete')
            && Tine[this.app.appName].registry.get('preferences').get('confirmDelete') == 0
        ) {
            // don't show confirmation question for record deletion
            this.deleteRecords(sm, records);
        } else {
            var recordNames = records[0].get(this.recordClass.getMeta('titleProperty'));
            if (records.length > 1) {
                recordNames += ', ...';
            }
            
            var i18nQuestion = this.i18nDeleteQuestion ?
                this.app.i18n.n_hidden(this.i18nDeleteQuestion[0], this.i18nDeleteQuestion[1], records.length) :
                String.format(Tine.Tinebase.translation.ngettext('Do you really want to delete the selected record ({0})?',
                    'Do you really want to delete the selected records ({0})?', records.length), recordNames);
            Ext.MessageBox.confirm(_('Confirm'), i18nQuestion, function(btn) {
                if(btn == 'yes') {
                    this.deleteRecords(sm, records);
                }
            }, this);
        }
    },
    
    /**
     * delete records
     * 
     * @param {SelectionModel} sm
     * @param {Array} records
     */
    deleteRecords: function(sm, records) {
        // directly remove records from the store (only for non-filter-selection)
        if (Ext.isArray(records) && ! (sm.isFilterSelect && this.filterSelectionDelete)) {
            Ext.each(records, function(record) {
                this.store.remove(record);
            });
        }
        
        if (this.recordProxy) {
            if (this.usePagingToolbar) {
                this.pagingToolbar.refresh.disable();
            }
            
            var i18nItems = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), records.length),
                recordIds = [].concat(records).map(function(v){ return v.id; });

            if (sm.isFilterSelect && this.filterSelectionDelete) {
                if (! this.deleteMask) {
                    this.deleteMask = new Ext.LoadMask(this.grid.getEl(), {
                        msg: String.format(_('Deleting {0}'), i18nItems) + _(' ... This may take a long time!')
                    });
                }
                this.deleteMask.show();
            }
            
            this.deleteQueue = this.deleteQueue.concat(recordIds);
            
            var options = {
                scope: this,
                success: function() {
                    this.refreshAfterDelete(recordIds);
                    this.onAfterDelete(recordIds);
                },
                failure: function () {
                    this.refreshAfterDelete(recordIds);
                    this.loadGridData();                    
                    Ext.MessageBox.alert(_('Failed'), String.format(_('Could not delete {0}.'), i18nItems)); 
                }
            };
            
            if (sm.isFilterSelect && this.filterSelectionDelete) {
            	this.recordProxy.deleteRecordsByFilter(sm.getSelectionFilter(), options);
            } else {
                this.recordProxy.deleteRecords(records, options);
            }
        }
    },
    
    /**
     * refresh after delete (hide delete mask or refresh paging toolbar)
     */
    refreshAfterDelete: function(ids) {
        this.deleteQueue = this.deleteQueue.diff(ids);
        
        if (this.deleteMask) {
            this.deleteMask.hide();
        }
        
        if (this.usePagingToolbar) {
            this.pagingToolbar.refresh.show();
        }
    },
    
    /**
     * do something after deletion of records
     * - reload the store
     * 
     * @param {Array} [ids]
     */
    onAfterDelete: function(ids) {
        this.editBuffer = this.editBuffer.diff(ids);
        
        this.loadGridData({
            removeStrategy: 'keepBuffered'
        });
    }
});
