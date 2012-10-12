/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
    activeContentType: 'Product',
    contentTypes: [
        {model: 'Product',  requiredRight: null, singularContainerMode: true},
        {model: 'Contract', requiredRight: null, singularContainerMode: true, genericCtxActions: ['grants']}]
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