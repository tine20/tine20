/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/ 

Ext.ns('Tine.Admin.container');

/**
 * Containers 'mainScreen'
 * 
 * @static
 */
Tine.Admin.container.show = function () {
    var app = Tine.Tinebase.appMgr.get('Admin');
    if (! Tine.Admin.container.gridPanel) {
        Tine.Admin.container.gridPanel = new Tine.Admin.container.GridPanel({
            app: app
        });
    }
    else {
    	Tine.Admin.container.gridPanel.loadGridData.defer(100, Tine.Admin.container.gridPanel, []);
    }
    
    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Admin.container.gridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Admin.container.gridPanel.actionToolbar, true);
};

/************** backend *****************/

Tine.Admin.containerBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'Container',
    recordClass: Tine.Tinebase.Model.Container,
    idProperty: 'id'
});
