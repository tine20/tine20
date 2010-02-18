/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */

Ext.namespace('Tine.widgets.grid');

/**
 * Picker GridPanel
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.PickerGridPanel
 * @extends     Ext.grid.GridPanel
 * 
 * <p>Picker GridPanel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.PickerGridPanel
 */
Tine.widgets.grid.PickerGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    /**
     * @cfg {bool}
     * enable bottom toolbar
     */
    enableBbar: true,

    /**
     * store to hold records
     * 
     * @type Ext.data.Store
     * @property store
     */
    store: null,
    
    /**
     * record class
     * @cfg {} recordClass
     */
    recordClass: null,
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,
    
    /**
     * @cfg {Array} contextMenuItems
     * additional items for contextMenu
     */
    contextMenuItems: null,
    
    /**
     * @cfg {Array} Array of column's config objects where the config options are in
     */
    configColumns: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.contextMenuItems = (this.contextMenuItems !== null) ? this.contextMenuItems : [];
        this.configColumns = (this.configColumns !== null) ? this.configColumns : [];
        
        this.initStore();
        this.initActionsAndToolbars();
        this.initGrid();
        
        Tine.widgets.grid.PickerGridPanel.superclass.initComponent.call(this);
    },

    /**
     * init store
     * @private
     */
    initStore: function() {
        
        if (this.store === null) {
            this.store = new Ext.data.SimpleStore({
                fields: this.recordClass
            });
        }
        
        // focus+select new record
        this.store.on('add', function(store, records, index) {
            (function() {
                if (this.rendered) {
                    this.getView().focusRow(index);
                    this.getSelectionModel().selectRow(index); 
                }
            }).defer(300, this);
        }, this);
    },

    /**
     * init actions and toolbars
     */
    initActionsAndToolbars: function() {
        
        this.actionRemove = new Ext.Action({
            text: _('Remove record'),
            disabled: true,
            scope: this,
            handler: this.onRemove,
            iconCls: 'action_deleteContact'
        })
        
        var contextItems = [this.actionRemove];
        this.contextMenu = new Ext.menu.Menu({
            items: contextItems.concat(this.contextMenuItems)
        });
        
        if (this.enableBbar) {
            this.bbar = new Ext.Toolbar({
                items: [
                    this.actionRemove
                ]
            });
        }
    },
    
    /**
     * init grid (column/selection model, ctx menu, ...)
     */
    initGrid: function() {
        this.cm = this.getColumnModel();
        
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        // remove non-plugin config columns
        var nonPluginColumns = [];
        for (var i=0; i < this.configColumns.length; i++) {
            if (!this.configColumns[i].init || typeof(this.configColumns[i].init) != 'function') {
                nonPluginColumns.push(this.configColumns[i]);
            }
        }
        for (var i=0; i < nonPluginColumns.length; i++) {
            this.configColumns.remove(nonPluginColumns[i]);
        }
        this.plugins = this.configColumns;
    
        // on selectionchange handler
        this.selModel.on('selectionchange', function(sm) {
            var rowCount = sm.getCount();
            this.actionRemove.setDisabled(rowCount == 0);
        }, this);
        
        // on rowcontextmenu handler
        this.on('rowcontextmenu', function(grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if(!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            
            this.contextMenu.showAt(e.getXY());
        }, this);
    },
    
    /**
     * remove handler
     * 
     * @param {} button
     * @param {} event
     */
    onRemove: function(button, event) {                       
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            this.store.remove(selectedRows[i]);
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
                    // select all records
                    this.getSelectionModel().selectAll(true);
                    e.preventDefault();
                    break;
            }
        } else {
            switch (e.getKey()) {
                case e.DELETE:
                    // delete selected record(s)
                    this.onRemove();
                    break;
            }
        }
    }
});

