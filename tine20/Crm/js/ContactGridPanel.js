/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.ContactGridPanel
 * @extends     Ext.grid.EditorGridPanel
 * 
 * Lead Dialog Contact Grid Panel
 * 
 * <p>
 * TODO         make edit + add new actions work
 * TODO         add ctx menu
 * TODO         move contact search combo into grid (like attendee/recipient grid)
 * TODO         generalize this and use it for tasks/products
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Crm.ContactGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'n_fileas',
    clicksToEdit: 1,
    //enableHdMenu: false,
    
    baseCls: 'contact-grid',
    
    /**
     * The record currently being edited
     * 
     * @type Tine.Crm.Model.Lead
     * @property record
     */
    record: null,
    
    /**
     * store to hold all contacts
     * 
     * @type Ext.data.Store
     * @property contactStore
     */
    contactStore: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');

        this.initStore();
        this.initActions();
        this.cm = this.getColumnModel();
        
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        this.selModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();
            if (this.record && (this.record.get('container_id') && this.record.get('container_id').account_grants)) {
                this.actionUnlink.setDisabled(!this.record.get('container_id').account_grants.editGrant || rowCount != 1);
            }
            //this.actionEdit.setDisabled(rowCount != 1);
        }, this);
        
        Tine.Crm.ContactGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: [            
                {id:'id', header: "id", dataIndex: 'id', width: 25, hidden: true },
                {id:'n_fileas', header: this.app.i18n._('Name'), dataIndex: 'n_fileas', width: 200, sortable: true, renderer: 
                    function(val, meta, record) {
                        var org_name           = Ext.isEmpty(record.data.org_name) === false ? record.data.org_name : ' ';
                        var n_fileas           = Ext.isEmpty(record.data.n_fileas) === false ? record.data.n_fileas : ' ';                            
                        var formated_return = '<b>' + Ext.util.Format.htmlEncode(n_fileas) + '</b><br />' + Ext.util.Format.htmlEncode(org_name);
                        
                        return formated_return;
                    }
                },
                {id:'contact_one', header: this.app.i18n._("Address"), dataIndex: 'adr_one_locality', width: 140, sortable: false, renderer: function(val, meta, record) {
                        var adr_one_street     = Ext.isEmpty(record.data.adr_one_street) === false ? record.data.adr_one_street : ' ';
                        var adr_one_postalcode = Ext.isEmpty(record.data.adr_one_postalcode) === false ? record.data.adr_one_postalcode : ' ';
                        var adr_one_locality   = Ext.isEmpty(record.data.adr_one_locality) === false ? record.data.adr_one_locality : ' ';
                        var formated_return =  
                            Ext.util.Format.htmlEncode(adr_one_street) + '<br />' + 
                            Ext.util.Format.htmlEncode(adr_one_postalcode) + ' ' + Ext.util.Format.htmlEncode(adr_one_locality);
                    
                        return formated_return;
                    }
                },
                {id:'tel_work', header: this.app.i18n._("Data"), dataIndex: 'tel_work', width: 140, sortable: false, renderer: function(val, meta, record) {
                        var translation = new Locale.Gettext();
                        translation.textdomain('Crm');
                        var tel_work           = Ext.isEmpty(record.data.tel_work) === false ? translation._('Phone') + ': ' + record.data.tel_work : ' ';
                        var tel_cell           = Ext.isEmpty(record.data.tel_cell) === false ? translation._('Cellphone') + ': ' + record.data.tel_cell : ' ';          
                        var formated_return = tel_work + '<br/>' + tel_cell + '<br/>';
                        return formated_return;
                    }
                }, {
                    id:'relation_type', 
                    header: this.app.i18n._("Type"), 
                    dataIndex: 'relation_type', 
                    width: 60, 
                    sortable: true,
                    renderer: Tine.Crm.contactType.Renderer,
                    editor: new Tine.Crm.contactType.ComboBox({
                        autoExpand: true,
                        blurOnSelect: true,
                        listClass: 'x-combo-list-small'
                    })
                }
            ]}
        );
    },
    
    /**
     * @private
     */
    initStore: function() {
        var contactFields = Tine.Addressbook.Model.ContactArray;
        contactFields.push({name: 'relation'});   // the relation object           
        contactFields.push({name: 'relation_type'});     
        
        this.store = new Ext.data.JsonStore({
            id: 'id',
            fields: contactFields
        });

        // get contacts from record
        //console.log(this.record);
        this.store.loadData(this.record.get('contacts'), true);                    

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
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.actionsAdd = new Ext.Action({
            requiredGrant: 'editGrant',
            contactType: 'customer',
            text: this.app.i18n._('Add new contact'),
            tooltip: this.app.i18n._('Add new customer contact'),
            iconCls: 'actionAdd',
            scope: this,
            handler: this.onAdd
        }); 
        
        this.actionUnlink = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.app.i18n._('Unlink contact'),
            tooltip: this.app.i18n._('Unlink selected contacts'),
            disabled: true,
            iconCls: 'actionRemove',
            scope: this,
            handler: this.onUnlink
        });

        this.bbar = [                
            this.actionsAdd,
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
    },
    
    /**
     * onclick handler for onAddContact
     */
    onAdd: function(_button, _event) {
        var contactWindow = Tine.Addressbook.ContactEditDialog.openWindow({
            listeners: {
                scope: this,
                'update': this.onContactUpdate
            }
        });         
    },
        
    /**
     * onclick handler for editContact
     */
    onEdit: function(_button, _event) {
        var selectedRows = this.getSelectionModel().getSelections();
        
        var contactWindow = Tine.Addressbook.ContactEditDialog.openWindow({
            record: selectedRows[0],
            listeners: {
                scope: this,
                'update': this.onContactUpdate
            }
        });         
    },
    
    /**
     * unlink action handler for linked objects
     * 
     * remove selected objects from store
     * needs _button.gridId and _button.storeName
     */
    onUnlink: function(_button, _event) {                       
        console.log('unlink');
        
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            this.store.remove(selectedRows[i]);
        }           
    },

    /**
     * onclick handler for changeContactType
     */
    changeContactType: function(_button, _event) {          
        var selectedRows = this.getSelectionModel().getSelections();
        var store = Ext.StoreMgr.lookup('ContactsStore');
        
        for (var i = 0; i < selectedRows.length; ++i) {
            selectedRows[i].data.relation_type = _button.contactType;
        }
        
        store.fireEvent('dataChanged', store);
    },
    
    /**
     * update event handler for related contacts
     */
    onContactUpdate: function(contact) {
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
    }
});

