/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import './AbstractEditDialog'
import InvoicePositionGridPanel from "../DocumentPosition/AbstractGridPanel";

Ext.ns('Tine.Sales.Document');

Tine.Sales.Document_InvoiceEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    statusFieldName: 'invoice_status',

    initComponent () {
        this.supr().initComponent.call(this)
    }
});

Ext.reg('sales-document-position-invoice-gridpanel', InvoicePositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Invoice', 'positions', {
    xtype: 'sales-document-position-invoice-gridpanel',
    recordClass: 'Sales.DocumentPosition_Invoice',
    height: 650
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)
