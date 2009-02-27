/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        include this file
 * @todo        add env check model (to Setup.js)
 * @todo        make it available from tree menu
 * @todo        add column model
 * @todo        fill store with check registry data
 * @todo        make it work!
 * 
 */
 
Ext.ns('Tine', 'Tine.Setup');

Tine.Setup.EnvCheckGridPanel = Ext.extend(Ext.Panel, {
	
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'name'
    },
	
    initComponent: function() {
    	
    	this.gridConfig.columns = this.getColumns();
    	
        // init store
        this.initStore();

        // init (ext) grid
        this.initGrid();
        
        //this.initLayout();

        Tine.Tinebase.widgets.app.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init store
     * @private
     */
    initStore: function() {
        this.store = new Ext.data.Store({
            fields: Tine.Setup.Model.EnvCheck,
            mode: 'local'
            /*
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
            */
        });
            
        this.store.loadData(Tine.Setup.registry.get('setupChecks').results);
    },

    /**
     * init ext grid panel
     * @private
     */
    initGrid: function() {
        // init sel model
        this.selectionModel = new Ext.grid.RowSelectionModel({
            store: this.store
        });
        /*
        this.selectionModel.on('selectionchange', function(sm) {
            Tine.widgets.actionUpdater(sm, this.actions, this.recordClass.getMeta('containerProperty'), !this.evalGrants);
            
        }, this);
        */
        
        // we allways have a paging toolbar
        /*
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
        */
        
        // init view
        var view =  new Ext.grid.GridView({
            autoFill: true,
            forceFit:true,
            ignoreAdd: true,
            //emptyText: String.format(Tine.Tinebase.tranlation._("No {0} where found. Please try to change your filter-criteria, view-options or the {1} you search in."), this.i18nRecordsName, this.i18nContainersName),
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
        
        // activate grid header menu for column selection
        /*
        this.gridConfig.plugins = this.gridConfig.plugins ? this.gridConfig.plugins : [];
        this.gridConfig.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        this.gridConfig.enableHdMenu = false;
        */
        
        this.grid = new Ext.grid.GridPanel(Ext.applyIf(this.gridConfig, {
            border: false,
            store: this.store,
            sm: this.selectionModel,
            view: view
        }));
        
        // init various grid / sm listeners
        /*
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
        */
    },
    
    getColumns: function() {
        return  [
            {id: 'key',             width: 400, sortable: true, dataIndex: 'key',             header: this.app.i18n._("Check")}, 
            {id: 'value',            width: 400, sortable: true, dataIndex: 'value',          header: this.app.i18n._("Result"), renderer: this.resultRenderer}
        ];
    },

    resultRenderer: function(value) {
        return Tine.Tinebase.common.booleanRenderer(value);
    }
    
    /*
    initActions: function() {
        this.action_installApplications = new Ext.Action({
            text: this.app.i18n._('Install application'),
            handler: this.onAlterApplications,
            actionType: 'install',
            iconCls: 'setup_action_install',
            disabled: true,
            scope: this
        });
        
        this.action_uninstallApplications = new Ext.Action({
            text: this.app.i18n._('Uninstall application'),
            handler: this.onAlterApplications,
            actionType: 'uninstall',
            iconCls: 'setup_action_uninstall',
            disabled: true,
            scope: this
        });
        
        this.action_updateApplications = new Ext.Action({
            text: this.app.i18n._('Update application'),
            handler: this.onAlterApplications,
            actionType: 'update',
            iconCls: 'setup_action_update',
            disabled: true,
            scope: this
        });
        
        this.actions = [
            this.action_installApplications,
            this.action_uninstallApplications,
            this.action_updateApplications
        ];
        
        this.actionToolbar = new Ext.Toolbar({
            split: false,
            height: 26,
            items: this.actions.concat(this.actionToolbarItems)
        });
        
        this.contextMenu = new Ext.menu.Menu({
            items: this.actions.concat(this.contextMenuItems)
        });
        
    },
    
    initGrid: function() {
        Tine.Setup.GridPanel.superclass.initGrid.call(this);
        this.selectionModel.purgeListeners();
        
        this.selectionModel.on('selectionchange', this.onSelectionChange, this);
        
    },
    
    onSelectionChange: function(sm) {
        var apps = sm.getSelections();
        var disabled = sm.getCount() == 0;
        
        var nIn = disabled, nUp = disabled, nUn = disabled;
        
        for(var i=0; i<apps.length; i++) {
            var status = apps[i].get('install_status');
            nIn = nIn || status == 'uptodate' || status == 'updateable';
            nUp = nUp || status == 'uptodate' || status == 'uninstalled';
            nUn = nUn || status == 'uninstalled';
        }
        
        this.action_installApplications.setDisabled(nIn);
        this.action_uninstallApplications.setDisabled(nUn);
        this.action_updateApplications.setDisabled(nUp);
    },
    
    onAlterApplications: function(btn, e) {
        var appNames = [];
        var apps = this.selectionModel.getSelections();
        
        for(var i=0; i<apps.length; i++) {
            appNames.push(apps[i].get('name'));
        }
        
        Ext.Ajax.request({
            scope: this,
            params: {
                method: 'Setup.' + btn.actionType + 'Applications',
                applicationNames: Ext.util.JSON.encode(appNames)
            },
            success: function() {
                this.store.load();
            },
            fail: function() {
                Ext.Msg.alert(this.app.i18n._('Shit'), this.app.i18n._('Where are the backup tapes'));
            }
        });
    },
    
    upgradeStatusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    }
    */
});