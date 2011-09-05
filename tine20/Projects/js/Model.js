/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Projects.Model');


/**
 * @namespace   Tine.Projects.Model
 * @class       Tine.Projects.Model.Project
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Projects.Model.Project = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'name' },
    // TODO add more record fields here
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
]), {
    appName: 'Projects',
    modelName: 'Project',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('example record', 'example records', n);
    recordName: 'example record',
    recordsName: 'example records',
    containerProperty: 'container_id',
    // ngettext('example record list', 'example record lists', n);
    containerName: 'example record list',
    containersName: 'example record lists'
});

/**
 * @namespace Tine.Projects.Model
 * 
 * get default data for a new record
 *  
 * @return {Object} default data
 * @static
 * 
 * TODO generalize default container id handling
 */ 
Tine.Projects.Model.Project.getDefaultData = function() { 
    var app = Tine.Tinebase.appMgr.get('Projects');
    var defaultsContainer = Tine.Projects.registry.get('defaultContainer');
    
    return {
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getSelectedContainer('addGrant', defaultsContainer)
        // TODO add more defaults
    };
};

/**
 * default Project backend
 */
Tine.Projects.recordBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Projects',
    modelName: 'Project',
    recordClass: Tine.Projects.Model.Project
});
