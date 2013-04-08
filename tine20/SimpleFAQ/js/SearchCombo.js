/*global Ext, Tine*/
Ext.ns('Tine.SimpleFAQ');
/**
 Tine.SimpleFAQ.SearchCombo use for search without switch to SimpleFAQ modul
 Example usage:
 this.actions_searchFAQ = new Ext.Action({
     requiredGrant: 'readGrant',
     text: this.app.i18n._('Search FAQ'),
     disabled: false,
     handler: Tine.Tinebase.appMgr.get('SimpleFAQ').findQuestion,
     iconCls: '.x-btn-large SimpleFAQIconCls',
     scope: this
 });
 */
/**
 * Lead selection combo box
 * 
 * @namespace   Tine.SimpleFAQ
 * @class       Tine.SimpleFAQ.SearchCombo
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 * 
 * @param       {Object} config
 * @constructor Create a new Tine.SimpleFAQ.SearchCombo
 */
Tine.SimpleFAQ.SearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    /**
     * @cfg
     */
    allowBlank: false,
    itemSelector: 'div.search-item',
    minListWidth: 650,
    resizable: true,
    
    //private
    initComponent: function () {
        this.recordClass = Tine.SimpleFAQ.Model.Faq;
        
        this.initTemplate();
        
        Tine.SimpleFAQ.SearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function () {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td valign="top" width="100%">{[this.encode(values.question)]}</td>',
                        '</tr>',
                    '</table>',
                '</div></tpl>', {
                    encode: function (value) {
                        return Ext.util.Format.htmlEncode(value);
                    }
                }
            );
        }
    }
});

Ext.reg('faqpickercombobox', Tine.SimpleFAQ.SearchCombo);
Tine.widgets.form.RecordPickerManager.register('SimpleFAQ', 'Faq', 'faqpickercombobox');