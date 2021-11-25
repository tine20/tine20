/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.form');

Tine.widgets.form.DiscountField = Ext.extend(Ext.ux.form.MoneyField, {
    price_field: null,
    type_field: null,
    percentage_field: null,
    sum_field: null,
    net_field: null,

    /**
     * @cgf {Boolean} singleField
     * discount is handled with a single field only
     */
    singleField: false,

    allowNegative: false,
    validateOnBlur: true,

    initComponent: function() {
        this.baseChars = this.baseChars + '%' + Tine.Tinebase.registry.get('currencySymbol');

        Tine.widgets.form.DiscountField.superclass.initComponent.apply(this, arguments);
    },

    autoConfig: function(record) {
        if (record) {
            this.record = record;
            // autoconfig corresponding fields
            this.sum_field = this.sum_field || this.fieldName;
            ['type', 'percentage'].forEach((fld) => {
                if (!this[`${fld}_field`]) {
                    const fldName = this.fieldName.replace(/_sum$/, `_${fld}`)
                    if (this.record.constructor.hasField(fldName)) {
                        this[`${fld}_field`] = fldName;
                    }
                }
            })
        }
    },

    setValue: function(value, record) {
        this.autoConfig(record);

        if (this.type_field && this.record.get(this.type_field) && this.singleField) {
            this.suffix = ' ' + (this.record.get(this.type_field) === 'PERCENTAGE' ? '%' : Tine.Tinebase.registry.get('currencySymbol'));
            // this.decimalPrecision = this.suffix === ' %' ? 0 : 2;
        }

        const rtn = Tine.widgets.form.DiscountField.superclass.setValue.call(this, value, record)
    },

    checkState: function(editDialog, record) {
        this.autoConfig(record);
        this.computeValues(record, editDialog?.getForm(), this);

        const type = record.get(this.type_field)
        editDialog?.getForm().findField(this.percentage_field)?.setReadOnly(this.singleField === false && type !== 'PERCENTAGE');
        editDialog?.getForm().findField(this.sum_field)?.setReadOnly(this.singleField === false && type !== 'SUM');
    },

    validateValue: function(value) {
        value = this.stripSuffix(value);
        return Tine.widgets.form.DiscountField.superclass.validateValue.call(this, value);
    },

    parseValue: function(value) {
        value = this.stripSuffix(value);
        return Tine.widgets.form.DiscountField.superclass.parseValue.call(this, value);
    },

    stripSuffix: function(value) {
        if (! this.singleField) return value;
        const parts = String(value).trim().match(/([0-9,.]*)\s*([^0-9,.]*)/);
        this.suffix = parts.length && ['%', Tine.Tinebase.registry.get('currencySymbol')].indexOf(parts[2]) >=0 ? ` ${parts[2]}` : this.suffix;
        // this.decimalPrecision = this.suffix === ' %' ? 0 : 2;
        if (this.type_field && this.record) {
            const type = this.suffix === ' %' ? 'PERCENTAGE' : 'SUM';
            this.record.set(this.type_field, type);
            this.findParentBy((ct) => {return ct.getForm})?.getForm().findField(this.type_field)?.setValue(type);
        }
        return parts.length ? parts[1] : 0;
    },

    computeValues(record, form, config) {
        const price = (record.get(config.price_field) || 0);
        const type = record.get(config.type_field) || 'SUM';
        const sum = type === 'SUM' ? Math.min(price, (record.get(config.sum_field) || 0)) : (config.singleField ? (record.get(config.sum_field) || 0) : (record.get(config.percentage_field) || 0)) / 100 * price;
        const percentage = type === 'PERCENTAGE' ? (config.singleField ? (record.get(config.sum_field) || 0) : (record.get(config.percentage_field) || 0)) : ((Math.min(price, (record.get(config.sum_field) || 0)) / price) || 0) * 100;
        const net = price - sum;

        ['price', 'type', 'sum', 'percentage', 'net'].forEach((fld) => {
            const fieldName = config[`${fld}_field`];
            const val = (fld === 'sum' && type !== 'SUM' && config.singleField) ? percentage : eval(fld);
            if (fieldName && record.constructor.hasField(fieldName) && record.get(fieldName) !== val) {
                record.set(fieldName, val);
                form?.findField(fieldName)?.setValue(val, record);

            }
        });
    }
});

Ext.reg('discountfield', Tine.widgets.form.DiscountField);
