/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Timetracker', 'Tine.Timetracker.Model');

/**
 * @type {Array}
 * Timesheet model fields
 */
Tine.Timetracker.Model.TimesheetArray = Tine.Tinebase.Model.modlogFields.concat([
    { name: 'id' },
    { name: 'account_id' },
    { name: 'timeaccount_id' },
    { name: 'start_date', type: 'date', dateFormat: Date.patterns.ISO8601Short},
    { name: 'start_time', type: 'date', dateFormat: Date.patterns.ISO8601Time },
    { name: 'duration' },
    { name: 'description' },
    { name: 'is_billable', type: 'bool' },
    { name: 'is_billable_combined', type: 'bool' }, // ts & ta is_billable
    { name: 'is_cleared', type: 'bool' },
    { name: 'is_cleared_combined', type: 'bool' }, // ts is_cleared & ta status == 'billed'
    { name: 'billed_in' },
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' },
    { name: 'customfields'}
    // relations
    // TODO fix this, relations do not work yet in TS edit dialog (without admin/manage privileges)
    //{ name: 'relations'}
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
    containerName: 'All Timesheets',
    containersName: 'timesheets lists',
    getTitle: function() {
        var timeaccount = this.get('timeaccount_id'),
            description = Ext.util.Format.ellipsis(this.get('description'), 30, true),
            timeaccountTitle = '';
        
        if (timeaccount) {
            if (typeof(timeaccount.get) !== 'function') {
                timeaccount = new Tine.Timetracker.Model.Timeaccount(timeaccount);
            }
            timeaccountTitle = timeaccount.getTitle();
        }
        
        timeaccountTitle = timeaccountTitle ? '[' + timeaccountTitle + '] ' : '';
        return timeaccountTitle + description;
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

Tine.Timetracker.Model.Timesheet.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Timetracker');
    
    return [
        {label: app.i18n._('Quick search'), field: 'query',    operators: ['contains']}, // query only searches description
        {label: app.i18n._('Account'),      field: 'account_id', valueType: 'user'},
        {label: app.i18n._('Date'),         field: 'start_date', valueType: 'date', pastOnly: true},
        {label: app.i18n._('Description'),  field: 'description', defaultOperator: 'contains'},
        {label: app.i18n._('Billable'),     field: 'is_billable_combined', valueType: 'bool', defaultValue: true },
        {label: app.i18n._('Cleared'),      field: 'is_cleared_combined',  valueType: 'bool', defaultValue: false },
        {label: _('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
        {label: _('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
        {label: _('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
        {label: _('Created By'),                                                        field: 'created_by',         valueType: 'user'},
        {filtertype: 'tinebase.tag', app: app},
        {filtertype: 'timetracker.timeaccount'}
    ];
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
    { name: 'is_open', type: 'bool'},
    { name: 'is_billable', type: 'bool' },
    { name: 'billed_in' },
    { name: 'status' },
    { name: 'deadline' },
    { name: 'account_grants'},
    { name: 'grants'},
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' },
    { name: 'customfields'},
    // relations
    { name: 'relations'}
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
    containerName: 'All Timeaccounts',
    containersName: 'timeaccount lists',
    getTitle: function() {
        var closedText = this.get('is_open') ? '' : (' (' + Tine.Tinebase.appMgr.get('Timetracker').i18n._('closed') + ')');
        
        return this.get('number') ? (this.get('number') + ' - ' + this.get('title') + closedText) : '';
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

    var filters = [
        {label: _('Quick search'),              field: 'query',       operators: ['contains']},
        {label: app.i18n._('Number'),           field: 'number'       },
        {label: app.i18n._('Title'),            field: 'title'        },
        {label: app.i18n._('Description'),      field: 'description', operators: ['contains']},
        {label: app.i18n._('Billed'),           field: 'status',      filtertype: 'timetracker.timeaccountbilled'},
        {label: app.i18n._('Status'),           field: 'is_open',     filtertype: 'timetracker.timeaccountstatus'},
        {label: _('Last Modified Time'),        field: 'last_modified_time', valueType: 'date'},
        {label: _('Last Modified By'),          field: 'last_modified_by',   valueType: 'user'},
        {label: _('Creation Time'),             field: 'creation_time',      valueType: 'date'},
        {label: _('Created By'),                field: 'created_by',         valueType: 'user'},
        {label: app.i18n._('Booking deadline'), field: 'deadline'},
        {filtertype: 'tinebase.tag', app: app}
    ];
    
    if(Tine.Tinebase.appMgr.get('Sales')) {
        filters.push({filtertype: 'timetracker.timeaccountcontract'});
    }
    
    return filters;
};

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
