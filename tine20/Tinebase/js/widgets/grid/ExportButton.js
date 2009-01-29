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
     * @cfg {Tine.Tinebase.widgets.grid.FilterSelectionModel} sm
     */
    sm: null,
    /**
     * @cfg {Tine.Tinebase.widgets.app.GridPanel} gridPanel
     * use this alternativly to sm
     */
    gridPanel: null,
    
    /**
     * do export
     */
    doExport: function() {
        // get selection model
        if (!this.sm) {
            this.sm = this.gridPanel.grid.getSelectionModel();
        }
        
        // temporary select all
        this.sm.selectAll();
        
    	var filterSettings = this.sm.getSelectionFilter();

        //console.log(filterSettings);
    	//Tine.Tinebase.common.openWindow('exportWindow', 'index.php?method=' + 
        //    this.exportFunction + '&_format=' + this.format + '&_filter=' + Ext.util.JSON.encode(filterSettings), 200, 150);
    	
        var form = Ext.getBody().createChild({
            tag:'form',
            method:'post',
            cls:'x-hidden'
        });
        
        Ext.Ajax.request({
            isUpload: true,
            form: form,
            // @todo replace icon with loading icon ...
            /*
            beforerequest: function() {
            	// replace icon
            	this.iconCls: 
            },
            */
            params: {
                method: this.exportFunction,
                requestType: 'HTTP',
                _filter: Ext.util.JSON.encode(filterSettings),
                _format: this.format
            },
            success: function() {
                form.remove();
            },
            failure: function() {
                form.remove();
            }
        });
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
