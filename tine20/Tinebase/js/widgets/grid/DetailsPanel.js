/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tinebase.widgets', 'Tine.Tinebase.widgets.grid');

/**
 * grid details panel
 * 
 * @namespace   Tine.Tinebase.widgets.grid
 * @class       Tine.Tinebase.widgets.grid.DetailsPanel
 * @extends     Ext.Panel
 * 
 * <p>Grid Details Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.widgets.grid.DetailsPanel
 */
Tine.Tinebase.widgets.grid.DetailsPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Number} defaultHeight
     * default Heights
     */
    defaultHeight: 125,
    
    /**
     * @property grid
     * @type Tine.widgets.grid.GridPanel
     */
    grid: null,

    /**
     * @property record
     * @type Tine.Tinebase.data.Record
     */
    record: null,
    
    /**
     * @private
     */
    border: false,
    autoScroll: true,
    layout: 'fit',
    
    /**
     * update template
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.tpl.overwrite(body, record.data);
    },
    
    /**
     * show default template
     * 
     * @param {Mixed} body
     */
    showDefault: function(body) {
        if (this.defaultTpl) {
            this.defaultTpl.overwrite(body);
        }
    },
    
    /**
     * show template for multiple rows
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     * @param {Mixed} body
     */
    showMulti: function(sm, body) {
        if (this.multiTpl) {
            this.multiTpl.overwrite(body);
        }
    },
    
    /**
     * bind grid to details panel
     * 
     * @param {Tine.widgets.grid.GridPanel} grid
     */
    doBind: function(grid) {
        this.grid = grid;
        
        /*
        grid.getSelectionModel().on('selectionchange', function(sm) {
            if (this.updateOnSelectionChange) {
                this.onDetailsUpdate(sm);
            }
        }, this);
        */
        
        grid.store.on('load', function(store) {
            this.onDetailsUpdate(grid.getSelectionModel());
        }, this);
    },
    
    /**
     * update details panel
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     */
    onDetailsUpdate: function(sm) {
        var count = sm.getCount();
        if (count === 0 || sm.isFilterSelect) {
            this.showDefault(this.body);
            this.record = null;
        } else if (count === 1) {
            this.record = sm.getSelected();
            this.updateDetails(this.record, this.body);
        } else if (count > 1) {
            this.record = sm.getSelected();
        	this.showMulti(sm, this.body);
        }
    },
    
    /**
     * get load mask
     * 
     * @return {Ext.LoadMask}
     */
    getLoadMask: function() {
        if (! this.loadMask) {
            this.loadMask = new Ext.LoadMask(this.el);
        }
        
        return this.loadMask;
    }
});
