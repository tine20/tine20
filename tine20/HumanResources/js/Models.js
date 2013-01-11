/*
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.namespace('Tine.HumanResources', 'Tine.HumanResources.Model');

// Employee model fields
Tine.HumanResources.Model.EmployeeArray = Tine.Tinebase.Model.modlogFields.concat([
    {name: 'id',                  type: 'string',                    label: null, omitDuplicateResolving: true},
    {name: 'number',              type: 'string', group: 'Employee', label: 'Number' },
    {name: 'account_id',          type: Tine.Tinebase.Model.Account, group: 'Employee', label: 'Account'},
    {name: 'countryname',         type: 'string', group: 'Personal Information', label: 'Country'},
    {name: 'locality',            type: 'string', group: 'Personal Information', label: 'Locality'},
    {name: 'postalcode',          type: 'string', group: 'Personal Information', label: 'Postalcode'},
    {name: 'region',              type: 'string', group: 'Personal Information', label: 'Region'},
    {name: 'street',              type: 'string', group: 'Personal Information', label: 'Street'},
    {name: 'street2',             type: 'string', group: 'Personal Information', label: 'Street 2'},
    {name: 'email',               type: 'string', group: 'Personal Information', label: 'E-Mail'},
    {name: 'tel_home',            type: 'string', group: 'Personal Information', label: 'Telephone Number'},
    {name: 'tel_cell',            type: 'string', group: 'Personal Information', label: 'Cell Phone Number'},
    {name: 'bday',                type: 'date',   group: 'Personal Information', label: 'Birthday', dateFormat: Date.patterns.ISO8601Long},
    {name: 'n_given',             type: 'string', group: 'Employee', label: 'First Name'},
    {name: 'n_family',            type: 'string', group: 'Employee', label: 'Last Name'},
    {name: 'salutation',          type: 'string', group: 'Employee', label: 'Salutation'},
    {name: 'title',               type: 'string', group: 'Employee', label: 'Title'},
    {name: 'n_fn',                type: 'string', group: 'Employee', label: 'Employee name'},
    {name: 'bank_account_holder', type: 'string', group: 'Banking Information', label: 'Account Holder'},
    {name: 'bank_account_number', type: 'string', group: 'Banking Information', label: 'Account Number'},
    {name: 'bank_name',           type: 'string', group: 'Banking Information', label: 'Bank Name'},
    {name: 'bank_code_number',    type: 'string', group: 'Banking Information', label: 'Code Number'},
    {name: 'supervisor_id',       type: Tine.HumanResources.Model.Employee, group: 'Internal Information', label: 'Supervisor' },
    {name: 'division_id',         type: 'Sales.Division', group: 'Internal Information', label: 'Division' },
    {name: 'health_insurance',    type: 'string', group: 'Internal Information', label: 'Health Insurance' },
    {name: 'profession',          type: 'string', group: 'Internal Information', label: 'Profession' },

    {name: 'employment_begin',    type: 'date', group: 'Internal Information', label: 'Employment begin', dateFormat: Date.patterns.ISO8601Long },
    {name: 'employment_end',      type: 'date', group: 'Internal Information', label: 'Employment end', dateFormat: Date.patterns.ISO8601Long},
    // tine 2.0 tags and notes
    {name: 'tags', label: 'Tags'},
    {name: 'notes', omitDuplicateResolving: true},
    // relations with other objects
    { name: 'relations',   omitDuplicateResolving: true},
    { name: 'contracts',   omitDuplicateResolving: true},
    { name: 'costcenters', omitDuplicateResolving: true},
    { name: 'customfields', isMetaField: true}
]);

Tine.widgets.grid.RendererManager.register('HumanResources', 'Employee', 'account_id', function(value){
    return value.accountDisplayName;
});

/**
 * @namespace Tine.HumanResources.Model
 * @class Tine.HumanResources.Model.Employee
 * @extends Tine.Tinebase.data.Record
 * 
 * Employee Record Definition
 */ 
Tine.HumanResources.Model.Employee = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.EmployeeArray, {
    appName: 'HumanResources',
    modelName: 'Employee',
    idProperty: 'id',
    titleProperty: 'n_fn',
    // ngettext('Employee', 'Employees', n);
    recordName: 'Employee',
    recordsName: 'Employees',
    // ngettext('Employees', 'Employees', n);
    containerName: 'Employees',
    containersName: 'Employees',
    getTitle: function() {
        return this.get('n_fn') ? this.get('n_fn') : false;
    }
});

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get default data for a new Employee
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.HumanResources.Model.Employee.getDefaultData = function() {
    
    var data = {};
    return data;
};

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get Employee filter
 *  
 * @return {Array} filter objects
 * @static
 */ 
