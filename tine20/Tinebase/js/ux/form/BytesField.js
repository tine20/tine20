/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.BytesField
 * @extends     Ext.form.NumberField
 */
Ext.ux.form.BytesField = Ext.extend(Ext.form.NumberField, {
    /**
     * @cfg {String} basePow
     * change this if the value is measured other than in bytes
     */
    basePow: 0,

    /**
     * @cfg {Number} divisor
     */
    divisor: 1024,

    /**
     * @cfg {String} forceUnit
     */
    forceUnit: false,

    suffixes: ['bytes', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'],

    decimalPrecision: 2,
    minValue: 0,
    baseChars : "0123456789bBkKmMgGtTpPeEzZyY ",

    initComponent: function() {
        this.decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator');
        this.validateRe = new RegExp('([0-9' + this.decimalSeparator + ']+)\\s*([a-zA-Z]+)');

        this.supr().initComponent.apply(this, arguments);
    },

    validateValue : function(value){
        var _ = window.lodash,
            me = this,
            parts = String(value).match(this.validateRe),
            number = parts ? parts[1] : value,
            suffix = parts ? parts[2] : this.suffixes[this.basePow],
            pow = suffix ? this.suffixes.indexOf(suffix.toLowerCase()) : 0;

        if(!this.supr().validateValue.call(this, number)) {
            return false;
        }

        if (! _.reduce(me.suffixes, function(r, s) {
                return r || String(s).match(new RegExp('^' + suffix, 'i'));
            }, false)) {
            this.markInvalid(String.format(i18n._('{0} is not a valid unit'), suffix));
            return false;
        }

        return true;
    },


    parseValue : function(value){
        var parts = String(value).match(this.validateRe),
            number = parts ? parts[1] : value,
            suffix = parts ? parts[2] : this.suffixes[this.basePow],
            pow = suffix ? this.suffixes.indexOf(suffix.toLowerCase()) : 0;

        if (value === '' || value === null) return null;
        
        value = this.supr().parseValue.call(this, number);

        value = value * Math.pow(this.divisor, pow);

        value = value / Math.pow(this.divisor, this.basePow);

        // NOTE: decimals might come from bytes
        value = Math.round(value);

        return value;
    },

    setValue: function(value) {
        this.supr().setValue.call(this, value);
        
        value = value !== null && value !== ''?
            Tine.Tinebase.common.byteFormatter(value * Math.pow(this.divisor, this.basePow), this.forceUnit, this.decimalPrecision, false) :
            this.emptyText;

        this.setRawValue(value);

        return this;
    }
});

Ext.reg('extuxbytesfield', Ext.ux.form.BytesField);
