/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.ContactSearchCombo
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
 * Create a new Tine.Felamimail.ContactSearchCombo
 */
Tine.Felamimail.ContactSearchCombo = Ext.extend(Tine.Addressbook.SearchCombo, {

    /**
     * @cfg {Boolean} forceSelection
     */
    forceSelection: false,
    
    /**
     * @private
     */ 
    valueIsList: false,

    /**
     * no path filter for emails!
     */
    addPathFilter: false,

    /**
     * @private
     */
    initComponent: function() {
        // Search Lists and Contacts
        this.recordClass = Tine.Addressbook.Model.EmailAddress;
        this.recordProxy = Tine.Addressbook.emailAddressBackend;

        // add additional filter to show only contacts with email addresses
        this.additionalFilters = [
            {field: 'email_query', operator: 'contains', value: '@' }
        ];
        
        this.tpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item">',
                '{[this.encode(values.n_fileas)]}',
                ' (<b>{[this.shorten(this.encode(values.email, values.email_home, values.emails))]}</b>)',
            '</div></tpl>',
            {
                encode: function(email, email_home, emails) {
                    if (email) {
                        return Ext.util.Format.htmlEncode(email);
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
        
        Tine.Felamimail.ContactSearchCombo.superclass.initComponent.call(this);
        
        this.store.on('load', this.onStoreLoad, this);
    },

    /**
     * use beforequery to set query filter
     *
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        Tine.Felamimail.ContactSearchCombo.superclass.onBeforeQuery.apply(this, arguments);

        const filter = this.store.baseParams.filter;
        const queryFilter = _.find(filter, {field: 'query'});
        _.remove(filter, queryFilter);
        filter.push({field: 'name_email_query', operator: 'contains', value: queryFilter.value});
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
            var value = Tine.Felamimail.getEmailStringFromContact(record);
            this.setValue(value);
            this.valueIsList = false;
        } else {
            this.setValue(record.get("emails"));
            this.valueIsList = true;
        }

        this.selectedRecord = record;

        this.collapse();
        this.fireEvent('blur', this);
        this.fireEvent('select', this, record, index);
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
     * @return bool
     */
    getValueIsList: function() {
        return this.valueIsList;
    },

    /**
     * always set valueIsList to false
     *
     * @param String value
     */
    setValue: function(value) {
       this.valueIsList = false;
       Tine.Felamimail.ContactSearchCombo.superclass.setValue.call(this, value); 
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
                recordData = Ext.copyTo({}, record.data, ['email_home', 'n_fileas']);
                newRecord = Tine.Addressbook.contactBackend.recordReader({responseText: Ext.util.JSON.encode(recordData)});
                newRecord.id = Ext.id();
                
                Tine.log.debug('add alternative: ' + Tine.Felamimail.getEmailStringFromContact(newRecord));
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
                return record.id !== contact.id && Tine.Felamimail.getEmailStringFromContact(record) == Tine.Felamimail.getEmailStringFromContact(contact);
            });
            if (duplicates.getCount() > 0) {
                Tine.log.debug('remove duplicate: ' + Tine.Felamimail.getEmailStringFromContact(record));
                store.remove(record);
            }
        });
    }    
});
Ext.reg('felamimailcontactcombo', Tine.Felamimail.ContactSearchCombo);
