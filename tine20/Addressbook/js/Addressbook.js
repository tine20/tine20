/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: ContactGrid.js 6638 2009-02-09 11:56:32Z c.weiss@metaways.de $
 *
 */

// appName translation: _('Addressbook')
Ext.namespace('Tine.Addressbook');


/******************************* main screen **********************************/
Tine.Addressbook.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
    
    activeContentType: 'Contact',
    
    setContentPanel: function() {
        
        // which content panel?
        var type = this.activeContentType;
        
        if (! this[type + 'GridPanel']) {
            this[type + 'GridPanel'] = new Tine[this.app.appName][type + 'GridPanel']({
                app: this.app,
                plugins: [this.treePanel.getFilterPlugin()]
            });
            
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this[type + 'GridPanel'], true);
        this[type + 'GridPanel'].store.load();
    },
    
    /**
     * sets toolbar in mainscreen
     */
    setToolbar: function() {
        var type = this.activeContentType;
        
        if (! this[type + 'ActionToolbar']) {
            this[type + 'ActionToolbar'] = this[type + 'GridPanel'].actionToolbar;
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this[type + 'ActionToolbar'], true);
    }
});



/******************************* tree panel ***********************************/
Tine.Addressbook.TreePanel = function(config) {
    Ext.apply(this, config);
    
    var accountBackend = Tine.Tinebase.registry.get('accountBackend');
    if (accountBackend == 'Sql') {
       this.extraItems = [{
            text: Tine.Tinebase.appMgr.get('Addressbook').i18n._("Internal Contacts"),
            cls: "file",
            containerType: 'internal',
            id: "internal",
            children: [],
            leaf: false,
            expanded: true
        }];
    }
    
    this.id = 'Addressbook_Tree',
    this.recordClass = Tine.Addressbook.Model.Contact;
    Tine.Addressbook.TreePanel.superclass.constructor.call(this);
}
Ext.extend(Tine.Addressbook.TreePanel , Tine.widgets.container.TreePanel);

Tine.Addressbook.FilterPanel = Tine.widgets.grid.PersistentFilterPicker;

/**************************** edit dialog **********************************/

/**
 * The edit dialog
 * @constructor
 * @class Tine.Addressbook.ContactEditDialog
 * 
 * @todo move this to ContactEditDialog.js
 * @todo use generic Tine.widgets.EditDialog
 */  
