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

        // prepare filter / get paging from combo
        store.on('beforeload', function(store, options){
            options.params.paging = Ext.util.JSON.encode({
                start: options.params.start,
                limit: options.params.limit,
                sort: 'n_family',
                dir: 'ASC'
            });
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
    minChars: 3,

    //private
    initComponent: function(){
    	
        // Custom rendering Template
    	// @todo move style def to css ?
        var resultTpl = new Ext.XTemplate(
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
        
        this.tpl = resultTpl;

        this.store = Tine.Crm.Contact.getStore();
        
        // use beforequery to set query filter
        this.on('beforequery', function(qevent) {
            var filter = [
                {field: 'containerType', operator: 'equals', value: 'all' },
                {field: 'query', operator: 'contains', value: qevent.query }
            ];
            this.store.baseParams.filter = Ext.util.JSON.encode(filter);            
        });

        Tine.Crm.Contact.ComboBox.superclass.initComponent.call(this);        
    },
    
    /**
     * override default onSelect
     * 
     */
    onSelect: function(record){  
        record.data.relation_type = 'customer';            
        var store = Ext.StoreMgr.lookup('ContactsStore');
        store.add([record]);

        this.collapse();
        this.clearValue();
    },
    
    /**
     * on keypressed("enter") event to add record
     */ 
    onSpecialkey: function(combo, event){
        if(event.getKey() == event.ENTER){
         	var id = combo.getValue();
            var record = this.store.getById(id);
            this.onSelect(record);
        }
    },
    
});
