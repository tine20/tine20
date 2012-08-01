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
 * @class Tine.HumanResources.Application
 * @extends Tine.Tinebase.Application
 */
Tine.HumanResources.Application = Ext.extend(Tine.Tinebase.Application, {
    hasMainScreen : true,
    /**
     * Get translated application title of this application
     * @return {String}
     */
    getTitle : function() {
        return this.i18n.gettext('Human Resources');
    }
});

/**
 * @namespace Tine.HumanResources
 * @class     Tine.HumanResources.MainScreen
 * @extends   Tine.widgets.MainScreen
 * @author    Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.HumanResources.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Employee',
    contentTypes: [
        {model: 'Employee',  requiredRight: null, singularContainerMode: true},
        {model: 'FreeTime',  requiredRight: null, singularContainerMode: true}
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

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.TreePanel
 * @extends     Tine.widgets.container.TreePanel
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.HumanResources.FreeTimeTreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
    id: 'HumanResources_Tree',
    filterMode: 'filterToolbar',
    recordClass: Tine.HumanResources.Model.FreeTime
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
Tine.HumanResources.FreeTimeFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.HumanResources.FreeTimeFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.HumanResources.FreeTimeFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'HumanResources_Model_FreeTimeFilter'}]
});




