/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ContractProductFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Sales.ContractProductFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    
    // private
    field: 'products',
    operators: ['contains'],
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.label = this.app.i18n._('Products');
        //this.pickerConfig = {emptyText: this.app.i18n._('without customer'), allowBlank: true};

        Tine.Sales.ContractProductFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['sales.contract-product'] = Tine.Sales.ContractProductFilterModel;