Ext.namespace('Tine.Crm', 'Tine.Crm.contactType');

/**
 * contact type select combo box
 * 
 * TODO     add extdoc
 */
Tine.Crm.contactType.ComboBox = Ext.extend(Ext.form.ComboBox, { 
    /**
     * @cfg {bool} autoExpand Autoexpand comboBox on focus.
     */
    autoExpand: false,
    /**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    
    displayField: 'label',
    valueField: 'relation_type',
    mode: 'local',
    triggerAction: 'all',
    lazyInit: false,
    
    //private
    initComponent: function() {
        
        var translation = new Locale.Gettext();
        translation.textdomain('Crm');
        
        Tine.Crm.contactType.ComboBox.superclass.initComponent.call(this);
        // allways set a default
        if(!this.value) {
            this.value = 'responsible';
        }
            
        this.store = new Ext.data.SimpleStore({
            fields: ['label', 'relation_type'],
            data: [
                    [translation._('Responsible'), 'responsible'],
                    [translation._('Customer'), 'customer'],
                    [translation._('Partner'), 'partner']
                ]
        });
        
        if (this.autoExpand) {
            this.lazyInit = false;
            this.on('focus', function(){
                this.selectByValue(this.getValue());
                this.onTriggerClick();
            });
        }
        
        if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
    }
});
Ext.reg('leadcontacttypecombo', Tine.Crm.contactType.ComboBox);

/**
 * contact type renderer
 * 
 * @param   string type
 * @return  contact type icon
 * 
 * TODO     add extdoc
 */
Tine.Crm.contactType.Renderer = function(type)
{
    var translation = new Locale.Gettext();
    translation.textdomain('Crm');
    
    switch ( type ) {
        case 'responsible':
            var iconClass = 'contactIconResponsible';
            var qTip = translation._('Responsible');
            break;
        case 'customer':
            var iconClass = 'contactIconCustomer';
            var qTip = translation._('Customer');
            break;
        case 'partner':
            var iconClass = 'contactIconPartner';
            var qTip = translation._('Partner');
            break;
    }
    
    var icon = '<img class="x-menu-item-icon contactIcon ' + iconClass + '" src="library/ExtJS/resources/images/default/s.gif" ext:qtip="' + qTip + '"/>';
    
    return icon;
};
