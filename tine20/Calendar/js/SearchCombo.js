/*
 * Tine 2.0
 * event combo box and store
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Calendar');

/**
 * event selection combo box
 * 
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.SearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * <p>Event Search Combobox</p>
 * <p><pre>
 * TODO         make this a twin trigger field with 'clear' button?
 * TODO         add switch to filter for expired/enabled/disabled user accounts
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Calendar.SearchCombo
 * 
 * TODO         add     forceSelection: true ?
 */
Tine.Calendar.SearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
     
    itemSelector: 'div.search-item',
    minListWidth: 200,
    width: 240,
    hideLabel: true,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Calendar.Model.Event;
        this.recordProxy = Tine.Calendar.eventBackend;
        
        this.initTemplate();
        
        Tine.Calendar.SearchCombo.superclass.initComponent.call(this);
        
    },
     
    /**
     * init template
     * @private
     */
    initTemplate: function() {

        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item" style="border:1px solid white">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td><b>{[this.encode(values.summary)]}</b></td>',
                        '</tr>',
                    '</table>',
                '</div></tpl>',
                {
                    encode: function(value) {                
                        if (value) {
                            return Ext.util.Format.htmlEncode(value);
                        } else {
                            return '';
                        }
                    }
                }
            );
        }
    },
    
    /**
     * overwrite
     */
    onBeforeQuery: function () {
        
    },

    /**
     * sets the filter
     * @param {} filter
     */
    setFilter: function(filter) {
        this.store.baseParams.filter = [filter];
        this.fireEvent('filterupdate');
    }


});
