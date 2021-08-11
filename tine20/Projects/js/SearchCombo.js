/*
 * Tine 2.0
 * Projects combo box and store
 * 
 * @package     Projects
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Projects');

/**
 * Project selection combo box
 * 
 * @namespace   Tine.Projects
 * @class       Tine.Projects.SearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * <p>Project Search Combobox</p>
 * <p><pre>
 * TODO         make this a twin trigger field with 'clear' button?
 * TODO         add switch to filter for expired/enabled/disabled user accounts
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Projects.SearchCombo
 * 
 * TODO         add     forceSelection: true ?
 */
Tine.Projects.SearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    itemSelector: 'div.search-item',
    minListWidth: 200,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Projects.Model.Project;
        this.recordProxy = Tine.Projects.recordBackend;
        
        this.initTemplate();
        
        Tine.Projects.SearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        Tine.Projects.SearchCombo.superclass.onBeforeQuery.apply(this, arguments);
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
                            '<td style="height:16px">{[this.encode(values)]}</td>',
                        '</tr>',
                    '</table>',
                '</div></tpl>',
                {
                    encode: function(values) {
                        var ret = '<b>' + Ext.util.Format.htmlEncode(values.title) + '</b>';
                        if(values.number) ret += ' (' + values.number + ')';
                        return ret;
                        
                    }
                }
            );
        }
    },
    
    getValue: function() {
            return Tine.Projects.SearchCombo.superclass.getValue.call(this);
    },

    setValue: function (value) {
        return Tine.Projects.SearchCombo.superclass.setValue.call(this, value);
    }

});

Tine.widgets.form.RecordPickerManager.register('Projects', 'Project', Tine.Projects.SearchCombo);
