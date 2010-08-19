/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/*global Ext, Tine*/

Ext.ns('Tine.Tinebase.widgets.form');

/**
 * @namespace   Tine.Tinebase.widgets.form
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * @class       Tine.Tinebase.widgets.form.RecordPickerComboBox
 * @extends     Ext.form.ComboBox
 * 
 * <p>Abstract base class for recordPickers like account/group pickers </p>
 * 
 * Usage:
 * <pre><code>
var resourcePicker = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
    recordClass: Tine.Calendar.Model.Resource
});
   </code></pre>
 */
Tine.Tinebase.widgets.form.RecordPickerComboBox = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {bool} blurOnSelect
     * blur this combo when record got selected, usefull to be used in editor grids (defaults to false)
     */
    blurOnSelect: false,
    
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * model of record to be picked (required) 
     */
    recordClass: null,
    
    /**
     * @type Tine.Tinebase.data.Record selectedRecord
     * @property selectedRecord 
     * The last record which was selected
     */
    selectedRecord: null,
    
    triggerAction: 'all',
    pageSize: 10,
    minChars: 3,
    forceSelection: true,
    
    initComponent: function () {
        //this.appName = this.model.getMeta('appName');
        //this.modelName = this.model.getMeta('modelName');
        this.displayField = this.recordClass.getMeta('titleProperty');
        this.valueField = this.recordClass.getMeta('idProperty');
        
        this.loadingText = _('Searching...');
        
        this.store = new Tine.Tinebase.data.RecordStore(Ext.copyTo({readOnly: true}, this, 'totalProperty,root,recordClass'));
        
        this.on('beforequery', this.onBeforeQuery, this);
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * prepare paging
     * 
     * @param {Ext.data.Store} store
     * @param {Object} options
     */
    onBeforeLoad: function (store, options) {
        options.params.paging = {
            start: options.params.start,
            limit: options.params.limit
        };
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Object} qevent
     */
    onBeforeQuery: function (qevent) {
        this.store.baseParams.filter = [
            {field: 'query', operator: 'contains', value: qevent.query }
        ];
    },
    
    /**
     * store a copy of the selected record
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Number} index
     */
    onSelect: function (record, index) {
        this.selectedRecord = record;
        return this.supr().onSelect.call(this, record, index);
    },
    
    /**
     * on keypressed("enter") event to add record
     * 
     * @param {Tine.Addressbook.SearchCombo} combo
     * @param {Event} event
     */ 
    onSpecialkey: function (combo, event) {
        if (event.getKey() === event.ENTER) {
            var id = combo.getValue();
            var record = this.store.getById(id);
            this.onSelect(record);
        }
    },
    
    /**
     * set value and prefill store if needed
     * 
     * @param {mixed} value
     */
    setValue: function (value) {
        if (value) {
            if (typeof(value.get) === 'function') {
                // value is a record
                if (this.store.indexOf(value) < 0) {
                    this.store.addSorted(value);
                }
                value = value.get(this.valueField);
                
                
            } else if (value[this.valueField]) {
                // value is a js object
                if (! this.store.getById(value)) {
                    this.store.addSorted(new this.recordClass(value));
                }
                value = value[this.valueField];
            }
        }
        
        return this.supr().setValue.call(this, value);
    }
});
Ext.reg('tinerecordpickercombobox', Tine.Tinebase.widgets.form.RecordPickerComboBox);
