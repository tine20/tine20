/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.InvoiceDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 * 
 * <p>Invoice details Panel</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.InvoiceDetailsPanel
 */
Tine.Sales.InvoiceDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    defaultHeight: 145,
    
    /**
     * init
     */
    initComponent: function() {

        // init templates
        this.initTemplate();
        this.initDefaultTemplate();
        
        Tine.Sales.InvoiceDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        Tine.Sales.InvoiceDetailsPanel.superclass.afterRender.apply(this, arguments);
    },
    
    /**
     * init default template
     * 
     * @todo: generalize this
     */
    initDefaultTemplate: function() {
        
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-timesheet-nobreak">',    
                '<!-- Preview contacts -->',
                '<div class="preview-panel preview-panel-invoice-default">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.app.i18n.n_hidden('Invoice', 'Invoices', 3) + '</div>',
                    '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                        '<span class="preview-panel-bold">',
                            this.app.i18n._('Select invoice') + '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                        '</span>',
                    '</div>',
                    '<div class="preview-panel-timesheet-rightside preview-panel-left">',
                        '<span class="preview-panel-nonbold">',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                        '</span>',
                    '</div>',
                '</div>',
            '</div>'
        );
    },
    
    /**
     * init single contact template (this.tpl)
     */
    initTemplate: function() {
        var that = this;
        this.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<!-- Preview core data -->',
                '<div class="preview-panel preview-panel-invoice-left preview-panel-customer">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.app.i18n._('Invoice') + '</div>',
                    '<div class="preview-panel-left">',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Invoice Number') + '</span>{[this.encode(values, "number")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Date') + '</span>{[this.encode(values, "date")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Type') + '</span>{[this.encode(values, "type")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Description') + '</span>{[this.encode(values, "description")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Customer') + '</span>{[this.encode(values, "customer")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Contract') + '</span>{[this.encode(values, "contract")]}<br/>',
                    '</div>',
                '</div>',

                '<!-- Preview accounting data -->',
                '<div class="preview-panel preview-panel-invoice-left preview-panel-customer">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.app.i18n._('Billing Address') + '</div>',
                    '<div>',
                        '{[this.encode(values, "address")]}<br/>',
                    '</div>',
                '</div>',
            
                '<!-- Preview description -->',
                '<div class="preview-panel preview-panel-invoice-left preview-panel-customer">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.app.i18n._('Miscellaneous') + '</div>',
                    '<div class="preview-panel-left">',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Credit Term') + '</span>{[this.encode(values, "credit_term")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n.n_('Cost Center', 'Cost Centers', 1) + '</span>{[this.encode(values, "costcenter_id")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Cleared') + '</span>{[this.encode(values, "cleared")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Price Net') + '</span>{[this.encode(values, "price_net")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Price Tax') + ' (' + '{[this.encode(values, "sales_tax")]}' + ')' + '</span>{[this.encode(values, "price_tax")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Price Gross') + '</span>{[this.encode(values, "price_gross")]}<br/>',
                    '</div>',
                '</div>',
            '</tpl>',
            {
                /**
                 * encode
                 */
                encode: function(value, key) {
                    switch (key) {
                        case 'address':
                            if (value.fixed_address) {
                                return Ext.util.Format.nl2br(value.fixed_address);
                            } else if (value.address_id) {
                                var address = new Tine.Sales.Model.Address(value.address_id);
                                return Ext.util.Format.nl2br(Tine.Sales.renderAddress(address));
                            } else {
                                return '';
                            }
                        case 'price_gross':
                            return Ext.util.Format.money(value.price_gross);
                        case 'price_net':
                            return Ext.util.Format.money(value.price_net);
                        case 'price_tax':
                            return Ext.util.Format.money(value.price_tax);
                    }

                    var renderer = Tine.widgets.grid.RendererManager.get('Sales', 'Invoice', key);
                    if (renderer) {
                        return renderer(value[key], key, that.record);
                    } else {
                        return '';
                    }
                }
            }
        );
    }
});
