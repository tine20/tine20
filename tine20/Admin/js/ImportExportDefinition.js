require('./ImportExportDefinitionEditDialog');

Ext.ns('Tine.Admin.importexportdefinitions');
/**
 * importexportdefinitions 'mainScreen' (Admin grid panel)
 *
 * @static
 */
Tine.Admin.importexportdefinitions.show = function () {
    var app = Tine.Tinebase.appMgr.get('Tinebase');
    if (! Tine.Admin.importexportdefinitionsGridPanel) {
        Tine.Admin.importexportdefinitionsBackend = new Tine.Tinebase.data.RecordProxy({
            appName: 'Admin',
            modelName: 'ImportExportDefinition',
            recordClass: Tine.Tinebase.Model.ImportExportDefinition,
            idProperty: 'id'
        });
        Tine.Admin.importexportdefinitionsGridPanel = new Tine.Tinebase.ImportExportDefinitionGridPanel({
            recordProxy: Tine.Admin.importexportdefinitionsBackend,
            asAdminModule: true
        });
    } else {
        Tine.Admin.importexportdefinitionsGridPanel.loadGridData.defer(100, Tine.Admin.importexportdefinitionsGridPanel, []);
    }

    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Admin.importexportdefinitionsGridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Admin.importexportdefinitionsGridPanel.actionToolbar, true);
};

Tine.widgets.grid.RendererManager.register('Tinebase', 'ImportExportDefinition', 'application_id', function(v) {
    if (v && v.hasOwnProperty('name')) {
        this.translation = new Locale.Gettext();
        this.translation.textdomain(v.name);
        return this.translation.gettext(v.name);
    }
    return '';
});