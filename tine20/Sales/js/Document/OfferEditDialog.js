/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './AbstractEditDialog'
import OfferPositionGridPanel from '../DocumentPosition/AbstractGridPanel'

Ext.ns('Tine.Sales.Document');

Tine.Sales.Document_OfferEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    statusFieldName: 'offer_status',

    initComponent () {
        this.supr().initComponent.call(this)
    },

    getRecordFormItems() {
        const rtnVal = this.supr().getRecordFormItems.call(this)
        const items = rtnVal[0].items
        const placeholder = {xtype: 'label', html: '&nbsp', columnWidth: 1/5}

        const statusLine = [this.fields.reversal_status, this.fields.followup_order_created_status, this.fields.followup_order_booked_status, {... placeholder}, {... placeholder}]
        statusLine.cls = 'status-fields'
        items.splice(0, 0, statusLine)

        return rtnVal
    }
})

Ext.reg('sales-document-position-offer-gridpanel', OfferPositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Offer', 'positions', {
    xtype: 'sales-document-position-offer-gridpanel',
    recordClass: 'Sales.DocumentPosition_Offer',
    height: 650
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)
