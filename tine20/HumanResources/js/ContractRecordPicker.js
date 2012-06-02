
/*
 * Tine 2.0
 * HumanResources combo box and store
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.HumanResources');

/**
 * Contract selection combo box
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.ContractRecordPicker
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.ContractRecordPicker
 */
Tine.HumanResources.ContractRecordPicker = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    itemSelector: 'div.search-item',
    minListWidth: 200,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.HumanResources.Model.Contract;
        this.recordProxy = Tine.HumanResources.recordBackend;
        this.initTemplate();
        Tine.HumanResources.ContractRecordPicker.superclass.initComponent.call(this);
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
                        var value = '<b>' + Ext.util.Format.htmlEncode(values.workingtime_id.title) + '</b>  ' + Tine.Tinebase.common.dateRenderer(values.start_date) + ' - ' + Tine.Tinebase.common.dateRenderer(values.end_date);
                        return value;
                    }
                }
            );
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('HumanResources', 'Contract', Tine.HumanResources.ContractRecordPicker);