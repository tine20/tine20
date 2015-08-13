/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.ContactSearchCombo
 * @extends     Tine.Addressbook.SearchCombo
 *
 * <p>Email Search ComboBox</p>
 * <p></p>
 * <pre></pre>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.ContactSearchCombo
 */
Tine.Expressomail.ContactSearchCombo = Ext.extend(Tine.Addressbook.SearchCombo, {

    /**
     * @cfg {Boolean} forceSelection
     */
    forceSelection: false,

    /**
     * @cfg {Boolean} onlyContacts
     */
    onlyContacts: false,

    /**
     * @private
     */
    valueIsList: false,
    
    /**
     *@cfg {Integer} queryDelay
     */
    queryDelay : 100,
    
    triggerAction: 'query',
    
    pageSize: 0,
    
    minChars: 1,
    
    filterFields: null,

    lazyInit: false,

    
    /**
     * @private
     */
    initComponent: function() {
        // add additional filter to show only contacts with email addresses
        this.additionalFilters = [{field: 'email_query', operator: 'contains', value: '@' }];
              
        this.tpl = new Ext.XTemplate(
             '<tpl for="."><div class="search-item">',
                '{[this.encode(values.n_fn)]}',
                ' (<b>{[this.encode(values.email, values.email_home)]}</b>)',
                '  {[this.encode(values.org_unit)]}',
            '</div></tpl>',
            {
                encode: function(email, email_home, emails) {
                    if (email) {
                        return this.shorten(Ext.util.Format.htmlEncode(email));
                    } else if (email_home) {
                        return Ext.util.Format.htmlEncode(email_home);
                    } else if (emails) {
                        return Ext.util.Format.htmlEncode(emails);
                    } else {
                        return '';
                    }
                },
                shorten: function(text) {
                    if (text) {
                        if (text.length < 50) {
                            return text;
                        } else {
                            return text.substr(0,50) + "...";
                        }
                    } else {
                        return "";
                    }
                }
            }
        );

        Tine.Expressomail.ContactSearchCombo.superclass.initComponent.call(this);

        this.store.on('load', this.onStoreLoad, this);
        
        this.listEmptyText = '<b>'+Tine.Tinebase.appMgr.get('Expressomail').i18n._('No result found in yours personal catalogues.')+'</b>';
    },

    /**
     * override default onSelect
     * - set email/name as value
     *
     * @param {} record
     * @private
     */
    onSelect: function(record, index) {
        if (!record.get("emails")) {
            var value = Tine.Expressomail.getEmailStringFromContact(record);
            this.setValue(value);
            this.valueIsList = false;
        } else {
            this.setValue(record.get("emails"));
            this.valueIsList = true;
        }

        this.collapse();
        this.fireEvent('blur', this);
        this.fireEvent('select', this, record, index);
    },

    /**
     * @return bool
     */
    getValueIsList: function() {
        return this.valueIsList;
    },

    /**
     * always return raw value
     *
     * @return String
     */
    getValue: function() {
        return this.getRawValue();
    },

    /**
     * always set valueIsList to false
     *
     * @param String value
     */
    setValue: function(value) {
       this.valueIsList = false;
       if(value !== undefined)
           Tine.Expressomail.ContactSearchCombo.superclass.setValue.call(this, value);
    },
 
    /**
     * on load handler of combo store
     * -> add additional record if contact has multiple email addresses
     *
     * @param {} store
     * @param {} records
     * @param {} options
     */
    onStoreLoad: function(store, records, options) {
        this.addAlternativeEmail(store, records);
        this.removeDuplicates(store);
    },
            
    /**
     * add alternative email addresses
     *
     * @param {} store
     * @param {} records
     */
    addAlternativeEmail: function(store, records) {
        var index = 0,
            newRecord,
            recordData;

        Ext.each(records, function(record) {
            if (record.get('email') && record.get('email_home') && record.get('email') !== record.get('email_home')) {
                index++;
                recordData = Ext.copyTo({}, record.data, ['email_home', 'n_fn']);
                newRecord = Tine.Addressbook.contactBackend.recordReader({responseText: Ext.util.JSON.encode(recordData)});
                newRecord.id = Ext.id();

                Tine.log.debug('add alternative: ' + Tine.Expressomail.getEmailStringFromContact(newRecord));
                store.insert(index, [newRecord]);
            }
            index++;
        });
    },

    /**
     * remove duplicate contacts
     *
     * @param {} store
     */
    removeDuplicates: function(store) {
        var duplicates = null;

        store.each(function(record) {
            duplicates = store.queryBy(function(contact) {
                return record.id !== contact.id && Tine.Expressomail.getEmailStringFromContact(record) == Tine.Expressomail.getEmailStringFromContact(contact);
            });
            if (duplicates.getCount() > 0) {
                Tine.log.debug('remove duplicate: ' + Tine.Expressomail.getEmailStringFromContact(record));
                store.remove(record);
            }
        });
    },
    
    /**
     * Execute a query to filter the dropdown list.  Fires the {@link #beforequery} event prior to performing the
     * query allowing the query action to be canceled if needed.
     * @param {String} q query The SQL query to execute
     * @param {Boolean} forceAll <tt>true</tt> to force the query to execute even if there are currently fewer
     * characters in the field than the minimum specified by the <tt>{@link #minChars}</tt> config option.  It
     * also clears any filter previously saved in the current store (defaults to <tt>false</tt>)
     */
    doQuery : function (q, forceAll) {
        q = Ext.isEmpty(q) ? '' : q;
        var qe = {
            query: q,
            forceAll: forceAll,
            combo: this,
            cancel:false
        };
        if(this.fireEvent('beforequery', qe)===false || qe.cancel){
            return false;
        }
        q = qe.query;
        forceAll = qe.forceAll;
        if(forceAll === true || (q.length >= this.minChars)){
            if(this.lastQuery !== q){
                this.lastQuery = q;
                if(this.mode == 'local'){
                    this.selectedIndex = -1;
                    if(forceAll){
                        this.store.clearFilter();
                    }else{
                        this.store.filter(this.filterFields, q, true);
                    }
                    this.onLoad();
                }else{
                    this.store.baseParams[this.queryParam] = q;
                    this.store.load({
                        params: this.getParams(q)
                    });
                    this.expand();
                }
            }else{
                this.selectedIndex = -1;
                this.onLoad();
            }
        }
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Object} qevent
     */
    onBeforeQuery: function (qevent) {}
});
Ext.reg('expressomailcontactcombo', Tine.Expressomail.ContactSearchCombo);
