/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Addressbook');

/**
 * the details panel (shows List details)
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListGridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 */
Tine.Addressbook.ListGridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    il8n: null,
    felamimail: false,
    
    /**
     * init
     */
    initComponent: function() {

        // init templates
        this.initTemplate();
        this.initDefaultTemplate();
        
        Tine.Addressbook.ListGridDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        Tine.Addressbook.ListGridDetailsPanel.superclass.afterRender.apply(this, arguments);
    },
    
    /**
     * init default template
     */
    initDefaultTemplate: function() {
        
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-list-nobreak">',    
                '<!-- Preview contacts -->',
                '<div class="preview-panel preview-panel-list-left">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Lists') + '</div>',
                    '<div class="preview-panel-list-leftside preview-panel-left">',
                        '<span class="preview-panel-bold">',
                            this.il8n._('Select list') + '<br/>',
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
     * init single List template (this.tpl)
     */
    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<div class="preview-panel-list-nobreak">',    
                '<!-- Preview contacts -->',
                '<div class="preview-panel preview-panel-list-left">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Lists') + '</div>',
                    '<div class="preview-panel-list-leftside preview-panel-left" style="width: 100%">',
                        '<span class="preview-panel-bold">',
                            this.il8n._('List') + '<br/>',
                        '</span>',
                        '<div style="word-wrap:break-word;">{}</div>',
                    '</div>',
                '</div>',
            '</div>'
        );
    }
});
