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

Tine.Tinebase.widgets.app.GridPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    /**
     * @cfg {Object} gridConfig
     * Config object for the Ext.grid.GridPanel
     */
    gridConfig: {},
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
    actionToolbarItems: [],
    /**
     * @cfg {Tine.widgets.grid.FilterToolbar} filterToolbar
     */
    filterToolbar: null,
    /**
     * @cfg {Array} contextMenuItems
     * additional items for contextMenu
     */
    contextMenuItems: [],
    /**
     * @cfg {Bool} evalGrants
     * should grants of a grant-aware records be evaluated (defaults to true)
     */
    evalGrants: true,
    /**
     * @cfg {Object} defaultSortInfo
     */
    defaultSortInfo: {},
    /**
     * @cfg {Object } defaultPaging 
     */
    defaultPaging: {
        start: 0,
        limit: 50
    },
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
     * spechialised strings for edit action button
     */
    i18nEditActionText: null,
    /**
     * @cfg {Array} i18nDeleteRecordAction 
     * spechialised strings for delete action button
     */
    i18nDeleteActionText: null,
    
    /**
     * @property {Ext.Tollbar} actionToolbar
     */
    actionToolbar: null,
    /**
     * @property {Ext.Menu} contextMenu
     */
    contextMenu: null,
    
    
    /**
     * @private
     */
    layout: 'border',
    border: false,
    
    /**
     * extend standart initComponent chain
     * @private
     */
    initComponent: function(){
        // init some translations
        this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
        this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
        this.i18nContainerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
        this.i18nContainersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        
        // init actions with actionToolbar and contextMenu
        this.initActions();
        // init store
        this.initStore();
        // init (ext) grid
        this.initGrid();
        
        this.initLayout();

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
            this.filterToolbar.on('bodyresize', function(ftb, w, h) {
                if (this.filterToolbar.rendered && this.layout.rendered) {
                    this.layout.layout();
                }
            }, this);
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
            singularText: this.i18nDeleteActionText ? i18nDeleteActionText[0] : String.format(Tine.Tinebase.tranlation.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            pluralText: this.i18nDeleteActionText ? i18nDeleteActionText[1] : String.format(Tine.Tinebase.tranlation.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordsName),
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
        
        this.actionToolbar = new Ext.Toolbar({
            split: false,
            height: 26,
            items: this.actions.concat(this.actionToolbarItems)
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
                    // on paging-refreshes (prev/last...) we don't preserv the scroller state
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
        
        this.grid = new Grid(Ext.applyIf(this.gridConfig, {
            border: false,
            store: this.store,
            sm: this.selectionModel,
            view: view
        }));
        
        // init various grid / sm listeners
        this.grid.on('keydown', this.onKeyDown, this);
        this.grid.on('rowclick',  this.onRowClick, this);
        
        this.grid.on('rowdblclick', function(grid, row, e){
            this.onEditInNewWindow.call(this, {actionType: 'edit'});
        }, this);
        
        this.grid.on('rowcontextmenu', function(grid, row, e) {
            e.stopEvent();
            if(!grid.getSelectionModel().isSelected(row)) {
                grid.getSelectionModel().selectRow(row);
            }
            
            this.contextMenu.showAt(e.getXY());
        }, this);
        
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
                    if (!this.action_editInNewWindow.isDisabled()) {
                        this.onEditInNewWindow.call(this, {
                            actionType: 'edit'
                        });
                        e.preventDefault();
                    }
                    break;
                case e.N:
                    if (!this.action_addInNewWindow.isDisabled()) {
                        this.onEditInNewWindow.call(this, {
                            actionType: 'add'
                        });
                        e.preventDefault();
                    }
                    break;
                
            }
        } else {
            switch (e.getKey()) {
                case e.DELETE:
                    if (!this.grid.editing && !this.grid.adding && !this.action_deleteRecord.isDisabled()) {
                        this.onDeleteRecords.call(this);
                    }
                    break;
            }
        }
    },
    
    /**
     * row click handler
     */
    onRowClick: function(grid, row, e) {
        /* @todo check if we need this in IE
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
            sm.clearSelections();
            sm.selectRow(row, false);
            grid.view.focusRow(row);
        }
    },
    
    /**
     * generic edit in new window handler
     */
    onEditInNewWindow: function(button, event) {
        var record; 
        if (button.actionType == 'edit') {
            if (this.action_editInNewWindow.isDisabled()) {
                // if edit action is disabled, we also don't open a new 
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
                    this.store.load({});
                }
            }
        });
    },
    
    /**
     * generic delete handler
     */
    onDeleteRecords: function(btn, e) {
        if (this.grid.getSelectionModel().isFilterSelect) {
            Ext.MessageBox.show({
                title: _('Not Allowed'), 
                msg: _('You are not allowed to delete all pages at once'),
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.INFO
            });
            
            return;
        }
        var records = this.grid.getSelectionModel().getSelections();
        
        var i18nItems    = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), records.length);
        var i18nQuestion = this.i18nDeleteQuestion ?
            this.app.i18n.n_hidden(this.i18nDeleteQuestion[0], this.i18nDeleteQuestion[1], records.length) :
            Tine.Tinebase.tranlation.ngettext('Do you really want to delete the selected record', 'Do you really want to delete the selected records', records.length);
            
        Ext.MessageBox.confirm(_('Confirm'), i18nQuestion, function(btn) {
            if(btn == 'yes') {
                if (! this.deleteMask) {
                    this.deleteMask = new Ext.LoadMask(this.grid.getEl(), {msg: String.format(_('Deleting {0}'), i18nItems)});
                }
                this.deleteMask.show();
                
                this.recordProxy.deleteRecords(records, {
                    scope: this,
                    success: function() {
                        this.deleteMask.hide();
                        this.store.load({});
                    },
                    failure: function () {
                        this.deleteMask.hide();
                        Ext.MessageBox.alert(_('Failed'), String.format(_('Could not delete {0}.'), i18nItems)); 
                    }
                });
            }
        }, this);
    }
});
