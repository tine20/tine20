/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/ 

Ext.ns('Tine.Admin.customfield');

/**
 * Customfields 'mainScreen'
 * 
 * @static
 */
Tine.Admin.customfield.show = function () {
    var app = Tine.Tinebase.appMgr.get('Admin');
    if (! Tine.Admin.customfield.gridPanel) {
        Tine.Admin.customfield.gridPanel = new Tine.Admin.customfield.GridPanel({
            app: app
        });
    }
    else {
        Tine.Admin.customfield.gridPanel.loadGridData.defer(100, Tine.Admin.customfield.gridPanel, []);
    }
    
    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Admin.customfield.gridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Admin.customfield.gridPanel.actionToolbar, true);
};

/************** models *****************/
Ext.ns('Tine.Admin.Model');

/**
 * Model of a customfield
 */
Tine.Admin.Model.Customfield = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.Customfield.prototype.fields.items, {
    appName: 'Admin',
    modelName: 'Customfield',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Customfield', 'Customfields', n);
    recordName: 'Customfield',
    recordsName: 'Customfields'
});

/************** backend *****************/

Tine.Admin.customfieldBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'Customfield',
    recordClass: Tine.Admin.Model.Customfield,
    idProperty: 'id'
});
