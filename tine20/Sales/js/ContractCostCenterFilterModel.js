/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ContractCostCenterFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.Sales.ContractCostCenterFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    // private
    field: 'costcenter',
    valueType: 'relation',
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.label = this.app.i18n._('Cost Center');
        this.foreignRecordClass = Tine.Sales.Model.CostCenter;
        this.pickerConfig = {emptyText: this.app.i18n._('without CostCenter'), allowBlank: true};

        Tine.Sales.ContractCostCenterFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['sales.contract.costcenter'] = Tine.Sales.ContractCostCenterFilterModel;
