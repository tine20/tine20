/**
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.FreeTimeEmployeeFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.HumanResources.FreeTimeEmployeeFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    // private
    ownField: 'employee_id',
    linkType: 'foreignId',
    foreignRecordClass: Tine.HumanResources.Model.Employee,
    filterName: 'EmployeeFilter',
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources');
        this.label = this.app.i18n._('Employee');
        this.pickerConfig = {allowBlank: true };

        Tine.HumanResources.FreeTimeEmployeeFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['humanresources.freetimeemployee'] = Tine.HumanResources.FreeTimeEmployeeFilterModel;