Tine.HumanResources.Model.Employee.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('HumanResources');

    var filters = [
        { label:          _('Quick search'),        field: 'query', operators: ['contains']},
        { label: app.i18n._('Employee name'),       field: 'n_fn' },
        { label: app.i18n._('Employment begin'),    field: 'employment_begin',   valueType: 'date'},
        { label: app.i18n._('Employment end'),      field: 'employment_end',     valueType: 'date'},
        { label: app.i18n._('Account'),             field: 'account_id',         valueType: 'user'},
        { label: app.i18n._('Currently employed'),  field: 'is_employed',        valueType: 'bool'},
        { label: app.i18n._('First Name'),          field: 'n_given'},
        { label: app.i18n._('Title'),               field: 'title'},
        { label: app.i18n._('Profession'),          field: 'profession'},
        { label: app.i18n._('Last Name'),           field: 'n_family'},
        { label: app.i18n._('Salutation'),          field: 'salutation'},
        { label: app.i18n._('Health Insurance'),    field: 'health_insurance'},
        
        { label: _('Last Modified Time'),           field: 'last_modified_time', valueType: 'date'},
        { label: _('Last Modified By'),             field: 'last_modified_by',   valueType: 'user'},
        { label: _('Creation Time'),                field: 'creation_time',      valueType: 'date'},
        { label: _('Created By'),                   field: 'created_by',         valueType: 'user'},
        
        { filtertype: 'tinebase.tag', app: app}
    ];
    
    if (Tine.Tinebase.appMgr.get('Sales')) {
        filters.push({ filtertype: 'foreignrecord', app: app, foreignRecordClass: Tine.Sales.Model.Division, ownField: 'division_id'});
    }
    return filters;
};


/**
 * @namespace Tine.HumanResources
 * @class Tine.HumanResources.employeeBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Employee Backend
 */ 
Tine.HumanResources.employeeBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'HumanResources',
    modelName: 'Employee',
    recordClass: Tine.HumanResources.Model.Employee
});

// Workingtime

Tine.HumanResources.Model.WorkingTimeArray = [
    { name: 'id',            type: 'string'},
    { name: 'title',         type: 'string' },
    { name: 'json',          type: 'string'},
    { name: 'working_hours', type: 'float'}
];

Tine.HumanResources.Model.WorkingTime = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.WorkingTimeArray, {
    appName: 'HumanResources',
    modelName: 'WorkingTime',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Working Time', 'Working Times', n);
    recordName: 'Working Time',
    recordsName: 'Working Times',
    // ngettext('Working Times', 'Working Times', n);
    containerName: 'Working Times',
    containersName: 'Working Times',
    getTitle: function() {
        return this.get('title') ? this.get('title') : false;
    }
});

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get default data for a new WorkingTime
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.HumanResources.Model.WorkingTime.getDefaultData = function() {
    
    var data = {};
    return data;
};

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get WorkingTime filter
 *  
 * @return {Array} filter objects
 * @static
 */ 
Tine.HumanResources.Model.WorkingTime.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('HumanResources');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']}
    ];
};

/**
 * @namespace Tine.HumanResources
 * @class Tine.HumanResources.workingtimeBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Employee Backend
 */ 
Tine.HumanResources.workingtimeBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'HumanResources',
    modelName: 'WorkingTime',
    recordClass: Tine.HumanResources.Model.WorkingTime
});

// CostCenter

Tine.HumanResources.Model.CostCenterArray = Tine.Tinebase.Model.modlogFields.concat([
    {name: 'id',        type: 'string'},
    {name: 'start_date', type: 'date'},
    {name: 'cost_center_id', type: 'Sales.CostCenter' },
    {name: 'employee_id', type: Tine.HumanResources.Model.Employee }
]);

Tine.HumanResources.Model.CostCenter = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.CostCenterArray, {
    appName: 'HumanResources',
    modelName: 'CostCenter',
    idProperty: 'id',
    titleProperty: 'workingtime_id.title',
    // ngettext('CostCenter', 'CostCenters', n);
    recordName: 'CostCenter',
    recordsName: 'CostCenters',
    containerProperty: null,
    // ngettext('CostCenters', 'CostCenters', n);
    containerName: 'CostCenters',
    containersName: 'CostCenters',
    getTitle: function() {
        var cc = this.get('cost_center_id');
        return  cc ? Ext.util.Format.htmlEncode(cc.number + ' - ' + cc.remark) : '';
    }
});

// Contract

Tine.HumanResources.Model.ContractArray = Tine.Tinebase.Model.modlogFields.concat([
    {name: 'id',                type: 'string'},
    {name: 'start_date',        type: 'date'},
    {name: 'feast_calendar_id', type: Tine.Tinebase.Model.Container },
    {name: 'end_date',          type: 'date'},
    {name: 'vacation_days',     type: 'float'},
    {name: 'workingtime_json',  type: 'string' },
    {name: 'employee_id',       type: Tine.HumanResources.Model.Employee },
    {name: 'workingtime_id',    type: Tine.HumanResources.Model.WorkingTime }
]);

