/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tinebase.widgets.app');

/**
 * tine 2.0 app grid panel widget
 * 
 * @namespace   Tine.Tinebase.widgets.app
 * @class       Tine.Tinebase.widgets.app.GridPanel
 * @extends     Ext.Panel
 * 
 * <p>Application Grid Panel</p>
 * <p>
 * TODO         remove the loadmask on error
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new GridPanel
 */
Tine.Tinebase.widgets.app.GridPanel = function(config) {
    Ext.apply(this, config);
    
    
    this.gridConfig = this.gridConfig || {};
    this.defaultSortInfo = this.defaultSortInfo || {};
    this.defaultPaging = this.defaultPaging || {
        start: 0,
        limit: 50
    };
    
    this.actionToolbarItems = this.actionToolbarItems || [];
    this.contextMenuItems = this.contextMenuItems || [];
    
    // autogenerate stateId
    if (this.stateful !== false && ! this.stateId) {
        this.stateId = this.recordClass.getMeta('appName') + '-' + this.recordClass.getMeta('recordName') + '-GridPanel';
    }
        
    Tine.Tinebase.widgets.app.GridPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Tinebase.widgets.app.GridPanel, Ext.Panel, {
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
     * @cfg {Array} actionToolbarItems
     * additional items for actionToolbar
     */
    actionToolbarItems: null,
    /**
     * @cfg {Tine.widgets.grid.FilterToolbar} filterToolbar
     */
    filterToolbar: null,
    /**
     * @cfg {Array} contextMenuItems
     * additional items for contextMenu
     */
    contextMenuItems: null,
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
     * @cfg {Object} defaultPaging 
     */
    defaultPaging: null,
    /**
     * @cfg {Tine.Tinebase.widgets.grid.DetailsPanel} detailsPanel
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
     * @cfg {Bool} i18nDeleteRecordAction 
     * update details panel if context menu is shown
     */
    updateDetailsPanelOnCtxMenu: true,
    
    /**
     * @type Bool
     * @property updateOnSelectionChange
     */
    updateOnSelectionChange: true,

    /**
     * @type Bool
     * @property showDeleteMask
     */
    showDeleteMask: true,

    /**
     * @type Ext.Toolbar
     * @property actionToolbar
     */
    actionToolbar: null,

    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,
    
    /**
     * @type function
     * @property getViewRowClass 
     */
    getViewRowClass: null,
    
    layout: 'border',
    border: false,
    stateful: true,
    
    /**
     * extend standart initComponent chain
     * 
     * @private
     */
    initComponent: function(){
        // init some translations
        this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
        this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
        this.i18nContainerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
        this.i18nContainersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        
        // init store
        this.initStore();
        // init (ext) grid
        this.initGrid();
        // init actions with actionToolbar and contextMenu
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
        
        Tine.Tinebase.widgets.app.GridPanel.superclass.initComponent.call(this);
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
                split: true,
                layout: 'fit',
                height: this.detailsPanel.defaultHeight ? this.detailsPanel.defaultHeight : 125,
                items: this.detailsPanel
                
            });
            this.detailsPanel.doBind(this.grid);
        }
        
        // add filter toolbar
        if (this.filterToolbar) {
            this.items.push(this.filterToolbar);
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
            text: this.i18nEditActionText ? this.app.i18n._hidden(this.i18nEditActionText) : String.format(_('Edit {0}'), this.i18nRecordName),
            disabled: true,
            actionType: 'edit',
            handler: this.onEditInNewWindow,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.action_addInNewWindow = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(_('Add {0}'), this.i18nRecordName),
            handler: this.onEditInNewWindow,
            iconCls: this.app.appName + 'IconCls',
            scope: this
        });
        
        // note: unprecise plural form here, but this is hard to change
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(Tine.Tinebase.tranlation.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            pluralText: this.i18nDeleteActionText ? this.i18nDeleteActionText[1] : String.format(Tine.Tinebase.tranlation.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordsName),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.tranlation,
            text: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(Tine.Tinebase.tranlation.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        
        this.actions = [
            this.action_addInNewWindow,
            this.action_editInNewWindow,
            this.action_deleteRecord
        ];

        if (this.recordClass.getField('tags')) {
            this.action_tagsMassAttach = new Tine.widgets.tags.TagsMassAttachAction({
                selectionModel: this.grid.getSelectionModel(),
                recordClass:    this.recordClass,
                updateHandler:  this.loadData.createDelegate(this, [true])
            });
            
            this.contextMenuItems.push('-'/*, {xtype: 'menutextitem', text: _('Tagging')}*/, this.action_tagsMassAttach);
        }
        
        var actionToolbarItems = this.actions.concat(this.actionToolbarItems);
        if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
            actionToolbarItems.push('->');
            actionToolbarItems.push(this.filterToolbar.getQuickFilterField());
        }
        
        this.actionToolbar = new Ext.Toolbar({
            split: false,
            height: 26,
            items: actionToolbarItems
        });
        
        this.contextMenu = new Ext.menu.Menu({
            items: this.actions.concat(this.contextMenuItems)
        });
        
        // pool together all our actions, so that we can hand them over to our actionUpdater
        for (var all=this.actionToolbarItems.concat(this.contextMenuItems), i=0; i<all.length; i++) {
            if(this.actions.indexOf(all[i]) == -1) {
                this.actions.push(all[i]);
            }
        }
        
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
                remoteSort: true,
                sortInfo: this.defaultSortInfo,
                listeners: {
                    scope: this,
                    'update': this.onStoreUpdate,
                    'beforeload': this.onStoreBeforeload,
                    'load': this.onStoreLoad
                }
            });
        } else {
            this.store = new Tine.Tinebase.data.RecordStore({
                autoLoad: true,
                recordClass: this.recordClass
            });
        }
    },
    
    /**
     * init ext grid panel
     * @private
     */
    initGrid: function() {
        // init sel model
        this.selectionModel = new Tine.Tinebase.widgets.grid.FilterSelectionModel({
            store: this.store
        });
        this.selectionModel.on('selectionchange', function(sm) {
            Tine.widgets.actionUpdater(sm, this.actions, this.recordClass.getMeta('containerProperty'), !this.evalGrants);
            if (this.updateOnSelectionChange && this.detailsPanel) {
                this.detailsPanel.onDetailsUpdate(sm);
            }
        }, this);
        
        // we allways have a paging toolbar
        this.pagingToolbar = new Ext.ux.grid.PagingToolbar({
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: Tine.Tinebase.tranlation._('Displaying records {0} - {1} of {2}').replace(/records/, this.i18nRecordsName),
            emptyMsg: String.format(Tine.Tinebase.tranlation._("No {0} to display"), this.i18nRecordsName),
            displaySelectionHelper: true,
            sm: this.selectionModel
        });
        // mark next grid refresh as paging-refresh
        this.pagingToolbar.on('beforechange', function() {
            this.grid.getView().isPagingRefresh = true;
        }, this);
        this.pagingToolbar.on('render', function() {
            //Ext.fly(this.pagingToolbar.el.dom).createChild({cls:'x-tw-selection-info', html: '<b>100 Selected</b>'});
            //console.log('h9er');
            //this.pagingToolbar.addFill();
            //this.pagingToolbar.add('sometext');
        }, this);
        
        // init view
        var view =  new Ext.grid.GridView({
            getRowClass: this.getViewRowClass,
            autoFill: true,
            forceFit:true,
            ignoreAdd: true,
            emptyText: String.format(Tine.Tinebase.tranlation._("No {0} where found. Please try to change your filter-criteria, view-options or the {1} you search in."), this.i18nRecordsName, this.i18nContainersName),
            onLoad: Ext.emptyFn,
            listeners: {
                beforerefresh: function(v) {
                    v.scrollTop = v.scroller.dom.scrollTop;
                },
                refresh: function(v) {
                    // on paging-refreshes (prev/last...) we don't preserve the scroller state
                    if (v.isPagingRefresh) {
                        v.scrollToTop();
                        v.isPagingRefresh = false;
                    } else {
                        v.scroller.dom.scrollTop = v.scrollTop;
                    }
                }
            }
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
        this.grid.on('keydown',     this.onKeyDown,     this);
        this.grid.on('rowclick',    this.onRowClick,    this);
        this.grid.on('rowdblclick', this.onRowDblClick, this);
        
        this.grid.on('rowcontextmenu', function(grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if(!selModel.isSelected(row)) {
                // disable preview update if config option is set to false
                this.updateOnSelectionChange = this.updateDetailsPanelOnCtxMenu;
                selModel.selectRow(row);
            }
            
            this.contextMenu.showAt(e.getXY());
            // reset preview update
            this.updateOnSelectionChange = true;
        }, this);
        
    },
    
    loadData: function(preserveCursor/*, preserveSelection*/) {
        var opts = {};
        
        if (preserveCursor) {
            opts.params = {
                start: this.pagingToolbar.cursor
            };
        }
        
        this.store.load(opts);
    },
    
    /**
     * called when the store gets updated, e.g. from editgrid
     */
    onStoreUpdate: function(store, record, operation) {
        switch (operation) {
            case Ext.data.Record.EDIT:
                this.recordProxy.saveRecord(record, {
                    scope: this,
                    success: function(updatedRecord) {
                        store.commitChanges();
                        // update record in store to prevent concurrency problems
                        record.data = updatedRecord.data;
                        
                        // reloading the store feels like oldschool 1.x
                        // maybe we should reload if the sort critera changed, 
                        // but even this might be confusing
                        //store.load({});
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
        options.params = options.params || {};
        
        // allways start with an empty filter set!
        // this is important for paging and sort header!
        options.params.filter = [];
        
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
        if (this.store.getCount() > 0) {
            this.grid.getView().focusRow(0);
        }
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
                
            }
        } else {
            if (e.getKey() == e.DELETE) {
                if (!this.grid.editing && !this.grid.adding && !this.action_deleteRecord.isDisabled()) {
                    this.onDeleteRecords.call(this);
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
        if (button.actionType == 'edit') {
            if (! this.action_editInNewWindow || this.action_editInNewWindow.isDisabled()) {
                // if edit action is disabled or not available, we also don't open a new window
                return false;
            }
            var selectedRows = this.grid.getSelectionModel().getSelections();
            record = selectedRows[0];
        } else {
            record = new this.recordClass(this.recordClass.getDefaultData(), 0);
        }
        
        var popupWindow = Tine[this.app.appName][this.recordClass.getMeta('modelName') + 'EditDialog'].openWindow({
            record: record,
            listeners: {
                scope: this,
                'update': function(record) {
                    this.loadData(true);
                }
            }
        });
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
        
        var i18nItems    = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), records.length);
        var i18nQuestion = this.i18nDeleteQuestion ?
            this.app.i18n.n_hidden(this.i18nDeleteQuestion[0], this.i18nDeleteQuestion[1], records.length) :
            Tine.Tinebase.tranlation.ngettext('Do you really want to delete the selected record', 'Do you really want to delete the selected records', records.length);
            
        Ext.MessageBox.confirm(_('Confirm'), i18nQuestion, function(btn) {
            if(btn == 'yes') {
                if (this.recordProxy) {
                    if (this.showDeleteMask) {
                        if (! this.deleteMask) {
                            var message = String.format(_('Deleting {0}'), i18nItems);
                            if (sm.isFilterSelect) {
                                message = message + _(' ... This may take a long time!');
                            } 
                            this.deleteMask = new Ext.LoadMask(this.grid.getEl(), {msg: message});
                        }
                        this.deleteMask.show();
                    }
                    
                    var options = {
                        scope: this,
                        success: function() {
                            if (this.showDeleteMask) {
                                this.deleteMask.hide();
                            }
                            this.onAfterDelete();
                        },
                        failure: function () {
                            if (this.showDeleteMask) {
                                this.deleteMask.hide();
                            }
                            Ext.MessageBox.alert(_('Failed'), String.format(_('Could not delete {0}.'), i18nItems)); 
                        }
                    };
                    
                    if (sm.isFilterSelect && this.filterSelectionDelete) {
                        this.recordProxy.deleteRecordsByFilter(sm.getSelectionFilter(), options);
                    } else {
                        this.recordProxy.deleteRecords(records, options);
                    }
                } else {
                    Ext.each(records, function(record) {
                        this.store.remove(record);
                    });
                }
            }
        }, this);
    },
    
    /**
     * do something after deletion of records
     * - reload the store
     */
    onAfterDelete: function() {
        this.loadData(true);
    }
});
