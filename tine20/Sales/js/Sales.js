/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.MainScreen
 * @extends Tine.widgets.MainScreen
 * MainScreen of the Sales Application <br>
 * <pre>
 * TODO         generalize this
 * </pre>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @constructor
 * Constructs mainscreen of the Sales application
 */
Tine.Sales.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    appName: 'Sales',
    activeContentType: 'Product',
    contentTypes: [
        {modelName: 'Product', requiredRight: 'manage_products', singularContainerMode: true},
        {modelName: 'Contract', requiredRight: null, singularContainerMode: true, genericCtxActions: ['grants']},
        {modelName: 'CostCenter', requiredRight: 'manage_costcenters', singularContainerMode: true},
        {modelName: 'Division', requiredRight: null, singularContainerMode: true},
        // the customer and the dependent address records are configured using the modelconfiguration
        {modelName: 'Customer', requiredRight: 'manage_customers', singularContainerMode: true},
        {modelName: 'Division', requiredRight: null, singularContainerMode: true}
    ]
});

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.FilterPanel
 * @extends Tine.widgets.persistentfilter.PickerPanel
 * Sales Filter Panel<br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Sales.ProductFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Sales.ProductFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Sales.ProductFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Sales_Model_ProductFilter'}]
});

Tine.Sales.ContractFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Sales.ContractFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Sales.ContractFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Sales_Model_ContractFilter'}]
});

Tine.Sales.CostCenterFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Sales.CostCenterFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Sales.CostCenterFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Sales_Model_CostCenterFilter'}]
});

/**
 * default contracts backend
 */
Tine.Sales.contractBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sales',
    modelName: 'Contract',
    recordClass: Tine.Sales.Model.Contract
});

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.productBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Product Backend
 */ 
Tine.Sales.productBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sales',
    modelName: 'Product',
    recordClass: Tine.Sales.Model.Product
});

/**
 * default costcenter backend
 */
Tine.Sales.costcenterBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sales',
    modelName: 'CostCenter',
    recordClass: Tine.Sales.Model.CostCenter
});

/**
 * default division backend
 */
Tine.Sales.divisionBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sales',
    modelName: 'Division',
    recordClass: Tine.Sales.Model.Division
});

Tine.Sales.addToClipboard = function(record, companyName) {
    // this is called either from the edit dialog or from the grid, so we have different record types
    var fieldPrefix = record.data.hasOwnProperty('bic') ? 'adr_' : '';
    
    companyName = companyName ? companyName : (record.get('name') ? record.get('name') : '');

    var lines = companyName + "\n";
    
        lines += (record.get((fieldPrefix + 'prefix1')) ? record.get((fieldPrefix + 'prefix1')) + "\n" : '');
        lines += (record.get((fieldPrefix + 'prefix2')) ? record.get((fieldPrefix + 'prefix2')) + "\n" : '');
        lines += (record.get(fieldPrefix + 'pobox') ? record.get(fieldPrefix + 'pobox') + "\n" : (record.get(fieldPrefix + 'street') ? record.get(fieldPrefix + 'street') + "\n" : ''));
        lines += (record.get((fieldPrefix + 'postalcode')) ? (record.get((fieldPrefix + 'postalcode')) + ' ') : '') + (record.get((fieldPrefix + 'locality')) ? record.get((fieldPrefix + 'locality')) : '');
        
        if (record.get('countryname')) {
            lines += "\n" + record.get('countryname');
        }
    
    var app = Tine.Tinebase.appMgr.get('Sales');
    
    Tine.Sales.CopyAddressDialog.openWindow({winTitle: app.i18n._('Copy address to the clipboard'), app: app, content: lines});
};
