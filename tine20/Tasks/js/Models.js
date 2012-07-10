/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Tasks.Model');

// Task model
Tine.Tasks.Model.TaskArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'percent', header: 'Percent' },
    { name: 'completed', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'due', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    // ical common fields
    { name: 'class' },
    { name: 'description' },
    { name: 'geo' },
    { name: 'location' },
    { name: 'organizer' },
    { name: 'originator_tz' },
    { name: 'priority' },
    { name: 'status' },
    { name: 'summary' },
    { name: 'url' },
    // ical common fields with multiple appearance
    { name: 'attach' },
    { name: 'attendee' },
    { name: 'tags' },
    { name: 'comment' },
    { name: 'contact' },
    { name: 'related' },
    { name: 'resources' },
    { name: 'rstatus' },
    // scheduleable interface fields
    { name: 'dtstart', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'duration', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'recurid' },
    // scheduleable interface fields with multiple appearance
    { name: 'exdate' },
    { name: 'exrule' },
    { name: 'rdate' },
    { name: 'rrule' },
    // tine 2.0 notes field
    { name: 'notes'},
    // tine 2.0 alarms field
    { name: 'alarms'},
    // relations with other objects
    { name: 'relations'}
]);

/**
 * Task record definition
 */
Tine.Tasks.Model.Task = Tine.Tinebase.data.Record.create(Tine.Tasks.Model.TaskArray, {
    appName: 'Tasks',
    modelName: 'Task',
    idProperty: 'id',
    titleProperty: 'summary',
    // ngettext('Task', 'Tasks', n); gettext('Tasks');
    recordName: 'Task',
    recordsName: 'Tasks',
    containerProperty: 'container_id',
    // ngettext('to do list', 'to do lists', n); gettext('to do lists');
    containerName: 'to do list',
    containersName: 'to do lists'
});

/**
 * returns default account data
 *
 * @namespace Tine.Tasks.Model.Task
 * @static
 * @return {Object} default data
 */
Tine.Tasks.Model.Task.getDefaultData = function() {
    var app = Tine.Tinebase.appMgr.get('Tasks');

    return {
        'class': 'PUBLIC',
        percent: 0,
        organizer: Tine.Tinebase.registry.get('currentAccount'),
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer()
    };
};

/**
 * @namespace Tine.Tasks.Model.Task
 *
 * get task filter
 *
 * @return {Array} filter objects
 * @static
 */
Tine.Tasks.Model.Task.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Tasks');

    return [
        {label: _('Quick search'),                  field: 'query',    operators: ['contains']},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Tasks.Model.Task},
        {label: app.i18n._('Summary'),         field: 'summary' },
        {label: app.i18n._('Due Date'),        field: 'due', valueType: 'date', operators: ['within', 'before', 'after']},
        {
            label: app.i18n._('Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter',
            app: app,
            defaultValue: Tine.Tasks.Model.Task.getClosedStatus(),
            keyfieldName: 'taskStatus',
            defaultOperator: 'notin'
        },
        {label: app.i18n._('Responsible'),     field: 'organizer', valueType: 'user'},
        {filtertype: 'tinebase.tag', app: app},
        {label: _('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
        {label: _('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
        {label: _('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
        {label: _('Created By'),                                                        field: 'created_by',         valueType: 'user'}
    ];
};

/**
 * @namespace Tine.Tasks.Model.Task
 *
 * get closed status ids
 *
 * @return {Array} status ids objects
 * @static
 */
Tine.Tasks.Model.Task.getClosedStatus = function() {
    var reqStatus = [];

    Tine.Tinebase.widgets.keyfield.StoreMgr.get('Tasks', 'taskStatus').each(function(status) {
        if (! status.get('is_open')) {
            reqStatus.push(status.get('id'));
        }
    }, this);

    return reqStatus;
};

/**
 * default tasks backend
 */
Tine.Tasks.JsonBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Tasks',
    modelName: 'Task',
    recordClass: Tine.Tasks.Model.Task
});

Tine.Tasks.Model.Status = Tine.Tinebase.data.Record.create([
    { name: 'id' },
    { name: 'value' },
    { name: 'icon' },
    { name: 'system' },
    { name: 'is_open' },
    { name: 'i18nValue' }
], {
    appName: 'Tasks',
    modelName: 'Status',
    idProperty: 'id',
    titleProperty: 'i18nValue',
    // ngettext('Status', 'Status', n); gettext('Status');
    recordName: 'Status',
    recordsName: 'Status'
});