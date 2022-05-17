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
    
    recordEditPluginConfig: false,
    additionalFilterSpec: {},
    
    /**
     * @private
     */
    initComponent: function() {
        // Search Lists and Contacts
        this.recordClass = Tine.Addressbook.Model.EmailAddress;
        this.recordProxy = Tine.Addressbook.emailAddressBackend;
        
        this.tpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item">',
            '{[this.getIcon(values)]}',
            '<span style="padding-left: 5px;">',
            '{[this.encode(values, "name")]}',
            ' <b>{[this.shorten(this.encode(values, "email"))]}</b>',
            '</span>',
            '</div></tpl>',
            {
                encode: function(values, field) {
                    let value = _.get(values, field) ?? '';
                    
                    if (field === 'email' && value !== '') {
                        value = `( ${value} )`;
                    }
                    
                    return Ext.util.Format.htmlEncode(value);
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
                },
                getIcon: this.resolveAddressIconCls.createDelegate(this)
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
    onBeforeQuery: function (qevent) {
        Tine.Felamimail.ContactSearchCombo.superclass.onBeforeQuery.apply(this, arguments);
    
        const filter = this.store.baseParams.filter;
        const queryFilter = _.find(filter, {field: 'query'});
        _.remove(filter, queryFilter);

        filter.push({field: 'name_email_query', operator: 'contains', value: queryFilter.value});
    },

    doQuery : function(q, forceAll){
        // always load store otherwise the recipients will not be updated
        this.store.load({
            params: this.getParams(q)
        });
  
        Tine.Felamimail.ContactSearchCombo.superclass.doQuery.apply(this, arguments);
    },
    
    /**
     * override default onSelect
     * - set email/name as value
     * 
     * @param {} record
     * @private
     */
    onSelect: function(record, index) {
        this.selectedRecord = record;
        this.value = this.getValue();
        this.collapse();
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
     * always set valueIsList to false
     *
     * @param value
     */
    setValue: function(value) {
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
        this.removeDuplicates(store);
    },
    
    resolveAddressIconCls: function(values) {
        let type = values?.type ?? '';
        let iconClass = 'EmailAccount';
        let tip = Ext.util.Format.capitalize(type);
        const i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;
        
        switch (type) {
            case 'user':
                tip = 'Contact of a user account'
                iconClass = 'Account';
                break;
            case 'mailingListMember':
                tip = 'Mailing List Member';
                iconClass = 'Account';
                break;
            case 'responsible':
                iconClass = 'Contact';
                break;
            case 'mailingList':
                tip = 'Mailing List';
                iconClass = 'MailingList';
                break;
            case 'email_account':
                iconClass = 'EmailAccount';
                break;
            case 'email_home':
                iconClass = 'Private';
                break;
            case 'group':
                tip = 'System Group';
                iconClass = 'Group';
                break;
            case 'list':
                tip = 'Group';
                iconClass = 'List';
                break;
            case 'groupMember':
            case 'listMember':
                tip = 'Group Member';
                iconClass = 'GroupMember';
                break;
            default :
                if (type === '') {
                    tip = 'E-Mail';
                    iconClass = 'EmailAccount';
                    if (values.record_id !== '') {
                        tip = 'Contact';
                        iconClass = 'Contact';
                    }
                }
                break;
        }
    
        if (values?.email_type === 'email_home') {
            iconClass = 'Private';
            tip = 'Email (private)';
        }
        
        return '<div class="tine-combo-icon renderer AddressbookIconCls renderer_type' + iconClass + 'Icon" ext:qtip="' 
            + Ext.util.Format.htmlEncode(i18n._(tip)) + '"/></div>';
    },
    
    /**
     * remove duplicate contacts
     * 
     * @param {} store
     */
    removeDuplicates: function(store) {
        let duplicates = null;
        
        store.each(function(record) {
            duplicates = store.queryBy(function(contact) {
                return record.id !== contact.id && Tine.Felamimail.getEmailStringFromContact(record) === Tine.Felamimail.getEmailStringFromContact(contact);
            });
            if (duplicates.getCount() > 0) {
                Tine.log.debug('remove duplicate: ' + Tine.Felamimail.getEmailStringFromContact(record));
                store.remove(record);
            }
        });
    
        // only remove duplicated email addresses with type mailingList ,
        store.each(function(record) {
            if (record.data.emails !== '' && record.data.type === 'useAsMailinglist') {
                const idx = store.indexOf(record);
                let emailArray = _.compact(_.split(record.data.emails, ','));

                duplicates = store.queryBy(function (contact) {
                    if (contact.data.email !== '' ) {
                        return record.id !== contact.id && _.includes(emailArray, contact.data.email);
                    }
                });

                emailArray = _.difference(emailArray, _.map(duplicates.items, 'data.email'));
                record.data.emails = _.join(emailArray, ',');
                Tine.log.debug('remove duplicate email from mailing list: ' + Tine.Felamimail.getEmailStringFromContact(record));
                store.removeAt(idx);
                
                if (emailArray.length > 0) {
                    store.insert(idx, record);
                }
            }
        });
    }
});
Ext.reg('felamimailcontactcombo', Tine.Felamimail.ContactSearchCombo);
