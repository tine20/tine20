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
Tine.HumanResources.Model.EmployeeArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id',                  type: 'string'},
    {name: 'number',              type: 'string'},
    {name: 'account_id',          type: Tine.Tinebase.Model.Account},
    {name: 'countryname',         type: 'string'},
    {name: 'locality',            type: 'string'},
    {name: 'postalcode',          type: 'string'},
    {name: 'region',              type: 'string'},
    {name: 'street',              type: 'string'},
    {name: 'street2',             type: 'string'},
    {name: 'email',               type: 'string'},
    {name: 'tel_home',            type: 'string'},
    {name: 'tel_cell',            type: 'string'},
    {name: 'title',               type: 'string'},
    {name: 'n_fn',                type: 'string'},
    {name: 'bday',                type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'bank_account_holder', type: 'string'},
    {name: 'bank_account_number', type: 'string'},
    {name: 'bank_name',           type: 'string'},
    {name: 'bank_code_number',    type: 'string'},
    {name: 'status',    type: 'string'},
    {name: 'vacation_manager_id', type: Tine.Tinebase.Model.Account },
    {name: 'sickness_manager_id', type: Tine.Tinebase.Model.Account },

    {name: 'employment_begin',    type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'employment_end',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'vacation_days',       type: 'int'},
    // tine 2.0 tags and notes
    {name: 'tags'},
    {name: 'notes'},
    // relations with other objects
    { name: 'relations'},
    { name: 'contracts'}
]);

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
//    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'All Employees',
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
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Employee name'),   field: 'name' },
        {filtertype: 'tinebase.tag', app: app},
        {label: app.i18n._('Creator'), field: 'created_by', valueType: 'user'}
        
    ];
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
    // ngettext('WorkingTime', 'WorkingTimes', n);
    recordName: 'WorkingTime',
    recordsName: 'WorkingTimes',
//    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'All WorkingTimes',
    containersName: 'WorkingTimes',
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
//        {label: app.i18n._('WorkingTime name'),   field: 'name' },
//        {filtertype: 'tinebase.tag', app: app},
//        {label: app.i18n._('Creator'), field: 'created_by', valueType: 'user'}
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

// ELayer

Tine.HumanResources.Model.ContractArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id',        type: 'string'},
    {name: 'start_date', type: 'date'},
    {name: 'feast_calendar_id', type: Tine.Tinebase.Model.Container },
    {name: 'end_date', type: 'date'},
    {name: 'vacation_days', type: 'int'},
    {name: 'cost_centre', type: 'string'},
    {name: 'employee_id', type: Tine.HumanResources.Model.Employee },
    {name: 'workingtime_id', type: Tine.HumanResources.Model.WorkingTime }
]);

Tine.HumanResources.Model.Contract = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.ContractArray, {
    appName: 'HumanResources',
    modelName: 'Contract',
    idProperty: 'id',
    titleProperty: 'workingtime_id.title',
    // ngettext('Contract', 'Contracts', n);
    recordName: 'Contract',
    recordsName: 'Contracts',
//    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'All Contracts',
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
//        {label: app.i18n._('Contract name'),   field: 'name' },
//        {filtertype: 'tinebase.tag', app: app},
//        {label: app.i18n._('Creator'), field: 'created_by', valueType: 'user'}
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
Tine.HumanResources.Model.FreeTimeArray = Tine.Tinebase.Model.genericFields.concat([
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
    titleProperty: 'name',
    // ngettext('FreeTime Day', 'FreeTime Days', n);
    recordName: 'FreeTime',
    recordsName: 'FreeTime',
//    containerProperty: 'container_id',
    // ngettext('FreeTime days', 'All vacations days', n);
    containerName: 'FreeTime',
    containersName: '',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
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
            label: app.i18n._('Type'),
            field: 'type',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'freetimeType'
        },
        {
            label: app.i18n._('Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'freetimeStatus'
        }
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


/*
 * Vacation
 */
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
    // ngettext('FreeDay Day', 'FreeDay Days', n);
    recordName: 'FreeDay Day',
    recordsName: 'FreeDay Days',
//    containerProperty: 'container_id',
    // ngettext('FreeDay days', 'All vacations days', n);
    containerName: 'FreeDay days',
    containersName: 'All vacations days',
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

///**
// * @namespace Tine.HumanResources.Model
// * 
// * get FreeDay filter
// *  
// * @return {Array} filter objects
// * @static
// */ 
//Tine.HumanResources.Model.FreeDay.getFilterModel = function() {
//    var app = Tine.Tinebase.appMgr.get('HumanResources');
//    
//    return [
//        { label: _('Quick search'), field: 'query', operators: ['contains']},
//        {
//            label: app.i18n._('Type'),
//            field: 'type',
//            filtertype: 'tine.widget.keyfield.filter', 
//            app: app, 
//            keyfieldName: 'freetimeType'
//        },
//        { filtertype: 'humanresources.freetimeemployee' } 
//    ];
//};


