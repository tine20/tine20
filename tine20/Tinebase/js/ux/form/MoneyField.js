/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.MoneyField
 * @extends     Ext.form.MoneyField
 */
Ext.ux.form.MoneyField = Ext.extend(Ext.ux.form.NumberField, {

    style: 'text-align: right',

   initComponent: function() {
        this.suffix = ' ' + Tine.Tinebase.registry.get('currencySymbol');
        this.decimalPrecision = 2;
        this.decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator');

        this.supr().initComponent.apply(this, arguments);
    }
});

Ext.reg('extuxmoneyfield', Ext.ux.form.MoneyField);