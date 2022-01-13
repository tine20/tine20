/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import './AbstractEditDialog'
import DeliveryPositionGridPanel from "../DocumentPosition/AbstractGridPanel";

Ext.ns('Tine.Sales.Document');

Tine.Sales.Document_DeliveryEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    statusFieldName: 'delivery_status',

    initComponent () {
        this.supr().initComponent.call(this)
    }
});

Ext.reg('sales-document-position-delivery-gridpanel', DeliveryPositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Delivery', 'positions', {
    xtype: 'sales-document-position-delivery-gridpanel',
    recordClass: 'Sales.DocumentPosition_Delivery',
    height: 650
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)
