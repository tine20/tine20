/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2-14 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Calendar');


Tine.Calendar.ExportButton = Ext.extend(Tine.widgets.grid.ExportButton, {

    doExport: function() {
        var app = Tine.Tinebase.appMgr.get('Calendar'),
            mainScreen = app.getMainScreen(),
            centerPanel = mainScreen.getCenterPanel(),
            filterData = centerPanel.getAllFilterData({
                noPeriodFilter: false
            });

        if (this.showExportDialog) {
            Tine.widgets.dialog.ExportDialog.openWindow({
                appName: 'Calendar',
                record: new Tine.Tinebase.Model.ExportJob({
                    filter: filterData,
                    format: this.format,
                    exportFunction: this.exportFunction,
                    recordsName: Tine.Calendar.Model.Event.getRecordsName(),
                    count: '',
                    model: 'Calendar_Model_Event'
                })
            });
        } else {
            this.startDownload(filterData);
        }
    }

});