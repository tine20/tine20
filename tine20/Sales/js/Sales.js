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
        {modelName: 'Division', requiredRight: null, singularContainerMode: true}
    ]
});