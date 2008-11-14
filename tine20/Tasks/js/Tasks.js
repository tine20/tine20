/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine');

// default app
Tine.Tasks = Tine.Tinebase.Application;

// default mainscreen
Tine.Tasks.MainScreen = Tine.Tinebase.widgets.app.MainScreen;

Tine.Tasks.TreePanel = function(config) {
    Ext.apply(this, config);
    this.recordClass = Tine.Tasks.Task;
    Tine.Tasks.TreePanel.superclass.constructor.call(this);
}
Ext.extend(Tine.Tasks.TreePanel , Tine.widgets.container.TreePanel);

// Task model
Tine.Tasks.TaskArray = [
    // tine record fields
    { name: 'container_id', header: 'Container'                                     },
    { name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'created_by',         type: 'int'                  },
    { name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'last_modified_by',   type: 'int'                  },
    { name: 'is_deleted',         type: 'boolean'              },
    { name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'deleted_by',         type: 'int'                  },
    // task only fields
    { name: 'id' },
    { name: 'percent', header: 'Percent' },
    { name: 'completed', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'due', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    // ical common fields
    { name: 'class_id' },
    { name: 'description' },
    { name: 'geo' },
    { name: 'location' },
    { name: 'organizer' },
    { name: 'priority' },
    { name: 'status_id' },
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
    { name: 'notes'}
];

/**
 * Task record definition
 */
Tine.Tasks.Task = Tine.Tinebase.Record.create(Tine.Tasks.TaskArray, {
    appName: 'Tasks',
    modelName: 'Task',
    idProperty: 'id',
    titleProperty: 'summary',
    // ngettext('Task', 'Tasks, n);
    recordName: 'Task',
    recordsName: 'Tasks',
    containerProperty: 'container_id',
    // ngettext('to do list', 'to do lists', n);
    containerName: 'to do list',
    containersName: 'to do lists'
});

/**
 * default tasks backend
 */
Tine.Tasks.JsonBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Tasks',
    modelName: 'Task',
    recordClass: Tine.Tasks.Task
});
