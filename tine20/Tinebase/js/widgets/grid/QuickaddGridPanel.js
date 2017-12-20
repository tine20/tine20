/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * quickadd grid panel
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.QuickaddGridPanel
 * @extends     Ext.ux.grid.QuickaddGridPanel
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.QuickaddGridPanel
 */
Tine.widgets.grid.QuickaddGridPanel = Ext.extend(Ext.ux.grid.QuickaddGridPanel, {
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     */
    recordClass: null,
    /**
     * @cfg {String} dataField
     * use this (single) field as data instead of whole record
     */
    dataField: null,
    /**
     * @cfg {Bool} useBBar
     */
    useBBar: false,
    
    /**
     * @private
     */
    clicksToEdit:'auto',
    frame: true,

    /**
     * @private
     */
    initComponent: function() {
        this.initGrid();
        this.initActions();
        
        this.on('rowcontextmenu', this.onRowContextMenu, this);
        if (! this.store) {
            // create basic store
            this.store = new Ext.data.Store({
                // explicitly create reader
                reader: new Ext.data.ArrayReader({
                        idIndex: 0  // id for each record will be the first element
                    },
                    this.recordClass
                )
            });
        }

        Tine.widgets.grid.QuickaddGridPanel.superclass.initComponent.call(this);
        
        this.on('newentry', this.onNewentry, this);
    },

    /**
     * init grid
     */
    initGrid: function() {
        this.enableHdMenu = false;
        this.plugins = this.plugins || [];
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        
        this.sm = new Ext.grid.RowSelectionModel();
        this.sm.on('selectionchange', function(sm) {
            var rowCount = sm.getCount();
            this.deleteAction.setDisabled(rowCount == 0);
        }, this);
        
        this.cm = (! this.cm) ? this.getColumnModel() : this.cm;
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.deleteAction = new Ext.Action({
            text: i18n._('Remove'),
            iconCls: 'actionDelete',
            handler : this.onDelete,
            scope: this,
            disabled: true
        });
        
        if (this.useBBar) {
            this.bbar = [this.deleteAction];
        }
    },
    
    /**
     * get column model
     * 
     * @return {Ext.grid.ColumnModel}
     */
    getColumnModel: function() {
        var _ = window.lodash,
            me = this;

        if (! this.cm) {
            if (this.columns) {
                // convert string cols
                _.each(me.columns, function(col, idx) {
                    if (_.isString(col)) {
                        var config = Tine.widgets.grid.ColumnManager.get(me.recordClass.getMeta('appName'), me.recordClass.getMeta('modelName'), col, 'editDialog');
                        if (config) {
                            me.columns[idx] = config;
                            _.each(['quickaddField', 'editor'], function(prop) {
                                config[prop] = Ext.ComponentMgr.create(Tine.widgets.form.FieldManager.getByModelConfig(
                                    me.recordClass.getMeta('appName'),
                                    me.recordClass.getMeta('modelName'),
                                    col,
                                    Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID
                                ));
                            });
                        }
                    }
                });
                _.remove(me.columns, _.isString)
            }

            this.cm = new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: this.columns || []
            });
        }

        return this.cm;
    },
    
    /**
     * new entry event -> add new record to store
     * 
     * @param {Object} recordData
     * @return {Boolean}
     */
    onNewentry: function(recordData) {
        var initialData = null;
        if (Ext.isFunction(this.recordClass.getDefaultData)) {
            initialData = Ext.apply(this.recordClass.getDefaultData(), recordData);
        } else {
            initialData = recordData;
        }
        var newRecord = new this.recordClass(initialData, Ext.id());
        this.store.insert(0 , [newRecord]);
        
        return true;
    },
    
    /**
     * delete event
     */
    onDelete: function() {
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            this.store.remove(selectedRows[i]);
        }
    },
    
    onRowContextMenu: function(grid, row, e) {
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
    },
    
    /**
     * creates and returns the context  menu
     * @return {Ext.menu.Menu}
     */
    getContextMenu: function() {
        if (! this.contextMenu) {
            var items = [this.deleteAction];

            // lookup additional items
            items = items.concat(this.getContextMenuItems());

            this.contextMenu = new Ext.menu.Menu({
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   'Tinebase-MainContextMenu'
                }],
                items: items
            });
        }

        if (this.readOnly) {
            this.deleteAction.setDisabled(true);
        }

        return this.contextMenu;
    },

    getContextMenuItems: function() {
        return [];
    },
    
    /**
     * get next available id
     * @return {Number}
     */
    getNextId: function() {
        var newid = this.store.getCount() + 1;
        
        while (this.store.getById(newid)) {
            newid++;
        }
        
        return newid;
    },

    /**
     * get values from store (as array)
     * 
     * @param {Array}
     * 
     * TODO improve this
     */
    setStoreFromArray: function(data) {
        this.store.clearData();

        for (var i = data.length-1; i >=0; --i) {
            var recordData = {}
            if (this.dataField === null) {
                recordData = data[i];
            } else {
                recordData[this.dataField] = data[i];
            }

            this.store.insert(0, new this.recordClass(recordData));
        }
    },
    
    /**
     * get values from store (as array)
     * 
     * @return {Array}
     */
    getFromStoreAsArray: function(deleteAutoIds) {
        var result = [];
        this.store.each(function(record) {
            var data = (this.dataField === null) ? record.data : record.get(this.dataField);
            if (deleteAutoIds && String(data.id).match(/ext-gen/)) {
                delete data.id;
            }
            result.push(data);
        }, this);

        return result;
    }
});
