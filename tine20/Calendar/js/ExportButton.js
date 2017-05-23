/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Calendar');

Tine.Calendar.ExportButton = Ext.extend(Tine.widgets.grid.ExportButton, {

    doExport: function() {
        var app = Tine.Tinebase.appMgr.get('Calendar'),
            mainScreen = app.getMainScreen(),
            centerPanel = mainScreen.getCenterPanel(),
            exportJob = new Tine.Tinebase.Model.ExportJob({
                scope: this.exportScope,
                filter: centerPanel.getAllFilterData({
                    noPeriodFilter: false
                }),
                format: this.format,
                exportFunction: this.exportFunction,
                recordsName: Tine.Calendar.Model.Event.getRecordsName(),
                count: '',
                model: 'Calendar_Model_Event',
                options: {
                    format: this.format,
                    definitionId: this.definitionId
                }
            });

        if (this.showExportDialog) {
            Tine.widgets.dialog.ExportDialog.openWindow({
                appName: 'Calendar',
                record: exportJob
            });
        } else {
            Tine.widgets.exportAction.downloadExport(exportJob);
        }
    }

});