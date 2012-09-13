/*
 * Tine 2.0
 * Filemanager combo box and store
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Filemanager');

/**
 * Node selection combo box
 * 
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.SearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Filemanager.SearchCombo
 */
Tine.Filemanager.SearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    itemSelector: 'div.search-item',
    minListWidth: 200,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Filemanager.Model.Node;
        this.recordProxy = Tine.Filemanager.recordBackend;
        
        this.initTemplate();
        Tine.Filemanager.SearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent) {
        Tine.Filemanager.SearchCombo.superclass.onBeforeQuery.apply(this, arguments);
        this.store.baseParams.filter.push(
            {field: 'recursive', operator: 'equals', value: true }
        );
        this.store.baseParams.filter.push(
            {field: 'path', operator: 'equals', value: '/' }
        );
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function() {
        // Custom rendering Template
        // TODO move style def to css ?
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td ext:qtip="{[this.renderPathName(values)]}" style="height:16px">{[this.renderFileName(values)]}</td>',
                        '</tr>',
                    '</table>',
                '</div></tpl>',
                {
                    renderFileName: function(values) {
                        return Ext.util.Format.htmlEncode(values.name);
                    },
                    renderPathName: function(values) {
                        return Ext.util.Format.htmlEncode(values.path.replace(values.name, ''));
                    }
                    
                }
            );
        }
    },
    
    getValue: function() {
            return Tine.Filemanager.SearchCombo.superclass.getValue.call(this);
    },

    setValue: function (value) {
        return Tine.Filemanager.SearchCombo.superclass.setValue.call(this, value);
    }

});

Tine.widgets.form.RecordPickerManager.register('Filemanager', 'Node', Tine.Filemanager.SearchCombo);
