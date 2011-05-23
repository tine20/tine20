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

/************** models *****************/
Ext.ns('Tine.Admin.Model');

/**
 * Model of a container
 */
Tine.Admin.Model.Container = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.Container.getFieldDefinitions().concat([
    {name: 'note'}
]), {
    appName: 'Admin',
    modelName: 'Container',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Container', 'Containers', n);
    recordName: 'Container',
    recordsName: 'Containers'
});

/**
 * returns default account data
 * 
 * @namespace Tine.Admin.Model.Container
 * @static
 * @return {Object} default data
 */
Tine.Admin.Model.Container.getDefaultData = function () {
    return {
        type: 'shared',
        backend: 'Sql'
    };
};

/************** backend *****************/

Tine.Admin.containerBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'Container',
    recordClass: Tine.Admin.Model.Container,
    idProperty: 'id'
});
