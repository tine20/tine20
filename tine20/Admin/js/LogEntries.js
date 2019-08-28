/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Admin.LogEntries');

/**
 * LogEntries 'mainScreen' (Admin grid panel)
 *
 * @static
 */
Tine.Admin.LogEntries.show = function () {
    if (! Tine.Admin.logentriesGridPanel) {
        Tine.Admin.logentriesBackend = new Tine.Tinebase.data.RecordProxy({
            appName: 'Admin',
            modelName: 'LogEntry',
            recordClass: Tine.Tinebase.Model.LogEntry,
            idProperty: 'id'
        });
        Tine.Admin.logentriesGridPanel = new Tine.Tinebase.LogEntryGridPanel({
            recordProxy: Tine.Admin.logentriesBackend,
            asAdminModule: true
        });
    } else {
        Tine.Admin.logentriesGridPanel.loadGridData.defer(100, Tine.Admin.logentriesGridPanel, []);
    }

    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Admin.logentriesGridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Admin.logentriesGridPanel.actionToolbar, true);
};
