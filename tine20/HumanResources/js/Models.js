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
Tine.HumanResources.Model.EmployeeArray = [
    {name: 'id',                  type: 'string'},
    {name: 'contact_id',          type: Tine.Addressbook.Model.Contact},
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
    {name: 'n_family',            type: 'string'},
    {name: 'n_given',             type: 'string'},
    {name: 'n_fn',                type: 'string'},
    {name: 'bday',                type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'bank_account_holder', type: 'string'},
    {name: 'bank_account_number', type: 'string'},
    {name: 'bank_name',           type: 'string'},
    {name: 'bank_code_number',    type: 'string'},

    {name: 'employment_begin',    type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'employment_end',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'vacation_days',       type: 'int'},
    // tine 2.0 tags and notes
    {name: 'tags'},
    {name: 'notes'},
    // relations with other objects
    { name: 'relations'},
    { name: 'elayers' }
];

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

// ELayer

Tine.HumanResources.Model.ElayerArray = [
    {name: 'id',        type: 'string'},
    {name: 'start_date', type: 'date'},
    {name: 'end_date', type: 'date'},
    {name: 'vacation_days', type: 'int'},
    {name: 'cost_centre', type: 'string'},
    {name: 'working_hours', type: 'int'},
    {name: 'employee_id', type: 'string' }
];

Tine.HumanResources.Model.Elayer = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.ElayerArray, {
    appName: 'HumanResources',
    modelName: 'Elayer',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Elayer', 'Elayers', n);
    recordName: 'Elayer',
    recordsName: 'Elayers',
//    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'All Elayers',
    containersName: 'Elayers',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get default data for a new Elayer
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.HumanResources.Model.Elayer.getDefaultData = function() {
    
    var data = {};
    return data;
};

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get Elayer filter
 *  
 * @return {Array} filter objects
 * @static
 */ 
Tine.HumanResources.Model.Elayer.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('HumanResources');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']}
//        {label: app.i18n._('Elayer name'),   field: 'name' },
//        {filtertype: 'tinebase.tag', app: app},
//        {label: app.i18n._('Creator'), field: 'created_by', valueType: 'user'}
    ];
};


