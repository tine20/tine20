/*
 * Tine 2.0
 * Sales combo box and store
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * CostCenter selection combo box
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.CostCenterSearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.CostCenterSearchCombo
 */
Tine.Sales.CostCenterSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    itemSelector: 'div.search-item',
    minListWidth: 200,
    sortBy: 'number',
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Sales.Model.CostCenter;
        this.recordProxy = Tine.Sales.costcenterBackend;
        this.initTemplate();
        
        Tine.Sales.CostCenterSearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function() {
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
                        var ret = '';
                        if(values.number) ret += '<b>' + Ext.util.Format.htmlEncode(values.number) + '</b> - ';
                        ret += Ext.util.Format.htmlEncode(values.remark);
                        return ret;
                        
                    }
                }
            );
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'CostCenter', Tine.Sales.CostCenterSearchCombo);
