/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales', 'Tine.Sales.Model');

Tine.Sales.Model.ProductArray = Tine.Tinebase.Model.modlogFields.concat([
    {name: 'id',            type: 'string'},
    {name: 'name',          type: 'string'},
    {name: 'description',   type: 'string'},
    {name: 'price',         type: 'float'},
    {name: 'manufacturer',  type: 'string'},
    {name: 'category',      type: 'string'},
    // tine 2.0 tags and notes
    {name: 'tags'},
    {name: 'notes'},
    // relations with other objects
    { name: 'relations'}
]);

/**
 * @namespace Tine.Sales.Model
 * @class Tine.Sales.Model.Product
 * @extends Tine.Tinebase.data.Record
 * 
 * Product Record Definition
 */ 
Tine.Sales.Model.Product = Tine.Tinebase.data.Record.create(Tine.Sales.Model.ProductArray, {
    appName: 'Sales',
    modelName: 'Product',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Product', 'Products', n);
    recordName: 'Product',
    recordsName: 'Products',
    // ngettext('record list', 'record lists', n);
    containerName: 'All Products',
    containersName: 'Products',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});

/**
 * @namespace Tine.Sales.Model
 * 
 * get default data for a new product
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.Sales.Model.Product.getDefaultData = function() {
    
    var data = {};
    return data;
};

/**
 * @namespace Tine.Sales.Model
 * 
 * get product filter
 *  
 * @return {Array} filter objects
 * @static
 */ 
Tine.Sales.Model.Product.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Sales');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Product name'),   field: 'name' },
        {filtertype: 'tinebase.tag', app: app},
        {label: app.i18n._('Creator'), field: 'created_by', valueType: 'user'}
    ];
};


// Contract model fields
Tine.Sales.Model.ContractArray = Tine.Tinebase.Model.genericFields.concat([
    // contract only fields
    { name: 'id' },
    { name: 'number' },
    { name: 'title' },
    { name: 'description' },
    { name: 'status' },
    { name: 'cleared' },
    { name: 'cleared_in' },
    // tine 2.0 notes field
    { name: 'notes'},
    // linked contacts
    { name: 'relations' }
]);

/**
 * @namespace Tine.Sales.Model
 * @class Tine.Sales.Model.Contract
 * @extends Tine.Tinebase.data.Record
 * 
 * Contract Record Definition
 */ 
Tine.Sales.Model.Contract = Tine.Tinebase.data.Record.create(Tine.Sales.Model.ContractArray, {
    appName: 'Sales',
    modelName: 'Contract',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Contract', 'Contracts', n);
    recordName: 'Contract',
    recordsName: 'Contracts',
    containerProperty: 'container_id',
    // ngettext('All Contracts', 'contracts lists', n);
    containerName: 'All Contracts',
    containersName: 'contracts lists',
    getTitle: function() {
        return this.get('number')  + ' - ' + this.get('title');
    }
});

/**
 * @namespace Tine.Sales.Model
 * 
 * get default data for a new Contract
 *  
 * @return {Object} default data
 * @static
 */
Tine.Sales.Model.Contract.getDefaultData = function() {
    return {
        container_id: Tine.Sales.registry.get('defaultContainer')
    };
};

Tine.Sales.Model.Contract.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Sales');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Contract name'),   field: 'name' },
        {label: app.i18n._('Creator'), field: 'created_by', valueType: 'user'},
        {
            label: app.i18n._('Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'contractStatus'
        },
        {
            label: app.i18n._('Cleared'),
            field: 'cleared',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'contractCleared'
        },
        {label: app.i18n._('Cleared in'), field: 'cleared_in' },
        {filtertype: 'tinebase.tag', app: app}
        
    ];
};

// COST CENTER
Tine.Sales.Model.CostCenterArray = [
    { name: 'id' },
    { name: 'number' },
    { name: 'remark' },
    { name: 'relations' }
];

/**
 * @namespace Tine.Sales.Model
 * @class Tine.Sales.Model.CostCenter
 * @extends Tine.Tinebase.data.Record
 *
 * CostCenter Record Definition
 */
Tine.Sales.Model.CostCenter = Tine.Tinebase.data.Record.create(Tine.Sales.Model.CostCenterArray, {
    appName: 'Sales',
    modelName: 'CostCenter',
    idProperty: 'id',
    titleProperty: 'remark',
    // ngettext('CostCenter', 'CostCenters', n);
    recordName: 'Cost Center',
    recordsName: 'Cost Centers',
    // ngettext('All CostCenters', 'All CostCenters', n);
    containerName: 'All CostCenters',
    containersName: 'All CostCenters'
});

// costcenters filtermodel
Tine.Sales.Model.CostCenter.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Sales');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Number'), field: 'number' },
        {label: app.i18n._('Remark'), field: 'remark' }
    ];
};
// DIVISION

Tine.Sales.Model.DivisionArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id',            type: 'string'},
    {name: 'title',         type: 'string'}
]);

/**
 * @namespace Tine.Sales.Model
 * @class Tine.Sales.Model.Division
 * @extends Tine.Tinebase.data.Record
 * 
 * Division Record Definition
 */ 
Tine.Sales.Model.Division = Tine.Tinebase.data.Record.create(Tine.Sales.Model.DivisionArray, {
    appName: 'Sales',
    modelName: 'Division',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Division', 'Divisions', n);
    recordName: 'Division',
    recordsName: 'Divisions',
    // ngettext('record list', 'record lists', n);
    containerName: 'All Divisions',
    containersName: 'Divisions'
});

// divisions filtermodel
Tine.Sales.Model.Division.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Sales');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Title'), field: 'title' }
    ];
};