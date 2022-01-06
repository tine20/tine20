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
    }
})

Ext.reg('sales-document-position-offer-gridpanel', OfferPositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Offer', 'positions', {
    xtype: 'sales-document-position-offer-gridpanel',
    recordClass: 'Sales.DocumentPosition_Offer',
    height: 650
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)
