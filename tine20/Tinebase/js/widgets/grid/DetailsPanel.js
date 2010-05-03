/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.Tinebase.widgets', 'Tine.widgets.grid');

/**
 * grid details panel
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.DetailsPanel
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
 * Create a new Tine.widgets.grid.DetailsPanel
 */
Tine.widgets.grid.DetailsPanel = Ext.extend(Ext.Panel, {
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
     * @property defaultInfosPanel holds panel for default information
     * @type Ext.Panel
     */
    defaultInfosPanel: null,
    
    /**
     * @property singleRecordPanel holds panel for single record details
     * @type Ext.Panel
     */
    singleRecordPanel: null,
    
    /**
     * @property multiRecordsPanel holds panel for multi selection aggregates/information
     * @type Ext.Panel
     */
    multiRecordsPanel: null,
            
    /**
     * @private
     */
    border: false,
    autoScroll: true,
    layout: 'card',
    activeItem: 0,
    
    /**
     * get panel for default information
     * 
     * @return {Ext.Panel}
     */
    getDefaultInfosPanel: function() {
        if (! this.defaultInfosPanel) {
            this.defaultInfosPanel = new Ext.Panel(this.defaults);
        }
        return this.defaultInfosPanel;
    },
    
    /**
     * get panel for single record details
     * 
     * @return {Ext.Panel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Ext.Panel(this.defaults);
        }
        return this.singleRecordPanel;
    },
    
    /**
     * get panel for multi selection aggregates/information
     * 
     * @return {Ext.Panel}
     */
    getMultiRecordsPanel: function() {
        if (! this.multiRecordsPanel) {
            this.multiRecordsPanel = new Ext.Panel(this.defaults);
        }
        return this.multiRecordsPanel;
    },
        
    /**
     * inits this details panel
     */
    initComponent: function() {
        this.defaults = this.defaults || {};
        
        Ext.applyIf(this.defaults, {
            border: false,
            autoScroll: true,
            layout: 'fit'
        });
        
        this.items = [
            this.getDefaultInfosPanel(),
            this.getSingleRecordPanel(),
            this.getMultiRecordsPanel()
        ];
        
        Tine.widgets.grid.DetailsPanel.superclass.initComponent.apply(this, arguments);
    },
    
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
            this.layout.setActiveItem(this.getDefaultInfosPanel());
            this.showDefault(this.getDefaultInfosPanel().body);
            this.record = null;
        } else if (count === 1) {
            this.layout.setActiveItem(this.getSingleRecordPanel());
            this.record = sm.getSelected();
            this.updateDetails(this.record, this.getSingleRecordPanel().body);
        } else if (count > 1) {
            this.layout.setActiveItem(this.getMultiRecordsPanel());
            this.record = sm.getSelected();
        	this.showMulti(sm, this.getMultiRecordsPanel().body);
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