Tine.Addressbook.ContactEditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    /**
     * @cfg {Tine.Addressbook.Model.Contact}
     */
    contact: null,
    /**
     * @cfg {Object} container
     */
    forceContainer: null,    
    /**
     * @private
     */
    windowNamePrefix: 'AddressbookEditWindow_',
    
    /**
     * @private!
     */
    id: 'contactDialog',
    layout: 'hfit',
    appName: 'Addressbook',
    containerProperty: 'container_id',
    showContainerSelector: true,
    
    initComponent: function() {
        if (! this.contact) {
            this.contact = new Tine.Addressbook.Model.Contact({}, 0);
        }
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Addressbook');
        
        Ext.Ajax.request({
            scope: this,
            success: this.onContactLoad,
            params: {
                method: 'Addressbook.getContact',
                contactId: this.contact.id
            }
        });
        
        //this.containerItemName = this.translation._('contacts');
        this.containerName = this.translation._('addressbook');
        this.containersName = this.translation._('contacts');
        
        // export lead handler for edit contact dialog
        var exportContactButton = new Ext.Action({
            id: 'exportButton',
            text: this.translation.gettext('export as pdf'),
            handler: this.handlerExport,
            iconCls: 'action_exportAsPdf',
            disabled: false,
            scope: this
        });
        
        var addNoteButton = new Tine.widgets.activities.ActivitiesAddButton({});  

        this.tbarItems = [exportContactButton, addNoteButton];
        this.items = Tine.Addressbook.ContactEditDialog.getEditForm(this.contact);
        
        Tine.Addressbook.ContactEditDialog.superclass.initComponent.call(this);
    },
    
	onRender: function(ct, position) {
        Tine.Addressbook.ContactEditDialog.superclass.onRender.call(this, ct, position);
        Ext.MessageBox.wait(this.translation._('Loading Contact...'), _('Please Wait'));
    },
    
    onContactLoad: function(response) {
        this.getForm().findField('n_prefix').focus(false, 250);
        var contactData = Ext.util.JSON.decode(response.responseText);
        if (this.forceContainer) {
            contactData.container_id = this.forceContainer;
            // only force initially!
            this.forceContainer = null;
        }
        this.updateContactRecord(contactData);
        
        if (! this.contact.id) {
            this.window.setTitle(this.translation.gettext('Add new contact'));
        } else {
            this.window.setTitle(String.format(this.translation._('Edit Contact "{0}"'), this.contact.get('n_fn') + 
                (this.contact.get('org_name') ? ' (' + this.contact.get('org_name') + ')' : '')));
        }
        
        this.getForm().loadRecord(this.contact);
        this.updateToolbars(this.contact, 'container_id');
        Ext.getCmp('addressbookeditdialog-jpegimage').setValue(this.contact.get('jpegphoto'));

        Ext.MessageBox.hide();
    },
    
    handlerApplyChanges: function(_button, _event, _closeWindow) {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Addressbook');
        
        var form = this.getForm();

        // you need to fill in one of: n_given n_family org_name
        // @todo required fields should depend on salutation ('company' -> org_name, etc.) 
        //       and not required fields should be disabled (n_given, n_family, etc.) 
        if(form.isValid() 
            && (form.findField('n_family').getValue() !== ''
                || form.findField('org_name').getValue() !== '') ) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait a moment...'), this.translation.gettext('Saving Contact'));
            form.updateRecord(this.contact);
            this.contact.set('jpegphoto', Ext.getCmp('addressbookeditdialog-jpegimage').getValue());
    
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Addressbook.saveContact', 
                    contactData: Ext.util.JSON.encode(this.contact.data)
                },
                success: function(response) {
                    this.onContactLoad(response);

                    this.fireEvent('update', this.contact); 
                	
                    if(_closeWindow === true) {
                      	this.purgeListeners();
                        this.window.close();
                    } else {
                        this.updateToolbarButtons(this.contact);
                        
                        Ext.MessageBox.hide();
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save contact.')); 
                } 
            });
        } else {
            form.findField('n_family').markInvalid();
            form.findField('org_name').markInvalid();
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));        	
        }
    },

    handlerDelete: function(_button, _event) {
        var contactIds = Ext.util.JSON.encode([this.contact.data.id]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Addressbook.deleteContacts', 
                _contactIds: contactIds
            },
            text: this.translation.gettext('Deleting contact...'),
            success: function(_result, _request) {
                this.fireEvent('update', this.contact);
                this.window.close();
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occured while trying to delete the contact.')); 
            } 
        });                           
    },
    
    handlerExport: function(_button, _event) {
    	var contactId = Ext.util.JSON.encode(this.contact.id);

        Tine.Tinebase.common.openWindow('contactWindow', 'index.php?method=Addressbook.exportContacts&_format=pdf&_filter=' + contactId, 200, 150);                   
    },
    
    updateContactRecord: function(_contactData) {
        if(_contactData.bday && _contactData.bday !== null) {
            _contactData.bday = Date.parseDate(_contactData.bday, Date.patterns.ISO8601Long);
        }

        this.contact = new Tine.Addressbook.Model.Contact(_contactData, _contactData.id ? _contactData.id : 0);
    },
    
    updateToolbarButtons: function(contact) {
        this.updateToolbars.defer(10, this, [contact, 'container_id']);
        
        // add contact id to export button and enable it if id is set
        var contactId = contact.get('id');
        if (contactId) {
        	Ext.getCmp('exportButton').contactId = contactId;
            Ext.getCmp('exportButton').setDisabled(false);
        } else {
        	Ext.getCmp('exportButton').setDisabled(true);
        }
    }
});

/**
 * Addressbook Edit Popup
 */
