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
    {name: 'id',            type: 'string'},
    // tine 2.0 tags and notes
    {name: 'tags'},
    {name: 'notes'},
    // relations with other objects
    { name: 'relations'}
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
    titleProperty: 'name',
    // ngettext('Employee', 'Employees', n);
    recordName: 'Employee',
    recordsName: 'Employees',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'All Employees',
    containersName: 'Employees',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
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


// Contract model fields
Tine.HumanResources.Model.ContractArray = Tine.Tinebase.Model.genericFields.concat([
    // contract only fields
    { name: 'id' },
    { name: 'number' },
    { name: 'title' },
    { name: 'description' },
    { name: 'status' },
    // tine 2.0 notes field
    { name: 'notes'},
    // linked contacts/accounts
    { name: 'customers'},
    { name: 'accounts'}
]);

/**
 * @namespace Tine.HumanResources.Model
 * @class Tine.HumanResources.Model.Contract
 * @extends Tine.Tinebase.data.Record
 * 
 * Contract Record Definition
 */ 
Tine.HumanResources.Model.Contract = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.ContractArray, {
    appName: 'HumanResources',
    modelName: 'Contract',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Contract', 'Contracts', n);
    recordName: 'Contract',
    recordsName: 'Contracts',
    containerProperty: 'container_id',
    // ngettext('All Contracts', 'contracts lists', n);
    containerName: 'All Contracts',
    containersName: 'contracts lists'
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
    return {
        container_id: Tine.HumanResources.registry.get('defaultContainer')
    };
};

Tine.HumanResources.Model.Contract.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('HumanResources');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Contract name'),   field: 'name' },
        {label: app.i18n._('Creator'), field: 'created_by', valueType: 'user'},
        {filtertype: 'tinebase.tag', app: app}
        
    ];
};
