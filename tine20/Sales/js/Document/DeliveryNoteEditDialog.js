/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import './AbstractEditDialog'
import DeliveryNotePositionGridPanel from "../DocumentPosition/AbstractGridPanel";

Ext.ns('Tine.Sales.Document');

Tine.Sales.Document_DeliveryNoteEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    statusFieldName: 'delivery_note_status',

    initComponent () {
        this.supr().initComponent.call(this)
    }
});

Ext.reg('sales-document-position-delivery-note-gridpanel', DeliveryNotePositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_DeliveryNote', 'positions', {
    xtype: 'sales-document-position-delivery-note-gridpanel',
    recordClass: 'Sales.DocumentPosition_DeliveryNote',
    height: 650
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)
