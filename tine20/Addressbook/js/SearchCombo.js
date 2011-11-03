/*
 * Tine 2.0
 * contacts combo box and store
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Addressbook');

/**
 * contact selection combo box
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.SearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * <p>Contact Search Combobox</p>
 * <p><pre>
 * TODO         make this a twin trigger field with 'clear' button?
 * TODO         add switch to filter for expired/enabled/disabled user accounts
 * TODO         extend Tine.Tinebase.widgets.form.RecordPickerComboBox
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.SearchCombo
 * 
 * TODO         add     forceSelection: true ?
 */
Tine.Addressbook.SearchCombo = Ext.extend(Ext.form.ComboBox, {

    /**
     * combobox cfg
     * @private
     */
    typeAhead: false,
    triggerAction: 'all',
    pageSize: 10,
    itemSelector: 'div.search-item',
    store: null,
    minChars: 3,
    
    /**
     * @cfg {Boolean} blurOnSelect
     */
    blurOnSelect: false,
    
    /**
     * @cfg {Boolean} userOnly
     */
    userOnly: false,
    
    /**
     * @property additionalFilters
     * @type Array
     */
    additionalFilters: null,
    
    /**
     * @property selectedRecord
     * @type Tine.Addressbook.Model.Contact
     */
    selectedRecord: null,
    
    /**
     * @cfg {String} nameField
     */
    nameField: 'n_fn',
    
    /**
     * use account objects/records in get/setValue
     * 
     * @cfg {Boolean} legacy
     * @legacy
     * 
     * TODO remove this later
     */
    useAccountRecord: false,
    
    /**
     * @property lastStoreTransactionId 
     * @type String
     */
    lastStoreTransactionId: null,
    
    //private
    initComponent: function(){
        
        this.loadingText = _('Searching...');
    	
        this.initTemplate();
        this.initStore();
        
        Tine.Addressbook.SearchCombo.superclass.initComponent.call(this);        

        this.on('beforequery', this.onBeforeQuery, this);
    },
    
    /**
     * onRender
     * 
     * @param {} ct
     * @param {} position
     * @private
     */
    onRender : function(ct, position){
        Tine.Addressbook.SearchCombo.superclass.onRender.call(this, ct, position);
        
        var c = this.getEl();
 
        this.mon(c, {
            scope: this,
            contextmenu: Ext.emptyFn
        });
 
        this.relayEvents(c, ['contextmenu']);        
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        // define a transaction
        this.lastStoreTransactionId = options.transactionId = Ext.id();
        
        // prepare filter / get paging from combo
        options.params.paging = {
            start: options.params.start,
            limit: options.params.limit,
            sort: 'n_family',
            dir: 'ASC'
        };
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
            Tine.log.debug('cancelling old transaction request.');
            return false;
        }
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        var filter = [
            {field: 'query', operator: 'contains', value: qevent.query }
        ];
        
        if (this.userOnly) {
            filter.push({field: 'type', operator: 'equals', value: 'user'});
        }
        
        if (this.additionalFilters !== null && this.additionalFilters.length > 0) {
            for (var i = 0; i < this.additionalFilters.length; i++) {
                filter.push(this.additionalFilters[i]);
            }
        }
        
        this.store.baseParams.filter = filter;
    },
    
    /**
     * on select handler
     * - this needs to be overwritten in most cases
     * 
     * @param {Tine.Addressbook.Model.Contact} record
     */
    onSelect: function(record){
        this.selectedRecord = record;
        this.setValue(record.get(this.nameField));
        this.collapse();
        
        this.fireEvent('select', this, record);
        if (this.blurOnSelect) {
            this.fireEvent('blur', this);
        }
    },
    
    /**
     * on keypressed("enter") event to add record
     * 
     * @param {Tine.Addressbook.SearchCombo} combo
     * @param {Event} event
     */ 
    onSpecialkey: function(combo, event){
        if(event.getKey() == event.ENTER){
         	var id = combo.getValue();
            var record = this.store.getById(id);
            this.onSelect(record);
        }
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
                            '<td width="30%"><b>{[this.encode(values.n_fileas)]}</b><br/>{[this.encode(values.org_name)]}</td>',
                            '<td width="25%">{[this.encode(values.adr_one_street)]}<br/>',
                                '{[this.encode(values.adr_one_postalcode)]} {[this.encode(values.adr_one_locality)]}</td>',
                            '<td width="25%">{[this.encode(values.tel_work)]}<br/>{[this.encode(values.tel_cell)]}</td>',
                            '<td width="20%">',
                                '<img width="45px" height="39px" src="{jpegphoto}" />',
                            '</td>',
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
    
    getValue: function() {
        if (this.useAccountRecord) {
            if (this.selectedRecord) {
                return this.selectedRecord.get('account_id');
            } else {
                return this.accountId;
            }
        } else {
            return Tine.Addressbook.SearchCombo.superclass.getValue.call(this);
        }
    },

    setValue: function (value) {
    	
        if (this.useAccountRecord) {
            if (value) {
                if(value.accountId) {
                    // account object
                    this.accountId = value.accountId;
                    value = value.accountDisplayName;
                } else if (typeof(value.get) == 'function') {
                    // account record
                    this.accountId = value.get('id');
                    value = value.get('name');
                }
            } else {
                this.accountId = null;
            }
        } else {
            // value is a record
             if (value && typeof(value.get) === 'function') {
                if (this.store.indexOf(value) < 0) {
                    this.store.addSorted(value);
                }
                value = value.get(this.nameField);
            }
            
            // value is a js object
            else if (value && value[this.nameField]) {
                if (! this.store.getById(value)) {
                    this.store.addSorted(new Tine.Addressbook.Model.Contact(value));
                }
                value = value[this.nameField];
            }
            
            return Tine.Tinebase.widgets.form.RecordPickerComboBox.prototype.setValue.call(this, value);
        }
        
        return Tine.Addressbook.SearchCombo.superclass.setValue.call(this, value);
    },
    
    /**
     * get contact store
     *
     * @return Ext.data.JsonStore with contacts
     * @private
     */
    initStore: function() {
        
        if (! this.store) {
            
            if (! this.contactFields) {
                this.contactFields = Tine.Addressbook.Model.ContactArray;
            }
            
            // create store
            this.store = new Ext.data.JsonStore({
                //fields: Tine.Addressbook.Model.Contact,
                fields: this.contactFields,
                baseParams: {
                    method: 'Addressbook.searchContacts'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                remoteSort: true,
                sortInfo: {
                    field: 'n_family',
                    direction: 'ASC'
                }            
            });
            
            this.store.on('beforeload', this.onStoreBeforeload, this);
            this.store.on('beforeloadrecords', this.onStoreBeforeLoadRecords, this);
        }
        
        return this.store;
    }
});
