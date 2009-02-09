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
    region: 'south',
    border: false,
    collapsible:true,
    collapseMode: 'mini',
    split: true,
    layout: 'fit',
    height: 125,
    
    /*
    initComponent: function() {
        this.items = [{
            tpl: this.tplMarkup
        }];
        Tine.widgets.grid.DetailsPanel.superclass.initComponent.call(this);
    }
    */
    /*
    onRender: function(ct, position) {
        Tine.widgets.grid.DetailsPanel.superclass.onRender.call(this, ct, position);
        this.showDefault(this.body);
    },
    */
    
    
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
    }
    
});