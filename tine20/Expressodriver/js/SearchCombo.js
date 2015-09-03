/*
 * Tine 2.0
 * Expressodriver combo box and store
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

Ext.ns('Tine.Expressodriver');

/**
 * Node selection combo box
 *
 * @namespace   Tine.Expressodriver
 * @class       Tine.Expressodriver.SearchCombo
 * @extends     Ext.form.ComboBox
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressodriver.SearchCombo
 */
Tine.Expressodriver.SearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {

    allowBlank: false,
    itemSelector: 'div.search-item',
    minListWidth: 200,

    /**
     * init component
     * @private
     */
    initComponent: function(){
        this.recordClass = Tine.Expressodriver.Model.Node;
        this.recordProxy = Tine.Expressodriver.recordBackend;
        this.additionalFilters = [
            {field: 'recursive', operator: 'equals', value: true },
            {field: 'path', operator: 'equals', value: '/' }
        ];
        this.initTemplate();
        Tine.Expressodriver.SearchCombo.superclass.initComponent.call(this);
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
            return Tine.Expressodriver.SearchCombo.superclass.getValue.call(this);
    },

    setValue: function (value) {
        return Tine.Expressodriver.SearchCombo.superclass.setValue.call(this, value);
    }

});
/**
 * register search combo
 */
Tine.widgets.form.RecordPickerManager.register('Expressodriver', 'Node', Tine.Expressodriver.SearchCombo);