Tine.HumanResources.Model.Contract = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.ContractArray, {
    appName: 'HumanResources',
    modelName: 'Contract',
    idProperty: 'id',
    titleProperty: 'workingtime_id.title',
    // ngettext('Contract', 'Contracts', n);
    recordName: 'Contract',
    recordsName: 'Contracts',
    // ngettext('Contracts', 'Contracts', n);
    containerName: 'Contracts',
    containersName: 'Contracts',
    getTitle: function() {
        return this.get('workingtime_id') ? Ext.util.Format.htmlEncode(this.get('workingtime_id').title) + ' ' + Tine.Tinebase.common.dateRenderer(this.get('start_date')) + ' - ' + Tine.Tinebase.common.dateRenderer(this.get('end_date')) : '';
    }
});

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get default data for a new Contract
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.HumanResources.Model.Contract.getDefaultData = function() {
    var data = {};
    return data;
};

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get Contract filter
 *  
 * @return {Array} filter objects
 * @static
 */ 
Tine.HumanResources.Model.Contract.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('HumanResources');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']}
    ];
};

/**
 * @namespace Tine.HumanResources
 * @class Tine.HumanResources.contractBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Employee Backend
 */ 
Tine.HumanResources.contractBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'HumanResources',
    modelName: 'Employee',
    recordClass: Tine.HumanResources.Model.Contract
});


/*
 * Vacation
 */
Tine.HumanResources.Model.FreeTimeArray = Tine.Tinebase.Model.modlogFields.concat([
    {name: 'id',          type: 'string'},
    {name: 'employee_id', type: Tine.HumanResources.Model.Employee},
    {name: 'type', type: 'string'},
    {name: 'firstday_date', type: 'date'},
    {name: 'remark', type: 'string' },
    {name: 'freedays' },
    {name: 'status', type: 'string' }
    
]);

Tine.HumanResources.Model.FreeTime = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.FreeTimeArray, {
    appName: 'HumanResources',
    modelName: 'FreeTime',
    idProperty: 'id',
    titleProperty: 'remark',
    // ngettext('Free Time', 'Free Times', n);
    recordName: 'Free Time',
    recordsName: 'Free Times',
    containerName: 'Free Time',
    containersName: 'Free Times'
});

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get default data for a new FreeTime
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.HumanResources.Model.FreeTime.getDefaultData = function() {
    var data = {};
    return data;
};

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get FreeTime filter
 *  
 * @return {Array} filter objects
 * @static
 */ 
Tine.HumanResources.Model.FreeTime.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('HumanResources');
    
    return [
        { label: _('Quick search'), field: 'query', operators: ['contains']},
        {
            label: app.i18n._('Type'),
            field: 'type',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'freetimeType'
        },
        { filtertype: 'humanresources.freetimeemployee' },
        {
            label: app.i18n._('Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'freetimeStatus'
        },
        { field: 'remark', label: app.i18n._('Remark'), type: 'text' },
        {label: _('Last Modified Time'), field: 'last_modified_time', valueType: 'date'},
        {label: _('Last Modified By'),   field: 'last_modified_by',   valueType: 'user'},
        {label: _('Creation Time'),      field: 'creation_time',      valueType: 'date'},
        {label: _('Created By'),         field: 'created_by',         valueType: 'user'}
    ];
};

/**
 * @namespace Tine.HumanResources
 * @class Tine.HumanResources.vacationBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Employee Backend
 */ 
Tine.HumanResources.freetimeBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'HumanResources',
    modelName: 'FreeTime',
    recordClass: Tine.HumanResources.Model.FreeTime
});


// FREEDAY
Tine.HumanResources.Model.FreeDayArray = [
    {name: 'id',          type: 'string'},
    {name: 'freetime_id', type: Tine.HumanResources.Model.Employee},
    {name: 'type', type: 'string'},
    {name: 'date', type: 'date'},
    {name: 'duration', type: 'float'}
];

Tine.HumanResources.Model.FreeDay = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.FreeDayArray, {
    appName: 'HumanResources',
    modelName: 'FreeDay',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Free Day', 'Free Days', n);
    recordName: 'Free Day',
    recordsName: 'Free Days',
    // ngettext('Free Days', 'Free Days', n);
    containerName: 'Free Days',
    containersName: 'Free Days',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get default data for a new FreeDay
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.HumanResources.Model.FreeDay.getDefaultData = function() {
    return {
        workingtime_id: null,
        duration: null
    };
};
