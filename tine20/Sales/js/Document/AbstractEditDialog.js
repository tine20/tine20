/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

import { BoilerplatePanel } from './BoilerplatePanel'

Tine.Sales.Document_AbstractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowWidth: 1024,

    initComponent() {
        Tine.Sales.Document_AbstractEditDialog.superclass.initComponent.call(this);

        this.items.get(0).insert(1, new BoilerplatePanel({}));
    },

    checkStates () {
        if(this.loadRequest){
            return _.delay(_.bind(this.checkStates, this), 250);
        }

        const positions = this.getForm().findField('positions').getValue(); //this.record.get('positions')
        const sums = positions.reduce((a, pos) => {
            a['positions_net_sum'] = (a['positions_net_sum'] || 0) + (pos['net_price'] || 0)
            a['positions_discount_sum'] = (a['positions_discount_sum'] || 0) + (pos['position_discount_sum'] || 0)

            const rate = pos['sales_tax_rate'] || 0
            a['sales_tax_by_rate'][rate] = (a['sales_tax_by_rate'].hasOwnProperty(rate) ? a['sales_tax_by_rate'][rate] : 0) + (pos['sales_tax'] || 0)
            a['net_sum_by_tax_rate'][rate] = (a['net_sum_by_tax_rate'].hasOwnProperty(rate) ? a['net_sum_by_tax_rate'][rate] : 0) + (pos['net_price'] || 0)

            return a;
        }, {positions_net_sum:0, positions_discount_sum: 0, sales_tax_by_rate: {}, net_sum_by_tax_rate: {}})

        Object.keys(sums).forEach((fld) => {
            if (this.recordClass.hasField(fld)) {
                this.record.set(fld, sums[fld])
            }
            this.getForm().findField(fld)?.setValue(sums[fld])
        })

        // make sure discount calculations run
        Tine.Sales.Document_AbstractEditDialog.superclass.checkStates.apply(this, arguments)

        this.record.set('sales_tax', Object.keys(sums['net_sum_by_tax_rate']).reduce((a, rate) => {
            sums['sales_tax_by_rate'][rate] = (sums['net_sum_by_tax_rate'][rate] - this.record.get('invoice_discount_sum') * sums['net_sum_by_tax_rate'][rate] / this.record.get('positions_net_sum')) * rate / 100
            return a + sums['sales_tax_by_rate'][rate]
        }, 0))
        this.record.set('sales_tax_by_rate', sums['sales_tax_by_rate'])
        this.getForm().findField('sales_tax_by_rate')?.setValue(this.record.get('sales_tax_by_rate'))
        this.getForm().findField('sales_tax')?.setValue(this.record.get('sales_tax'))

        this.record.set('gross_sum', this.record.get('positions_net_sum') - this.record.get('invoice_discount_sum') + this.record.get('sales_tax'))
        this.getForm().findField('gross_sum')?.setValue(this.record.get('gross_sum'))
    },

    getRecordFormItems: function() {
        const fields = this.fields = Tine.widgets.form.RecordForm.getFormFields(this.recordClass, (fieldName, fieldDefinition) => {
            switch (fieldName) {

            }
        });

        const placeholder = {xtype: 'label', html: '&nbsp', columnWidth: 1/5}
        return [{
            region: 'center',
            xtype: 'columnform',
            items: [
                [fields.document_number, fields.offer_status, fields.booking_date, fields.document_category, fields.document_language],
                [fields.customer_id, fields.recipient_id, fields.contact_id, _.assign(fields.customer_reference, {columnWidth: 2/5})],
                [ _.assign(fields.document_title, {columnWidth: 3/5}), { ...placeholder }, fields.date ],
                [{xtype: 'textarea', name: 'boilerplate_pretext', enableKeyEvents: true, height: 70, fieldLabel: 'Pretext'}],
                [fields.positions],
                [_.assign({ ...placeholder } , {columnWidth: 3/5}), fields.positions_discount_sum, fields.positions_net_sum],
                [_.assign({ ...placeholder } , {columnWidth: 2/5}), fields.invoice_discount_type, fields.invoice_discount_percentage, fields.invoice_discount_sum],
                [fields.payment_method, { ...placeholder }, fields.net_sum, fields.sales_tax, fields.gross_sum],
                [{xtype: 'textarea', name: 'boilerplate_posttext', enableKeyEvents: true, height: 70, fieldLabel: 'Posttext'}],
                [fields.cost_center_id, fields.cost_bearer_id, _.assign({ ...placeholder } , {columnWidth: 3/5})],
                [fields.note]
            ]
        }];
    }

});
