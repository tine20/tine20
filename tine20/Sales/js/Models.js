/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Sales', 'Tine.Sales.Model');

// Product model fields
Tine.Sales.Model.ProductArray = [
    {name: 'id',            type: 'string'},
    {name: 'name',          type: 'string'},
    {name: 'description',   type: 'string'},
    {name: 'price',         type: 'float'},
    // tine 2.0 tags and notes
    {name: 'tags'},
    {name: 'notes'},
    // relations with other objects
    { name: 'relations'}
];

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
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'Products',
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
        {label: _('Quick search'), field: 'query',    operators: ['contains']},
        {label: app.i18n._('Product name'),   field: 'name' },
        {filtertype: 'tinebase.tag', app: app}
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
    // tine 2.0 notes field
    { name: 'notes'},
    // linked contacts/accounts
    { name: 'customers'},
    { name: 'accounts'}
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
    recordName: 'Contracts',
    recordsName: 'Contracts',
    containerProperty: 'container_id',
    // ngettext('contracts list', 'contracts lists', n);
    containerName: 'contracts list',
    containersName: 'contracts lists'
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
        container_id: Tine.Sales.registry.get('DefaultContainer')
    };
};


