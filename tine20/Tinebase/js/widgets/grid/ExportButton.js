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
     * @cfg {String} application tree id
     */
    appTreeId: null,
    
    /**
     * do export
     */
    doExport: function() {    	
    	var filterSettings = this.filterToolbar.getValue();
        
        // add container to filter
    	if (this.appTreeId) {
            var nodeAttributes = Ext.getCmp(this.appTreeId).getSelectionModel().getSelectedNode().attributes || {};
            filterSettings.push(
                {field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                {field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                {field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
            );
    	}

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
