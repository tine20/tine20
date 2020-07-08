/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.CustomerDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 * 
 * <p>Customer details Panel</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.CustomerDetailsPanel
 */
Tine.Sales.CustomerDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    /**
     * init
     */
    initComponent: function() {

        // init templates
        this.initTemplate();
        this.initDefaultTemplate();
        
        Tine.Sales.CustomerDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        Tine.Sales.CustomerDetailsPanel.superclass.afterRender.apply(this, arguments);
        
        if (this.felamimail === true) {
            this.body.on('click', this.onClick, this);
        }
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
                '<div class="preview-panel preview-panel-timesheet-left">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.app.i18n.n_hidden('Customer', 'Customers', 3) + '</div>',
                    '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                        '<span class="preview-panel-bold">',
                            this.app.i18n._('Select customer') + '<br/>',
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
                '<!-- Preview xxx -->',
                '<div class="preview-panel-timesheet-right">',
                    '<div class="bordercorner_gray_1"></div>',
                    '<div class="bordercorner_gray_2"></div>',
                    '<div class="bordercorner_gray_3"></div>',
                    '<div class="bordercorner_gray_4"></div>',
                    '<div class="preview-panel-declaration"></div>',
                    '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                        '<span class="preview-panel-bold">',
                            '<br/>',
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
        this.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<!-- Preview core data -->',
                '<div class="preview-panel preview-panel-left preview-panel-customer">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.app.i18n._('Core Data') + '</div>',
                    '<div class="preview-panel-left">',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Customer Number') + '</span>{[this.encode(values.number)]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Name') + '</span>{[this.encode(values.name)]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Web') + '</span><a href="{[this.encode(values.url, "text")]}" target="_blank">{[this.encode(values.url, "text")]}</a><br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Contact Person (external)') + '</span>{[this.encode(values.cpextern_id, "address")]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Contact Person (internal)') + '</span>{[this.encode(values.cpintern_id, "address")]}<br/>',
                        
                        
                    '</div>',
                '</div>',

                '<!-- Preview accounting data -->',
                '<div class="preview-panel preview-panel-left preview-panel-customer">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.app.i18n._('Accounting') + '</div>',
                    '<div>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('IBAN') + '</span>{[this.encode(values.iban)]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('BIC') + '</span>{[this.encode(values.bic)]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('VAT ID') + '</span>{[this.encode(values.vatid)]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Currency') + '</span>{[this.encode(values.currency)]}<br/>',
                        '<span class="preview-panel-symbolcompare wide">' + this.app.i18n._('Currency Translation Rate') + '</span>{[this.encode(values.currency_trans_rate)]}<br/>',
                    '</div>',
                '</div>',
            
                '<!-- Preview description -->',
                '<div class="preview-panel-description preview-panel-left preview-panel-customer">',
                    '<div class="bordercorner_gray_1"></div>',
                    '<div class="bordercorner_gray_2"></div>',
                    '<div class="bordercorner_gray_3"></div>',
                    '<div class="bordercorner_gray_4"></div>',
                    '<div class="preview-panel-declaration">' + i18n._('Description') + '</div>',
                    '{[this.encode(values.description)]}',
                '</div>',
            '</tpl>',
            {
                /**
                 * encode
                 */
                encode: function(value, type, prefix) {
                    if (! value) {
                        return '';
                    }
                    type = type ? type : 'text';
                    switch (type) {
                        case 'text':
                            var ret = value;
                            break;
                        case 'address':
                            var ret = value.n_fn
                            break;
                    }
                    return Ext.util.Format.htmlEncode(ret);
                }
            }
        );
    }
});