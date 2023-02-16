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
    },

    getRecordFormItems() {
        const rtnVal = this.supr().getRecordFormItems.call(this)
        const items = rtnVal[0].items
        const placeholder = {xtype: 'label', html: '&nbsp', columnWidth: 1/5}

        const statusLine = [this.fields.reversal_status, this.fields.followup_delivery_created_status, this.fields.followup_delivery_booked_status, this.fields.followup_invoice_created_status, this.fields.followup_invoice_booked_status]
        statusLine.cls = 'status-fields'
        items.splice(0, 0, statusLine)

        const followUpLine = [{... placeholder}, this.fields.delivery_recipient_id, this.fields.shared_delivery, this.fields.invoice_recipient_id, this.fields.shared_invoice]
        const rIdx = _.indexOf(items, _.find(items, {line: 'recipient'}))
        items.splice(rIdx+1, 0, followUpLine)

        return rtnVal
    }
});

Ext.reg('sales-document-position-order-gridpanel', OrderPositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Order', 'positions', {
    xtype: 'sales-document-position-order-gridpanel',
    recordClass: 'Sales.DocumentPosition_Order',
    height: 650
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)
