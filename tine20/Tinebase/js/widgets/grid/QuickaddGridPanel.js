/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: DetailsPanel.js 10291 2009-09-02 14:08:36Z p.schuele@metaways.de $
 */
Ext.ns('Tine.widgets.grid');

/**
 * quickadd grid panel
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.QuickaddGridPanel
 * @extends     Ext.ux.grid.QuickaddGridPanel
 * 
 * <p>Grid Details Panel</p>
 * <p>
 * Details Panel with toolbar menu
 * <pre>
 * TODO         add ctx menu
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: DetailsPanel.js 10291 2009-09-02 14:08:36Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.QuickaddGridPanel
 */
Tine.widgets.grid.QuickaddGridPanel = Ext.extend(Ext.ux.grid.QuickaddGridPanel, {
    /**
     * @property recordClass
     */
    recordClass: null,
    dataField: null,
    
    /**
     * useBBar 
     * @config Boolean
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
        
        this.sm = new Ext.grid.RowSelectionModel({multiSelect:true});
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
            text: _('Remove'),
            iconCls: 'actionDelete',
            handler : this.onDelete,
            scope: this,
            disabled: true
        });
        
        if (this.useBBar) {
            this.bbar = [this.deleteAction];
        } else {
            this.tbar = [this.deleteAction];
        }
    },
    
    /**
     * get column model
     * 
     * @return {Ext.grid.ColumnModel}
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel([]);
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
        var newRecord = new this.recordClass(initialData);
        this.store.insert(0 , newRecord);
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
        for (var i = 0; i < data.length; ++i) {
            if (this.dataField === null) {
                var recordData = data[i];
            } else {
                var recordData = {};
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
    getFromStoreAsArray: function() {
        var result = [];
        this.store.each(function(record) {                     
            result.push((this.dataField === null) ? record.data : record.get(this.dataField));
        }, this);
        //store.commitChanges();
        
        return result;
    }
});
