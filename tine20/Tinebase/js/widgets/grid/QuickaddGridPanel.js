/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: DetailsPanel.js 10291 2009-09-02 14:08:36Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Tinebase.widgets', 'Tine.Tinebase.widgets.grid');

/**
 * quickadd grid panel
 * 
 * @namespace   Tine.Tinebase.widgets.grid
 * @class       Tine.Tinebase.widgets.grid.QuickaddGridPanel
 * @extends     Ext.ux.grid.QuickaddGridPanel
 * 
 * <p>Grid Details Panel</p>
 * <p>
 * Details Panel with toolbar/ctx menu
 * <pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: DetailsPanel.js 10291 2009-09-02 14:08:36Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.widgets.grid.QuickaddGridPanel
 */
Tine.Tinebase.widgets.grid.QuickaddGridPanel = Ext.extend(Ext.ux.grid.QuickaddGridPanel, {
    /**
     * @property recordClass
     */
    recordClass: null,
    
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

        Tine.Tinebase.widgets.grid.QuickaddGridPanel.superclass.initComponent.call(this);
        
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
        
        this.tbar = [this.deleteAction];        
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
     * new entry event
     * 
     * @param {Object} recordData
     * @return {Boolean}
     */
    onNewentry: function(recordData) {
        // add new option to store
        recordData.id = this.getNextId();
        var newOption = new this.recordClass(recordData);
        this.store.insert(0,newOption);
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
    }    
});
