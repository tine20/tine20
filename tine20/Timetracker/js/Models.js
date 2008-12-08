/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Timetracker', 'Tine.Timetracker.Model');

/**
 * @type {Array}
 * Timesheet model fields
 */
Tine.Timetracker.Model.TimesheetArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'account_id' },
    { name: 'timeaccount_id' },
    { name: 'start' },
    { name: 'duration' },
    { name: 'description' },
    { name: 'is_billable' },
    { name: 'is_cleared' },
    // tine 2.0 notes field
    { name: 'notes'}
]);

/**
 * @type {Tine.Tinebase.Record}
 * Timesheet record definition
 */
Tine.Timetracker.Model.Timesheet = Tine.Tinebase.Record.create(Tine.Timetracker.Model.TimesheetArray, {
    appName: 'Timetracker',
    modelName: 'Timesheet',
    idProperty: 'id',
    titleProperty: null,
    // ngettext('Timesheet', 'Timesheets', n);
    recordName: 'Timesheet',
    recordsName: 'Timesheets',
    containerProperty: 'container_id',
    // ngettext('timesheets list', 'timesheets lists', n);
    containerName: 'timesheets list',
    containersName: 'timesheets lists'
});

/**
 * @type {Array}
 * Timeaccount model fields
 */
Tine.Timetracker.Model.TimeaccountArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'title' },
    { name: 'number' },
    { name: 'description' },
    { name: 'budget' },
    { name: 'unitprice' },
    { name: 'unit' },
    { name: 'is_open' },
    // tine 2.0 notes field
    { name: 'notes'}
]);

/**
 * @type {Tine.Tinebase.Record}
 * Timesheet record definition
 */
Tine.Timetracker.Model.Timeaccount = Tine.Tinebase.Record.create(Tine.Timetracker.Model.TimeaccountArray, {
    appName: 'Timetracker',
    modelName: 'Timeaccount',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Timeaccount', 'Timeaccounts', n);
    recordName: 'Timeaccount',
    recordsName: 'Timeaccounts',
    containerProperty: 'container_id',
    // ngettext('timeaccount list', 'timeaccount lists', n);
    containerName: 'timeaccount list',
    containersName: 'timeaccount lists',
    getTitle: function() {
        return this.get('number') + ' ' + this.get('title');
    }
});
