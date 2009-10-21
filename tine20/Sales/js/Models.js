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

Tine.Sales.Model.ProductArray = [
    {name: 'id',            type: 'string'},
    {name: 'name',          type: 'string'},
    {name: 'description',   type: 'string'},
    {name: 'price',         type: 'float'},
    {name: 'tags'},
    {name: 'notes'}
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
