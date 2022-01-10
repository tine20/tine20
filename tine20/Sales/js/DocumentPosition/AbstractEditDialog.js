/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

Tine.Sales.AbstractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    initComponent() {
        Tine.Sales.AbstractEditDialog.superclass.initComponent.call(this);
        
        // @TODO add filter for product_id
        
        this.getForm().findField('product_id').on('select', this.onProductSelect, this);
    },

    onProductSelect(combo, record, idx) {
        this.record.setFromProduct(record);
        this.onRecordLoad();
    },

    checkStates() {
        if (this.loadRequest) {
            return _.delay(_.bind(this.checkStates, this), 250);
        }
        Tine.Sales.AbstractEditDialog.superclass.checkStates.call(this);

        const type = this.record.get('type');
        const isProductType = this.record.isProductType();
        const productId = this.record.get('product_id');
        
        this.getForm().findField('product_id').allowBlank = !isProductType;
        this.getForm().items.items.forEach((field) => {
            const isGenericField = ['type', 'title'].concat(type === 'TEXT' ? 'description' : []).indexOf(field.name) >= 0;
            
            // manage type relevant fields
            const isTypeField = isProductType || isGenericField;
            field[!isTypeField ? 'hide' : 'show']();
            if (!isTypeField) {
                field.setValue(null);
                if (field.clearValue) field.clearValue();
            }

            // disable fields unless product is chosen
            field.setDisabled(this.fixedFields.get(field.name) ||
                (isProductType && !productId && ['type', 'product_id'].indexOf(field.name) < 0));
        });
        if (isProductType) {
            this.record.computePrice();
            this.getForm().findField('net_price').setValue(this.record.get('net_price'));
            this.getForm().findField('gross_price').setValue(this.record.get('gross_price'));
        }
        _.defer(_.bind(this.doLayout, this));
    }
    
});


Tine.Sales.DocumentPosition_OfferEditDialog = Ext.extend(Tine.Sales.AbstractEditDialog, {

});
