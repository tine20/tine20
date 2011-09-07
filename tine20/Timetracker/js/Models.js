/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    { name: 'is_billable_combined' }, // ts & ta is_billable
    { name: 'is_cleared' },
    { name: 'is_cleared_combined' }, // ts is_cleared & ta status == 'billed'
    { name: 'billed_in' },
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' },
    { name: 'customfields'}
]);

/**
 * @type {Tine.Tinebase.data.Record}
 * Timesheet record definition
 */
Tine.Timetracker.Model.Timesheet = Tine.Tinebase.data.Record.create(Tine.Timetracker.Model.TimesheetArray, {
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
    containersName: 'timesheets lists',
    getTitle: function() {
        var timeaccount = this.get('timeaccount_id');
        if (timeaccount) {
            if (typeof(timeaccount.get) !== 'function') {
                timeaccount = new Tine.Timetracker.Model.Timeaccount(timeaccount);
            }
            return timeaccount.getTitle();
        }
    },
    copyOmitFields: ['billed_in', 'is_cleared']
});
Tine.Timetracker.Model.Timesheet.getDefaultData = function() { 
    return {
        account_id: Tine.Tinebase.registry.get('currentAccount'),
        duration:   '00:30',
        start_date: new Date(),
        is_billable: true,
        timeaccount_id: {account_grants: {bookOwnGrant: true}}
    };
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
    { name: 'is_billable' },
    { name: 'billed_in' },
    { name: 'status' },
    { name: 'deadline' },
    { name: 'account_grants'},
    { name: 'grants'},
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
]);

/**
 * @type {Tine.Tinebase.data.Record}
 * Timesheet record definition
 */
Tine.Timetracker.Model.Timeaccount = Tine.Tinebase.data.Record.create(Tine.Timetracker.Model.TimeaccountArray, {
    appName: 'Timetracker',
    modelName: 'Timeaccount',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Time Account', 'Time Accounts', n);
    recordName: 'Time Account',
    recordsName: 'Time Accounts',
    containerProperty: 'container_id',
    // ngettext('timeaccount list', 'timeaccount lists', n);
    containerName: 'timeaccount list',
    containersName: 'timeaccount lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    }
});

Tine.Timetracker.Model.Timeaccount.getDefaultData = function() { 
    return {
        is_open: 1,
        is_billable: true
    };
};

Tine.Timetracker.Model.Timeaccount.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Timetracker');
    return [
        {label: _('Quick search'),          field: 'query',       operators: ['contains']},
        {label: app.i18n._('Number'),       field: 'number'       },
        {label: app.i18n._('Title'),        field: 'title'        },
        {label: app.i18n._('Description'),  field: 'description', operators: ['contains']},
        {label: app.i18n._('Created By'),   field: 'created_by',  valueType: 'user'},
        {label: app.i18n._('Status'),       field: 'status',      filtertype: 'timetracker.timeaccountstatus'},
        {label: app.i18n._('Booking deadline'), field: 'deadline'},
        {filtertype: 'tinebase.tag', app: app}
    ];
}

/**
 * Model of a grant
 */
Tine.Timetracker.Model.TimeaccountGrant = Ext.data.Record.create([
    {name: 'id'},
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'},
    {name: 'bookOwnGrant',        type: 'boolean'},
    {name: 'viewAllGrant',        type: 'boolean'},
    {name: 'bookAllGrant',        type: 'boolean'},
    {name: 'manageBillableGrant', type: 'boolean'},
    {name: 'exportGrant',         type: 'boolean'},
    {name: 'adminGrant',          type: 'boolean'}
]);
