/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

Ext.ns('Tine.Tinebase.widgets.form');

import{expandFilter} from 'util/filterSpec';

/**
 * @namespace   Tine.Tinebase.widgets.form
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
Tine.Tinebase.widgets.form.RecordPickerComboBox = Ext.extend(Ext.ux.form.ClearableComboBox, {
    /**
     * @cfg {bool} blurOnSelect
     * blur this combo when record got selected, useful to be used in editor grids (defaults to false)
     */
    blurOnSelect: false,
    
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * model of record to be picked (required) 
     */
    recordClass: null,
    
    /**
     * @cfg {Tine.Tinebase.data.RecordProxy} recordProxy
     * record backend 
     */
    recordProxy: null,
    
    /**
     * @property app
     * @type Tine.Tinebase.Application
     */
    app: null,
    
    /**
     * @type Tine.Tinebase.data.Record selectedRecord
     * @property selectedRecord 
     * The last record which was selected
     */
    selectedRecord: null,
    
    /**
     * sort by field
     * 
     * @type String 
     */
    sortBy: null,
    
    /**
     * sort direction
     * 
     * @type String 
     */
    sortDir: 'ASC',
    
    /**
     * @type string
     * @property lastStoreTransactionId
     */
    lastStoreTransactionId: null,
    
    /**
     * if set to false, it is not possible to add the same record handled in this.editDialog
     * this.editDialog must also be set
     * 
     * @cfg {Boolean} allowLinkingItself
     */
    allowLinkingItself: null,
    
    /**
     * the editDialog, the form is nested in. Just needed if this.allowLinkingItself is set to false
     * 
     * @type Tine.widgets.dialog.EditDialog editDialog
     */
    editDialog: null,
    
    /**
     * always use additional filter
     * 
     * @type {Array}
     */
    additionalFilters: null,

    /**
     * config spec for additionalFilters
     *
     * @type: {object} e.g.
     * additionalFilterConfig: {config: { 'name': 'configName', 'appName': 'myApp'}}
     * additionalFilterConfig: {preference: {'appName': 'myApp', 'name': 'preferenceName}}
     * additionalFilterConfig: {favorite: {'appName': 'myApp', 'id': 'favoriteId', 'name': 'optionallyuseaname'}}
     */
    additionalFilterSpec: null,

    triggerAction: 'all',
    pageSize: 50,
    minChars: 3,
    forceSelection: true,
    minListWidth: 300,

    // NOTE: minWidth gets not evaluated by ext - it's just a hint for consumers!
    minWidth: 180,
    
    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        this.displayField = this.recordClass.getMeta('titleProperty');
        this.valueField = this.recordClass.getMeta('idProperty');
        this.disableClearer = ! this.allowBlank;

        this.emptyText = this.emptyText ||
            (this.readOnly || this.disabled ? '' : String.format(i18n._('Search for {0} ...'), this.recordClass.getRecordName()));

        this.loadingText = i18n._('Searching...');

        this.pageSize = parseInt(Tine.Tinebase.registry.get('preferences').get('pageSize'), 10) || this.pageSize;

        this.store = new Tine.Tinebase.data.RecordStore({
            readOnly: true,
            proxy: this.recordProxy || undefined,
            totalProperty: this.totalProperty,
            root: this.root,
            recordClass: this.recordClass
        });
        
        this.on('beforequery', this.onBeforeQuery, this);
        this.store.on('beforeloadrecords', this.onStoreBeforeLoadRecords, this);
        this.initTemplate();

        this.additionalFilters = expandFilter(this.additionalFilterSpec, this.additionalFilters);

        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.initComponent.call(this);
    },
    
    /**
     * respect record.getTitle method
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate('<tpl for="."><div class="x-combo-list-item" ext:qtip="{[this.doubleEncode(values.description)]}">{[this.getTitle(values.' + this.recordClass.getMeta('idProperty') + ')]}</div></tpl>', {
                getTitle: (function(id) {
                    var record = this.getStore().getById(id),
                        title = record ? record.getTitle() : ' ';
                    
                    return Ext.util.Format.htmlEncode(title);
                }).createDelegate(this)
            });
        }
    },

    // TODO re-init this.list if it goes away?
    // NOTE: we sometimes lose this.list (how?). prevent error by checking existence.
    doResize: function(w){
        if(!Ext.isDefined(this.listWidth) && this.list){
            var lw = Math.max(w, this.minListWidth);
            this.list.setWidth(lw);
            this.innerList.setWidth(lw - this.list.getFrameWidth('lr'));
        }
    },

    /**
     * prepare paging and sort
     * 
     * @param {Ext.data.Store} store
     * @param {Object} options
     */
    onBeforeLoad: function (store, options) {
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.onBeforeLoad.call(this, store, options);

        this.lastStoreTransactionId = options.transactionId = Ext.id();

        Ext.apply(options.params, {
            sort: (this.sortBy) ? this.sortBy : this.displayField,
            dir: this.sortDir
        });
    },
    
    /**
     * onStoreBeforeLoadRecords
     * 
     * @param {Object} o
     * @param {Object} options
     * @param {Boolean} success
     * @param {Ext.data.Store} store
     */
    onStoreBeforeLoadRecords: function(o, options, success, store) {
        if (! this.lastStoreTransactionId || options.transactionId && this.lastStoreTransactionId !== options.transactionId) {
            Tine.log.debug('Tine.Tinebase.widgets.form.RecordPickerComboBox::onStoreBeforeLoadRecords cancelling old transaction request.');
            return false;
        }
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Object} qevent
     */
    onBeforeQuery: function (qevent) {
        var filter = [
            {field: 'query', operator: 'contains', value: qevent.query }
        ];
        if (this.additionalFilters !== null && this.additionalFilters.length > 0) {
            for (var i = 0; i < this.additionalFilters.length; i++) {
                filter.push(this.additionalFilters[i]);
            }
        }
        this.store.baseParams.filter = filter;
        this.tpl.lastQuery = qevent.query;
    },

    /**
     * get last usesed query
     *
     * @returns {String}
     */
    getLastQuery: function() {
        return this.lastQuery;
    },

    /**
     * relay contextmenu events
     * 
     * @param {Ext.Container} ct
     * @param {Number} position
     * @private
     */
    onRender : function(ct, position){
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.onRender.call(this, ct, position);
        
        var c = this.getEl();
 
        this.mon(c, {
            scope: this,
            contextmenu: Ext.emptyFn
        });
 
        this.relayEvents(c, ['contextmenu']);
    },
    
    /**
     * store a copy of the selected record
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Number} index
     */
    onSelect: function (record, index) {
        this.selectedRecord = record;
        return Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.onSelect.call(this, record, index);
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
            
            // value is a record
            if (typeof(value.get) === 'function') {
                if (this.store.indexOf(value) < 0) {
                    this.store.addSorted(value);
                }
                value = value.get(this.valueField);
            }
            
            // value is a js object
            else if (Ext.isObject(value)) {
                var record = this.recordProxy ? this.recordProxy.recordReader({responseText: Ext.encode(value)}) : new this.recordClass(value)
                if (! this.store.getById(value.id)) {
                    this.store.addSorted(record);
                }
                value = value[this.valueField] || '';
            }
            
            // value is the current id
            else if (Ext.isPrimitive(value) && value == this.getValue()) {
                return this.setValue(this.selectedRecord);
            }
        }
        
        var r = (value !== "") ? this.findRecord(this.valueField, /* id = */ value) : null,
            text = value,
            description = '';
        
        if (r){
            text = r.getTitle();
            description = r.get('description') || description;
            this.selectedRecord = r;
            if (this.allowLinkingItself === false) {
                // check if editDialog exists
                if (this.editDialog && this.editDialog.record && r.getId() == this.editDialog.record.getId()) {
                    Ext.MessageBox.show({
                        title: i18n._('Failure'),
                        msg: i18n._('You tried to link a record with itself. This is not allowed!'),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR  
                    });
                    return;
                }
            }
            
        } else if (Ext.isDefined(this.valueNotFoundText)){
            text = this.valueNotFoundText;
        }
        this.lastSelectionText = text;
        if (this.hiddenField){
            this.hiddenField.value = Ext.value(value, '');
        }
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.setValue.call(this, text);
        
        var el = this.getEl();
        if (el) {
            el.set({qtip: Tine.Tinebase.common.doubleEncode(description)});
        }
        
        this.value = value;
        return this;
    }
});
Ext.reg('tinerecordpickercombobox', Tine.Tinebase.widgets.form.RecordPickerComboBox);
