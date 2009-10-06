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
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,

    /**
     * @type Array
     * @property otherActions
     */
    otherActions: null,
    
    /**
     * @type function
     * @property recordEditDialogOpener
     */
    recordEditDialogOpener: null,
    
    /**
     * record class
     * @cfg {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');
        this.recordEditDialogOpener = Tine.Addressbook.ContactEditDialog.openWindow;
        this.recordClass = Tine.Addressbook.Model.Contact;
        
        // create delegates
        this.initStore = Tine.Crm.LinkGridPanel.initStore.createDelegate(this);
        this.initActions = Tine.Crm.LinkGridPanel.initActions.createDelegate(this);
        this.initGrid = Tine.Crm.LinkGridPanel.initGrid.createDelegate(this);
        this.onUpdate = Tine.Crm.LinkGridPanel.onUpdate.createDelegate(this);

        // init other actions (changeContactType)
        this.initOtherActions();

        // call delegates
        this.initStore();
        this.initActions();
        this.initGrid();
        
        // add contact type to "add" action
        this.actionAdd.contactType = 'customer';
        
        Tine.Crm.ContactGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init other actions and tbar
     */
    initOtherActions: function() {
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
        
        this.otherActions = [{
            text: this.app.i18n._('Change contact type'),
            menu: [
               this.actionChangeContactTypeCustomer,
               this.actionChangeContactTypeResponsible,
               this.actionChangeContactTypePartner
            ]
        }];

        /*
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
        */
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
     * onclick handler for changeContactType
     */
    onChangeContactType: function(_button, _event) {          
        var selectedRows = this.getSelectionModel().getSelections();
        var store = Ext.StoreMgr.lookup('ContactsStore');
        
        for (var i = 0; i < selectedRows.length; ++i) {
            selectedRows[i].data.relation_type = _button.contactType;
        }
        
        store.fireEvent('dataChanged', store);
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
