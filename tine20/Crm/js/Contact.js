/**
 * Tine 2.0
 * contacts combo box and store
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo translate
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.Contact');

/**
 * get contact store
 * if available, load data from Tine.Crm.Contacts
 *
 * @return Ext.data.JsonStore with contacts
 */
Tine.Crm.Contact.getStore = function() {
    var store = Ext.StoreMgr.get('CrmContactStore');
    if (!store) {

        var contactFields = Tine.Addressbook.Model.ContactArray;
        contactFields.push({name: 'relation'});   // the relation object           
        contactFields.push({name: 'relation_type'});
            
        // create store
        store = new Ext.data.JsonStore({
            //fields: Tine.Addressbook.Model.Contact,
        	fields: contactFields,
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

        // prepare filter
        // @todo get paging / value from combo
        store.on('beforeload', function(store, options){
            if (!options.params) {
                options.params = {};
            }

            options.params.paging = Ext.util.JSON.encode({
                start: 0,
                limit: 10,
                sort: 'n_family',
                dir: 'ASC'
            });
        	
            var contactsCombo = Ext.getCmp('contactSearchCombo');
            
            //console.log(options);            
            //console.log(contactsCombo);
            //console.log(options.params);
            //console.log(options.params.query);
            //console.log(Ext.getCmp('contactSearchCombo').getValue());
            
            var filter = [
                {field: 'containerType', operator: 'equals', value: 'all' },
                //{field: 'query', operator: 'contains', value: Ext.getCmp('contactSearchCombo').getValue() }
                {field: 'query', operator: 'contains', value: options.params.query }
            ];
            
            options.params.filter = Ext.util.JSON.encode(filter);
            
        }, this);

        Ext.StoreMgr.add('CrmContactStore', store);
    }
    return store;
};

/**
 * contact selection combo box
 * 
 */
Tine.Crm.Contact.ComboBox = Ext.extend(Ext.form.ComboBox, {

	id: 'contactSearchCombo',
	
	//name: 'contact_combo',
    valueField: 'id',
    typeAhead: false,
    loadingText: 'Searching...',
    hideTrigger: true,
    pageSize: 10,
    itemSelector: 'div.search-item',
    store: null,

    /*
    applyTo: 'search',
    hiddenName: 'id',
    lazyRender: true,
    displayField:'n_family',
    allowBlank: false, 
    forceSelection: true, 
    selectOnFocus: true,
    listClass: 'x-combo-list-small',
    triggerAction: 'all', 
    editable: true,
    mode: 'local', 
    */
    
    //private
    initComponent: function(){
    	
        // Custom rendering Template
    	// @todo style that
        var resultTpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item">',
                '<h3>{n_fn}</h3><br/>',
                '{org_name}',
            '</div></tpl>'
                // <span>{adr_one_street} {adr_one_postalcode} {adr_one_locality}</span>
        );
        
        this.tpl = resultTpl;

        this.store = Tine.Crm.Contact.getStore();
        
        Tine.Crm.Contact.ComboBox.superclass.initComponent.call(this);        
    },
    
    /**
     * override default onSelect
     * 
     * @todo make it work with 'ENTER' key
     */
    onSelect: function(record){ // 
    	
        record.data.relation_type = 'customer';            
        var store = Ext.StoreMgr.lookup('ContactsStore');
        store.add([record]);

        this.collapse();
    }
        
    /*
        store: ds,
        displayField:'title',
        typeAhead: false,
        loadingText: 'Searching...',
        width: 570,
        pageSize:10,
        hideTrigger:true,
        tpl: resultTpl,
        applyTo: 'search',
        itemSelector: 'div.search-item',
        onSelect: function(record){ // override default onSelect to do redirect
            window.location =
                String.format('http://extjs.com/forum/showthread.php?t={0}&p={1}', record.data.topicId, record.id);
        }
    */
});

/**
 * contact renderer
 * 
 * @todo use that or tpl?
 */
Tine.Crm.Contact.renderer = function(data) {
                                                
    record = Tine.Crm.Contact.getStore().getById(data);
    
    if (record) {
        //return record.data.value;
        return Ext.util.Format.htmlEncode(record.data.productsource);
    }
    else {
        Ext.getCmp('leadDialog').doLayout();
        return Ext.util.Format.htmlEncode(data);
    }
};
