/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    { name: 'title' },
    { name: 'number' },
    { name: 'description' },
    { name: 'status' },
    { name: 'relations' },
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
]), {
    appName: 'Projects',
    modelName: 'Project',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Project', 'Projects', n);
    recordName: 'Project',
    recordsName: 'Projects',
    containerProperty: 'container_id',
    // ngettext('Project list', 'Project lists', n);
    containerName: 'Project list',
    containersName: 'Project lists'
    // _('Project lists')
});

/**
 * @namespace Tine.Projects.Model
 * 
 * get default data for a new record
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.Projects.Model.Project.getDefaultData = function() {
    var app = Tine.Tinebase.appMgr.get('Projects');
    var defaultsContainer = Tine.Projects.registry.get('defaultContainer');
    
    return {
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer(),
        status: 'IN-PROCESS'
    };
};

/**
 * get filtermodel of projects
 * 
 * @namespace Tine.ExampleApplication.Model
 * @static
 * @return {Object} filterModel definition
 */ 
Tine.Projects.Model.Project.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Projects');
    
    return [ 
        {label: _('Quick search'),    field: 'query',       operators: ['contains']},
        {label: app.i18n._('Title'),    field: 'title'},
        {label: app.i18n._('Number'),    field: 'number'},
        {label: app.i18n._('Description'),    field: 'description'},
        {
            label: app.i18n._('Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'projectStatus'
        },
        {filtertype: 'tinebase.tag', app: app},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Projects.Model.Project},
        {filtertype: 'tine.projects.attendee', app: app},
        {label: _('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
        {label: _('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
        {label: _('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
        {label: _('Created By'),                                                        field: 'created_by',         valueType: 'user'}
    ];
};

/**
 * default Project backend
 */
Tine.Projects.recordBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Projects',
    modelName: 'Project',
    recordClass: Tine.Projects.Model.Project
});
