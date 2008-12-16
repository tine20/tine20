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
    { name: 'start_date', type: 'date', dateFormat: Date.patterns.ISO8601Short},
    { name: 'start_time', type: 'date', dateFormat: Date.patterns.ISO8601Time },
    { name: 'duration' },
    { name: 'description' },
    { name: 'is_billable' },
    { name: 'is_cleared' },
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' },
    { name: 'customfields'}
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
    containerProperty: 'timeaccount_id',
    // ngettext('timesheets list', 'timesheets lists', n);
    containerName: 'timesheets list',
    containersName: 'timesheets lists'
});
Tine.Timetracker.Model.Timesheet.getDefaultData = function() { 
    return {
        account_id: Tine.Tinebase.registry.get('currentAccount'),
        duration:   '00:30',
        start_date: new Date(),
        is_billable: true
    }
};

/**
 * @type {Array}
 * Timeaccount model fields
 */
Tine.Timetracker.Model.TimeaccountArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'container_id' },
    { name: 'title' },
    { name: 'number' },
    { name: 'description' },
    { name: 'budget' },
    { name: 'budget_unit' },
    { name: 'price' },
    { name: 'price_unit' },
    { name: 'is_open' },
    { name: 'account_grants'},
    { name: 'grants'},
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
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
Tine.Timetracker.Model.Timeaccount.getDefaultData = function() { 
    return {
        is_open: 1
    }
};
/**
 * Model of a grant
 */
Tine.Timetracker.Model.TimeaccountGrant = Ext.data.Record.create([
    {name: 'id'},
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'},
    {name: 'book_own',        type: 'boolean'},
    {name: 'view_all',        type: 'boolean'},
    {name: 'book_all',        type: 'boolean'},
    {name: 'manage_clearing', type: 'boolean'},
    {name: 'manage_all',      type: 'boolean'}
]);
