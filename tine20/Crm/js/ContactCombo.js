/*
 * Tine 2.0
 * contacts combo box and store
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Crm');

/**
 * @namespace   Tine.Crm.Contact
 * @class       Tine.Crm.ContactCombo
 * @extends     Tine.Addressbook.SearchCombo
 * 
 * Lead Dialog Contact Search Combo
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Crm.ContactCombo = Ext.extend(Tine.Addressbook.SearchCombo, {

    valueField: 'id',
    
    /**
     * store to hold all contacts of grid
     * 
     * @type Ext.data.Store
     * @property contactsStore
     */
    contactsStore: null,
    
    //private
    initComponent: function(){
        this.contactFields = Tine.Addressbook.Model.ContactArray;
        this.contactFields.push({name: 'relation'});   // the relation object           
        this.contactFields.push({name: 'relation_type'});
        
        Tine.Crm.ContactCombo.superclass.initComponent.call(this);        
    },
    
    /**
     * override default onSelect
     * 
     */
    onSelect: function(record){  
        record.data.relation_type = 'customer';            
        
        // check if already in
        if (! this.contactsStore.getById(record.id)) {
            this.contactsStore.add([record]);
        }
            
        this.collapse();
        this.clearValue();
    }    
});
