/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.grid');

/**
 * @class Tine.widgets.grid.ExportButton
 * @extends Ext.Button
 * <p>export button</p>
 * @constructor
 */
Tine.widgets.grid.ExportButton = function(config) {
    config = config || {};
    Ext.apply(this, config);
    
    Tine.widgets.grid.ExportButton.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.grid.ExportButton, Ext.Button, {	
    /**
     * @cfg {String} icon class
     */
    iconCls: 'action_export',
    /**
     * @cfg {String} format of export (default: csv)
     */
    format: 'csv',
    /**
     * @cfg {String} export function (for example: Timetracker.exportTimesheets)
     */
    exportFunction: null,
    /**
     * @cfg {Object} the filter toolbar with filter settings
     */
    filterToolbar: null,
    
    /**
     * do export
     */
    doExport: function() {
    	var filterSettings = Ext.util.JSON.encode(this.filterToolbar.getValue());
    	Tine.Tinebase.common.openWindow('exportWindow', 'index.php?method=' + this.exportFunction + '&_format=' + this.format + '&_filter=' + filterSettings, 200, 150);
    },
    
    /**
     * @private
     * 
     * @todo add on click handler -> call export function with grid selected ids
     */
    handler: function() { 	
        this.doExport();
    }
});
