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
 * @class       Tine.Sales.ContractContactInternalFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.Sales.PurchaseInvoiceApproverFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    // private
    field: 'approver',
    valueType: 'relation',
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.label = this.app.i18n._('Approver');
        this.foreignRecordClass = Tine.Addressbook.Model.Contact;
        this.pickerConfig = {emptyText: this.app.i18n._('without approver'), allowBlank: true};

        Tine.Sales.PurchaseInvoiceApproverFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['sales.purchaseinvoice_approver'] = Tine.Sales.PurchaseInvoiceApproverFilterModel;
