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
    
    initComponent: function() {
        if (this.useThousandSeparator) {
            this.thousandSeparator = this.thousandSeparator ? this.thousandSeparator : (this.decimalSeparator == '.' ? ',' : '.');
        }
        
        Ext.ux.form.NumberField.superclass.initComponent.call(this);
        
        this.on('focus', this.emptyField, this);
    },
    
    emptyField: function() {
        if (this.getValue() == '0') {
            this.setRawValue("");
        }
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
        if (v == "") {
            v = "0";
        }

        Ext.ux.form.NumberField.superclass.setValue.call(this, v);
        
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
        
        while (decimalString.length < this.decimalPrecision) {
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
        
        var showValue = (this.prefix ? this.prefix : '') + tenStringValue + (this.decimalSeparator ? this.decimalSeparator : '') + decimalString + (this.suffix ? this.suffix : '');
        this.setRawValue(prefix + showValue);
        
        return this;
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
        
        return Ext.ux.form.NumberField.superclass.validateValue.call(this, value);
    }
});

Ext.reg('extuxnumberfield', Ext.ux.form.NumberField);
