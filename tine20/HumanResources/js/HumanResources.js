/*
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.HumanResources');

/**
 * @namespace Tine.HumanResources
 * @class     Tine.HumanResources.MainScreen
 * @extends   Tine.widgets.MainScreen
 * @author    Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.HumanResources.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Employee',
    contentTypes: [
        {model: 'Employee',  requiredRight: null, singularContainerMode: true}
//        {model: 'Contract', requiredRight: null, singularContainerMode: true, genericCtxActions: ['grants']}
        ]
});

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.TreePanel
 * @extends     Tine.widgets.container.TreePanel
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.HumanResources.EmployeeTreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
    id: 'HumanResources_Tree',
    filterMode: 'filterToolbar',
    recordClass: Tine.HumanResources.Model.Employee
});

/**
 * @namespace Tine.HumanResources
 * @class Tine.HumanResources.FilterPanel
 * @extends Tine.widgets.persistentfilter.PickerPanel
 * HumanResources Filter Panel<br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.HumanResources.EmployeeFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.HumanResources.EmployeeFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.HumanResources.EmployeeFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'HumanResources_Model_EmployeeFilter'}]
});

