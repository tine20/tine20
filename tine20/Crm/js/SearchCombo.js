/*
 * Tine 2.0
 * Crm combo box and store
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Crm');

/**
 * Lead selection combo box
 * 
 * @namespace   Tine.Crm
 * @class       Tine.Crm.SearchCombo
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.SearchCombo
 */
Tine.Crm.SearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    itemSelector: 'div.search-item',
    minListWidth: 200,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Crm.Model.Lead;
        this.recordProxy = Tine.Crm.recordBackend;
        
        this.initTemplate();
        
        Tine.Crm.SearchCombo.superclass.initComponent.call(this);
        
        this.displayField = 'lead_name';
        
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        Tine.Crm.SearchCombo.superclass.onBeforeQuery.apply(this, arguments);
        var filter = this.store.baseParams.filter;
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '{[this.encode(values)]}',
                '</div></tpl>',
                {
                    encode: function(values) {
                        return Ext.util.Format.htmlEncode(values.lead_name);
                        
                    }
                }
            );
        }
    },
    
    getValue: function() {
            return Tine.Crm.SearchCombo.superclass.getValue.call(this);
    },

    setValue: function (value) {
        return Tine.Crm.SearchCombo.superclass.setValue.call(this, value);
    }

});

Ext.reg('crmleadpickercombobox', Tine.Crm.SearchCombo);
Tine.widgets.form.RecordPickerManager.register('Crm', 'Lead', 'crmleadpickercombobox');