Tine.Addressbook.ContactEditDialog.openWindow = function (config) {
    
    // if a concreate container is selected in the tree, take this as default container
    var treeNode = Ext.getCmp('Addressbook_Tree') ? Ext.getCmp('Addressbook_Tree').getSelectionModel().getSelectedNode() : null;
    if (treeNode && treeNode.attributes && treeNode.attributes.containerType == 'singleContainer') {
        config.forceContainer = treeNode.attributes.container;
    }
    
    config.contact = config.record ? config.record : new Tine.Addressbook.Model.Contact({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        //layout: Tine.Addressbook.ContactEditDialog.prototype.windowLayout,
        name: Tine.Addressbook.ContactEditDialog.prototype.windowNamePrefix + config.contact.id,
        contentPanelConstructor: 'Tine.Addressbook.ContactEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

/**
 * get salutation store
 * if available, load data from initial data
 * 
 * @return Ext.data.JsonStore with salutations
 */
Tine.Addressbook.getSalutationStore = function() {
    
    var store = Ext.StoreMgr.get('AddressbookSalutationStore');
    if (!store) {

        store = new Ext.data.JsonStore({
            fields: Tine.Addressbook.Model.Salutation,
            baseParams: {
                method: 'Addressbook.getSalutations'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        if (Tine.Addressbook.registry.get('Salutations')) {
            store.loadData(Tine.Addressbook.registry.get('Salutations'));
        }
        
            
        Ext.StoreMgr.add('AddressbookSalutationStore', store);
    }
    
    return store;
};

/**************************** models ***************************************/

Ext.namespace('Tine.Addressbook.Model');

Tine.Addressbook.Model.ContactArray = [
    {name: 'id'},
    {name: 'tid'},
    {name: 'container_id'},
    {name: 'private'},
    {name: 'cat_id'},
    {name: 'n_family'},
    {name: 'n_given'},
    {name: 'n_middle'},
    {name: 'n_prefix'},
    {name: 'n_suffix'},
    {name: 'n_fn'},
    {name: 'n_fileas'},
    {name: 'bday', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    {name: 'org_name'},
    {name: 'org_unit'},
    {name: 'salutation_id'},
    {name: 'title'},
    {name: 'role'},
    {name: 'assistent'},
    {name: 'room'},
    {name: 'adr_one_street'},
    {name: 'adr_one_street2'},
    {name: 'adr_one_locality'},
    {name: 'adr_one_region'},
    {name: 'adr_one_postalcode'},
    {name: 'adr_one_countryname'},
    {name: 'label'},
    {name: 'adr_two_street'},
    {name: 'adr_two_street2'},
    {name: 'adr_two_locality'},
    {name: 'adr_two_region'},
    {name: 'adr_two_postalcode'},
    {name: 'adr_two_countryname'},
    {name: 'tel_work'},
    {name: 'tel_cell'},
    {name: 'tel_fax'},
    {name: 'tel_assistent'},
    {name: 'tel_car'},
    {name: 'tel_pager'},
    {name: 'tel_home'},
    {name: 'tel_fax_home'},
    {name: 'tel_cell_private'},
    {name: 'tel_other'},
    {name: 'tel_prefer'},
    {name: 'email'},
    {name: 'email_home'},
    {name: 'url'},
    {name: 'url_home'},
    {name: 'freebusy_uri'},
    {name: 'calendar_uri'},
    {name: 'note'},
    {name: 'tz'},
    {name: 'geo'},
    {name: 'pubkey'},
    {name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'created_by',         type: 'int'                  },
    {name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'last_modified_by',   type: 'int'                  },
    {name: 'is_deleted',         type: 'boolean'              },
    {name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'deleted_by',         type: 'int'                  },
    {name: 'jpegphoto'},
    {name: 'account_id'},
    {name: 'tags'},
    {name: 'notes'}
];

/**
 * @type {Tine.Tinebase.Record}
 * Contact record definition
 */
Tine.Addressbook.Model.Contact = Tine.Tinebase.Record.create(Tine.Addressbook.Model.ContactArray, {
    appName: 'Addressbook',
    modelName: 'Contact',
    idProperty: 'id',
    titleProperty: 'n_fn',
    // ngettext('Contact', 'Contacts', n);
    recordName: 'Contact',
    recordsName: 'Contacts',
    containerProperty: 'container_id',
    // ngettext('addressbook', 'addressbooks', n);
    containerName: 'addressbook',
    containersName: 'addressbooks'
});
/* not possible yet as we don't have the container client side
Tine.Addressbook.Model.Contact.getDefaultData = function() { 

};*/

/**
 * default timesheets backend
 */
Tine.Addressbook.contactBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Addressbook',
    modelName: 'Contact',
    recordClass: Tine.Addressbook.Model.Contact
});

/**
 * salutation model
 */
Tine.Addressbook.Model.Salutation = Ext.data.Record.create([
   {name: 'id'},
   {name: 'name'},
   {name: 'gender'}
]);

