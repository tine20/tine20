/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales.Document');

import './AbstractEditDialog'

Tine.Sales.Document_OfferEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    windowWidth: 1024,

    // manage boilerplates
    // manage prices
    // manage states

    initComponent () {
        this.supr().initComponent.call(this)
    },

    checkStates () {
        if(this.loadRequest){
            return _.delay(_.bind(this.checkStates, this), 250);
        }

        const positions = this.getForm().findField('positions').getValue(); //this.record.get('positions')
        const sums = positions.reduce((a, pos) => {
            a['positions_net_sum'] = a['positions_net_sum'] + pos['net_price']
            a['positions_discount_sum'] = a['positions_discount_sum'] + pos['position_discount_sum']

            const rate = pos['sales_tax_rate']
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
        this.supr().checkStates.apply(this, arguments)

        this.record.set('sales_tax', Object.keys(sums['net_sum_by_tax_rate']).reduce((a, rate) => {
            sums['sales_tax_by_rate'][rate] = (sums['net_sum_by_tax_rate'][rate] - this.record.get('invoice_discount_sum') * sums['net_sum_by_tax_rate'][rate] / this.record.get('positions_net_sum')) * rate / 100
            return a + sums['sales_tax_by_rate'][rate]
        }, 0))
        this.record.set('sales_tax_by_rate', sums['sales_tax_by_rate'])
        this.getForm().findField('sales_tax_by_rate')?.setValue(this.record.get('sales_tax_by_rate'))
        this.getForm().findField('sales_tax')?.setValue(this.record.get('sales_tax'))

        this.record.set('gross_sum', this.record.get('positions_net_sum') - this.record.get('invoice_discount_sum') + this.record.get('sales_tax'))
        this.getForm().findField('gross_sum')?.setValue(this.record.get('gross_sum'))

    }
});
