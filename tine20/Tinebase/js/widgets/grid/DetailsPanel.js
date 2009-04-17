/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.grid');

/**
 * details panel
 * 
 * @class Tine.widgets.grid.DetailsPanel
 * @extends Ext.Panel
 */
Tine.widgets.grid.DetailsPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Number}
     * default Heights
     */
    defaultHeight: 125,
    
    border: false,
    autoScroll: true,
    layout: 'fit',
    
    updateDetails: function(record, body) {
        this.tpl.overwrite(body, record.data);
    },
    
    showDefault: function(body) {
        if (this.defaultTpl) {
            this.defaultTpl.overwrite(body);
        }
    },
    
    showMulti: function(sm, body) {
        if (this.multiTpl) {
            this.multiTpl.overwrite(body);
        }
    },
    
    /**
     * 
     * @param grid
     */
    doBind: function(grid) {
        grid.getSelectionModel().on('selectionchange', function(sm) {
            this.onDetailsUpdate(sm);
        }, this);
        
        grid.store.on('load', function(store) {
            this.onDetailsUpdate(grid.getSelectionModel());
        }, this);
    },
    
    /**
     * 
     * @param sm selection model
     */
    onDetailsUpdate: function(sm) {
        var count = sm.getCount();
        if (count === 0 || sm.isFilterSelect) {
            this.showDefault(this.body);
        } else if (count === 1) {
            var record = sm.getSelected();
            this.updateDetails(record, this.body);
        } else if (count > 1) {
        	this.showMulti(sm, this.body);
        }
    },
    
    getLoadMask: function() {
        if (! this.loadMask) {
            this.loadMask = new Ext.LoadMask(this.el);
        }
        
        return this.loadMask;
    }
    
});