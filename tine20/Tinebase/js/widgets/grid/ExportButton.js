/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.ExportButton
 * @extends     Ext.Button
 * <p>export button</p>
 * @constructor
 */
Tine.widgets.grid.ExportButton = function(config) {
    config = config || {};
    Ext.apply(this, config);
    config.handler = this.doExport.createDelegate(this);
    
    Tine.widgets.grid.ExportButton.superclass.constructor.call(this, config);
};

Ext.extend(Tine.widgets.grid.ExportButton, Ext.Action, {
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
     * @cfg {Tine.widgets.grid.FilterSelectionModel} sm
     */
    sm: null,
    /**
     * @cfg {Tine.widgets.grid.GridPanel} gridPanel
     * use this alternativly to sm
     */
    gridPanel: null,
    /**
     * @cfg {Boolean} showExportDialog
     */
    showExportDialog: false,
    
    /**
     * do export
     */
    doExport: function() {
        // get selection model
        if (!this.sm) {
            this.sm = this.gridPanel.grid.getSelectionModel();
        }
        
        // return if no rows are selected
        if (this.sm.getCount() === 0) {
            return false;
        }
        
        var filterSettings = this.sm.getSelectionFilter();
        
        if (this.showExportDialog) {
            var gridRecordClass = this.gridPanel.recordClass,
                model = gridRecordClass.getMeta('appName') + '_Model_' + gridRecordClass.getMeta('modelName');
                
            Tine.widgets.dialog.ExportDialog.openWindow({
                appName: this.gridPanel.app.appName,
                record: new Tine.Tinebase.Model.ExportJob({
                    filter: filterSettings,
                    format: this.format,
                    exportFunction: this.exportFunction,
                    count: this.sm.getCount(),
                    recordsName: this.gridPanel.i18nRecordsName,
                    model: model
                })
            });
        } else {
            this.startDownload(filterSettings);
        }
    },
    
    /**
     * start download
     * 
     * @param {Object} filterSettings
     */
    startDownload: function(filterSettings) {
        var downloader = new Ext.ux.file.Download({
            params: {
                method: this.exportFunction,
                requestType: 'HTTP',
                filter: Ext.util.JSON.encode(filterSettings),
                options: Ext.util.JSON.encode({
                    format: this.format
                })
            }
        }).start();
    }
});

