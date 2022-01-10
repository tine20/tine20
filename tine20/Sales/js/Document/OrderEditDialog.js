/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import './AbstractEditDialog'
import OrderPositionGridPanel from "../DocumentPosition/AbstractGridPanel";

Ext.ns('Tine.Sales.Document');

Tine.Sales.Document_OrderEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    statusFieldName: 'order_status',

    initComponent () {
        this.supr().initComponent.call(this)
    }
});

Ext.reg('sales-document-position-order-gridpanel', OrderPositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Order', 'positions', {
    xtype: 'sales-document-position-order-gridpanel',
    recordClass: 'Sales.DocumentPosition_Order',
    height: 650
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)
