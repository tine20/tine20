/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine', 'Tine.Setup');

/**
 * Environment Check Grid Panel
 * 
 * @namespace   Tine.Setup
 * @class       Tine.Setup.EnvCheckGridPanel
 * @extends     Ext.Panel
 * 
 * <p>Environment Check Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.EnvCheckGridPanel
 */
 Tine.Setup.EnvCheckGridPanel = Ext.extend(Ext.Panel, {
	
    /**
     * @property actionToolbar
     * @type Ext.Toolbar
     */
    actionToolbar: null,
    
    /**
     * @property contextMenu
     * @type Ext.Menu
     */
    contextMenu: null,
    
    /**
     * @private
     */
    layout: 'border',
    border: false,
	
    /**
     * 
     * @cfg grid config 
     */
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'key'
    },
	
    /**
     * init component
     */
    initComponent: function() {
    	
    	this.gridConfig.columns = this.getColumns();
    	
        this.initActions();
        this.initStore();
        this.initGrid();        
        this.initLayout();

        Tine.Setup.EnvCheckGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init store
     * @private
     */
    initStore: function() {
    	this.store = new Ext.data.JsonStore({
            fields: Tine.Setup.Model.EnvCheck,
            mode: 'local',
            id: 'key',
            remoteSort: false
        });
        
        this.store.on('beforeload', function() {
            if (! this.loadMask) {
                this.loadMask = new Ext.LoadMask(this.el, {msg: this.app.i18n._("Performing Environment Checks...")});
            }
            
            this.loadMask.show();
            
            Ext.Ajax.request({
                params: {
                    method: 'Setup.envCheck'
                },
                scope: this,
                success: function(response) {
                    var data = Ext.util.JSON.decode(response.responseText);
                    Tine.Setup.registry.replace('setupChecks', data);
                    
                    this.store.loadData(data.results);
                    this.loadMask.hide();
                }
            })
            
            return false;
        }, this);
        
        var checkData = Tine.Setup.registry.get('setupChecks').results;
        this.store.loadData(checkData);
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
        
        // init view
        var view =  new Ext.grid.GridView({
            autoFill: true,
            forceFit:true,
            ignoreAdd: true
            //emptyText: String.format(Tine.Tinebase.tranlation._("No {0} where found. Please try to change your filter-criteria, view-options or the {1} you search in."), this.i18nRecordsName, this.i18nContainersName),
            /*
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
            */
        });
        
        this.grid = new Ext.grid.GridPanel(Ext.applyIf(this.gridConfig, {
            border: false,
            store: this.store,
            sm: this.selectionModel,
            view: view
        }));
    },
    
    getColumns: function() {
        return  [
            {id: 'key',   width: 150, sortable: true, dataIndex: 'key',   header: this.app.i18n._("Check")}, 
            {id: 'value', width: 50, sortable: true, dataIndex: 'value', header: this.app.i18n._("Result"), renderer: this.resultRenderer},
            {id: 'message', width: 600, sortable: true, dataIndex: 'message', header: this.app.i18n._("Error"), renderer: this.messageRenderer}
        ];
    },

    resultRenderer: function(value) {
    	var icon = (value) ? 'images/oxygen/16x16/actions/dialog-apply.png' : 'images/oxygen/16x16/actions/dialog-cancel.png';
        return '<img class="TasksMainGridStatus" src="' + icon + '">';
    },
    
    messageRenderer: function(value) {
    	// overwrite the default renderer to show links correctly
        return value;
    },
    
    initActions: function() {
    	// @todo add re-run checks here
    	
        this.action_reCheck = new Ext.Action({
            text: this.app.i18n._('Run setup tests'),
            handler: function() {
                this.store.load({});
            },
            iconCls: 'x-tbar-loading',
            scope: this
        });
        
        this.action_ignoreTests = new Ext.Action({
            text: this.app.i18n._('Ignore setup tests'),
            scope: this,
            handler: function() {
                var checks = Tine.Setup.registry.get('setupChecks');
                checks.success = true;
                Tine.Setup.registry.replace('setupChecks', checks);
                Tine.Setup.registry.replace('checkDB', true);
            }
        });
    	/*
        this.action_installApplications = new Ext.Action({
            text: this.app.i18n._('Install application'),
            handler: this.onAlterApplications,
            actionType: 'install',
            iconCls: 'setup_action_install',
            disabled: true,
            scope: this
        });
        
        this.actions = [
            this.action_installApplications,
        ];
        */
        
        this.actionToolbar = new Ext.Toolbar({
            items: [
                this.action_reCheck,
                this.action_ignoreTests
            ]
        });
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
            items: this.grid
        }];
    }
});
