/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * A NumberField allows to force decimals and prefix, suffix handling
 * 
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.NumberField
 * @extends     Ext.form.NumberField
 */
Ext.ux.form.NumberField = Ext.extend(Ext.form.NumberField, {
    
    /**
     * shall the thousand operator be shown?
     * 
     * @type {Boolean}
     */
    useThousandSeparator: true,
    
    /**
     * allows a prefix before the value
     * 
     * @type {String}
     */
    suffix: null,
    
    /**
     * allows a suffix after the value
     * 
     * @type {String}
     */
    prefix: null,

    /**
     * @cfg {Boolean} nullable
     */
    nullable: false,

    style: 'text-align: right',
    
    initComponent: function() {
        if (this.useThousandSeparator) {
            this.thousandSeparator = this.thousandSeparator ? this.thousandSeparator : (this.decimalSeparator == '.' ? ',' : '.');
        }
        
        Ext.ux.form.NumberField.superclass.initComponent.call(this);
        
        this.on('focus', this.selectText, this);
    },

    selectText: function() {
        this.el.dom.select();
    },
    
    /**
     * @see Ext.form.NumberField
     * 
     * if decimalSeparator is not ".", the decimals won't be shown if 0 in superclass, so fix it here
     * 
     * @param {String} v
     * @return {Ext.form.ComboBox}
     */
    setValue: function(v) {
        // If empty string!
        if (['', null, undefined].indexOf(v) >= 0 && !this.nullable) {
            v = "0";
        }

        Ext.ux.form.NumberField.superclass.setValue.call(this, v);

        if (['', null, undefined].indexOf(v) >= 0 && this.nullable) {
            return this;
        }
        
        var split = String(this.getValue()).split('.');

        var tenString = split[0];

        var prefix = '';

        if (tenString[0] == '-') {
            tenString = tenString.substr(1);
            prefix = '-';
        }

        if (split.length == 1) {
            var decimalString = '';
        } else {
            var decimalString = split[1];
        }
        
        while (this.allowDecimals && decimalString.length < this.decimalPrecision) {
            decimalString += '0';
        }

        var tenStringValue = tenString;

        if (this.useThousandSeparator && tenString.length > 3) {
            
            var tenStringValue = '';

            var stringIndex = 0;
            var firstLength = tenString.length % 3;
            if (firstLength) {
                while (stringIndex < firstLength) {
                    tenStringValue += tenString[stringIndex];
                    stringIndex++;
                }

                tenStringValue += this.thousandSeparator;
            }
            
            var i = 0;
            while(stringIndex < tenString.length) {
                tenStringValue += tenString[stringIndex];
                stringIndex++;
                i++;
                if (i == 3 && (stringIndex != (tenString.length))) {
                    i = 0;
                    tenStringValue += this.thousandSeparator;
                }
            }
        }
        
        var showValue = (this.prefix ? this.prefix : '') + tenStringValue + ((this.decimalSeparator && decimalString) ? this.decimalSeparator : '') + decimalString + (this.suffix ? this.suffix : '');
        this.setRawValue(prefix + showValue);

        // remove invalid after fix decimalPrecision
        this.clearInvalid();
        
        return this;
    },

    getValue: function () {
        let value = Ext.ux.form.NumberField.superclass.getValue.call(this);
        return value === '' ? null : value;
    },

    // private, overwrites Ext.form.NumberField.parseValue
    parseValue : function(value){
        if (!value) {
            return value;
        }
        
        if (this.useThousandSeparator) {
            var regex = new RegExp(((this.thousandSeparator == ".") ? '\\.' : this.thousandSeparator), 'g');
            value = value.replace(regex, '');
        }
        
        value = String(value).replace(regex, '', 'g');
        value = parseFloat(String(value).replace(this.decimalSeparator, "."));
        value = isNaN(value) ? '' : value;

        return value;
    },
    
    /**
     * @see Ext.form.NumberField
     * 
     * strips the prefix and suffix before superclass is validating
     * 
     * @param {String} value
     * @return {String}
     */
    validateValue: function(value) {
        if (value && this.prefix) {
            var regex = new RegExp(this.prefix, 'g');
            value = value.replace(regex, '');
        }
        
        if (value && this.suffix) {
            var regex = new RegExp(this.suffix, 'g');
            value = value.replace(regex, '');
        }
        
        if (value && this.useThousandSeparator) {
            var regex = new RegExp(((this.thousandSeparator == ".") ? '\\.' : this.thousandSeparator), 'g');
            value = value.replace(regex, '');
        }

        const decimalRe = new RegExp(this.decimalSeparator === "." ? '\\.' : this.decimalSeparator, 'g');
        if (value && !this.allowDecimals && String(value).match(decimalRe)) {
            this.markInvalid(Tine.Tinebase.i18n._('no decimals allowed'));
            return false;
        }

        if (value && this.allowDecimals && String(value).match(decimalRe)) {
            const parts = value.split(decimalRe);
            if (parts[1].length > this.decimalPrecision) {
                this.markInvalid(String.format(Tine.Tinebase.i18n._('max decimals allowed: {0}'), this.decimalPrecision));
                return false;
            }
        }

        return Ext.ux.form.NumberField.superclass.validateValue.call(this, value);
    }
});

Ext.reg('extuxnumberfield', Ext.ux.form.NumberField);
