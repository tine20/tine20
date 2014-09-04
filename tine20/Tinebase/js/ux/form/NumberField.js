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
     * @see Ext.form.NumberField
     * 
     * if decimalSeparator is not ".", the decimals won't be shown if 0 in superclass, so fix it here
     * 
     * @param {String} v
     * @return {Ext.form.ComboBox}
     */
    setValue: function(v) {
        var combo = Ext.ux.form.NumberField.superclass.setValue.call(this, v);
        var sep = this.decimalSeparator;
        
        if (combo) {
            var split = combo.value.split(sep);
            
            if (split.length == 1) {
                this.value = split[0] + this.decimalSeparator + '00';
            } else if (String(split[1]).length == 1) {
                this.value = split[0] + this.decimalSeparator + split[1] + '0';
            }
            
            this.setRawValue((this.prefix ? this.prefix : '') + this.value + (this.suffix ? this.suffix : ''));
        }
        
        return combo;
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
        if (this.prefix) {
            var regex = new RegExp(this.prefix, 'g');
            value = value.replace(regex, '');
        }
        
        if (this.suffix) {
            var regex = new RegExp(this.suffix, 'g');
            value = value.replace(regex, '');
        }
        
        return Ext.ux.form.NumberField.superclass.validateValue.call(this, value);
    }
});

Ext.reg('extuxnumberfield', Ext.ux.form.NumberField);