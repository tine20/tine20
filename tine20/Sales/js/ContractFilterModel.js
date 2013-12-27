/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ContractFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.Sales.ContractFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    // private
    field: 'contract',
    valueType: 'relation',
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.label = this.app.i18n._('Contract');
        this.foreignRecordClass = Tine.Sales.Model.Contract;
        this.pickerConfig = {emptyText: this.app.i18n._('without contract'), allowBlank: true};
        Tine.Sales.ContractFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['sales.invoicecontract']  = Tine.Sales.ContractFilterModel;