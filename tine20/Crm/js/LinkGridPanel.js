/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         generalize this
 * TODO         make grants work
 */
 
Ext.ns('Tine.Crm.LinkGridPanel');

/**
 * @namespace   Tine.Crm.LinkGridPanel
 * 
 * TODO         generalize
 * TODO         move change contact type functions
 */
Tine.Crm.LinkGridPanel.initActions = function() {
    this.actionAdd = new Ext.Action({
        requiredGrant: 'editGrant',
        contactType: 'customer',
        text: this.app.i18n._('Add new contact'),
        tooltip: this.app.i18n._('Add new customer contact'),
        iconCls: 'actionAdd',
        scope: this,
        handler: function(_button, _event) {
            var contactWindow = Tine.Addressbook.ContactEditDialog.openWindow({
                listeners: {
                    scope: this,
                    'update': this.onUpdate
                }
            });         
        }
    }); 
    
    this.actionUnlink = new Ext.Action({
        requiredGrant: 'editGrant',
        text: this.app.i18n._('Unlink contact'),
        tooltip: this.app.i18n._('Unlink selected contacts'),
        disabled: true,
        iconCls: 'actionRemove',
        scope: this,
        handler: function(_button, _event) {                       
            var selectedRows = this.getSelectionModel().getSelections();
            for (var i = 0; i < selectedRows.length; ++i) {
                this.store.remove(selectedRows[i]);
            }           
        }
    });
    
    this.actionChangeContactTypeCustomer = new Ext.Action({
        requiredGrant: 'editGrant',
        contactType: 'customer',
        text: this.app.i18n._('Customer'),
        tooltip: this.app.i18n._('Change type to Customer'),
        iconCls: 'contactIconCustomer',
        scope: this,
        handler: this.onChangeContactType
    }); 
    
    this.actionChangeContactTypeResponsible = new Ext.Action({
        requiredGrant: 'editGrant',
        contactType: 'responsible',
        text: this.app.i18n._('Responsible'),
        tooltip: this.app.i18n._('Change type to Responsible'),
        iconCls: 'contactIconResponsible',
        scope: this,
        handler: this.onChangeContactType
    }); 

    this.actionChangeContactTypePartner = new Ext.Action({
        requiredGrant: 'editGrant',
        contactType: 'partner',
        text: this.app.i18n._('Partner'),
        tooltip: this.app.i18n._('Change type to Partner'),
        iconCls: 'contactIconPartner',
        scope: this,
        handler: this.onChangeContactType
    }); 

    this.actionEdit = new Ext.Action({
        requiredGrant: 'editGrant',
        text: this.app.i18n._('Edit contact'),
        tooltip: this.app.i18n._('Edit selected contact'),
        //disabled: true,
        iconCls: 'actionEdit',
        scope: this,
        handler: function(_button, _event) {
            var selectedRows = this.getSelectionModel().getSelections();
            
            var contactWindow = Tine.Addressbook.ContactEditDialog.openWindow({
                record: selectedRows[0],
                listeners: {
                    scope: this,
                    'update': this.onUpdate
                }
            });         
        }
    });

    // init toolbars and ctx menut / add actions
    this.bbar = [                
        this.actionAdd,
        this.actionUnlink
    ];
        
    this.tbar = new Ext.Panel({
        layout: 'fit',
        width: '100%',
        items: [
            // TODO perhaps we could add an icon/button (i.e. edit-find.png) here
            new Tine.Crm.ContactCombo({
                emptyText: this.app.i18n._('Search for Contacts to add ...')
            })
        ]
    });
    
    this.contextMenu = new Ext.menu.Menu({
        items: [
            this.actionEdit,
            this.actionUnlink,
            {
                text: this.app.i18n._('Change contact type'),
                menu: [
                   this.actionChangeContactTypeCustomer,
                   this.actionChangeContactTypeResponsible,
                   this.actionChangeContactTypePartner
                ]
            },
            '-',
            this.actionAdd
        ]
    });
};

/**
 * init store
 * 
 * TODO         generalize
 */ 
Tine.Crm.LinkGridPanel.initStore = function() {
    var contactFields = Tine.Addressbook.Model.ContactArray;
    contactFields.push({name: 'relation'});   // the relation object           
    contactFields.push({name: 'relation_type'});     
    
    this.store = new Ext.data.JsonStore({
        id: 'id',
        fields: contactFields
    });

    this.store.setDefaultSort('type', 'asc');   
    
    // focus+select new record
    this.store.on('add', function(store, records, index) {
        (function() {
            this.getView().focusRow(index);
            this.getSelectionModel().selectRow(index); 
        }).defer(100, this);
    }, this);
    
    // TODO remove that later
    Ext.StoreMgr.add('ContactsStore', this.store);
};

/**
 * init ext grid panel
 * 
 * TODO         generalize
 * TODO         add grants again for all actions with required grants
 */
Tine.Crm.LinkGridPanel.initGrid = function() {
    this.cm = this.getColumnModel();
    
    this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
    this.selModel.on('selectionchange', function(_selectionModel) {
        var rowCount = _selectionModel.getCount();
        /*
        if (this.record && (this.record.get('container_id') && this.record.get('container_id').account_grants)) {
            this.actionUnlink.setDisabled(!this.record.get('container_id').account_grants.editGrant || rowCount != 1);
        }
        this.actionEdit.setDisabled(rowCount != 1);
        */
        this.actionUnlink.setDisabled(rowCount != 1);
    }, this);
    
    this.on('rowcontextmenu', function(grid, row, e) {
        e.stopEvent();
        var selModel = grid.getSelectionModel();
        if(!selModel.isSelected(row)) {
            selModel.selectRow(row);
        }
        
        this.contextMenu.showAt(e.getXY());
    }, this);
};

/**
 * update event handler for related contacts
 * 
 * TODO         generalize
 */
Tine.Crm.LinkGridPanel.onUpdate = function(contact) {
    var response = {
        responseText: contact
    };
    contact = Tine.Addressbook.contactBackend.recordReader(response);
    
    var myContact = this.store.getById(contact.id);
    if (myContact) {
        myContact.beginEdit();
        for (var p in contact.data) { 
            myContact.set(p, contact.get(p));
        }
        myContact.endEdit();
    } else {
        contact.data.relation_type = 'customer';
        this.store.add(contact);
    }        
};
