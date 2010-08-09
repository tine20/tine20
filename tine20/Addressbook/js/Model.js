/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.Addressbook.Model');


// todo: move this into model definition and replce uscases with getter fn
Tine.Addressbook.Model.ContactArray = [
    {name: 'id'},
    {name: 'tid'},
    {name: 'container_id'},
    {name: 'private'},
    {name: 'cat_id'},
    {name: 'n_family', label: 'Last Name' },//_('Last Name')
    {name: 'n_given', label: 'First Name' }, //_('First Name')
    {name: 'n_middle', label: 'Middle Name' }, //_('Middle Name')
    {name: 'n_prefix', label: 'Title' }, //_('Title')
    {name: 'n_suffix', label: 'Suffix' }, //_('Suffix')
    {name: 'n_fn', label: 'Display Name' }, //_('Display Name')
    {name: 'n_fileas' },
    {name: 'bday', label: 'Birthday' , type: 'date', dateFormat: Date.patterns.ISO8601Long }, //_('Birthday')
    {name: 'org_name', label: 'Company' }, //_('Company')
    {name: 'org_unit', label: 'Unit' }, //_('Unit')
    {name: 'salutation_id', label: 'Salutation' }, //_('Salutation')
    {name: 'title', label: 'Job Title' }, //_('Job Title')
    {name: 'role', label: 'Job Role' }, //_('Job Role')
    {name: 'assistent'},
    {name: 'room', label: 'Room' }, //_('Room')
    {name: 'adr_one_street', label: 'Street (Company Address)' }, //_('Street (Company Address)')
    {name: 'adr_one_street2', label: 'Street 2 (Company Address)' }, //_('Street 2 (Company Address)')
    {name: 'adr_one_locality', label: 'City (Company Address)' }, //_('City (Company Address)')
    {name: 'adr_one_region', label: 'Region (Company Address)' }, //_('Region (Company Address)')
    {name: 'adr_one_postalcode', label: 'Postal Code (Company Address)' }, //_('Postal Code (Company Address)')
    {name: 'adr_one_countryname', label: 'Country (Company Address)' }, //_('Country (Company Address)')
    {name: 'label'},
    {name: 'adr_two_street', label: 'Street (Private Address)' }, //_('Street (Private Address)')
    {name: 'adr_two_street2', label: 'Street 2 (Private Address)' }, //_('Street 2 (Private Address)')
    {name: 'adr_two_locality', label: 'City (Private Address)' }, //_('City (Private Address)')
    {name: 'adr_two_region', label: 'Region (Private Address)' }, //_('Region (Private Address)')
    {name: 'adr_two_postalcode', label: 'Postal Code (Private Address)' }, //_('Postal Code (Private Address)')
    {name: 'adr_two_countryname', label: 'Country (Private Address)' }, //_('Country (Private Address)')
    {name: 'tel_work', label: 'Phone' }, //_('Phone')
    {name: 'tel_cell', label: 'Mobile' }, //_('Mobile')
    {name: 'tel_fax', label: 'Fax' }, //_('Fax')
    {name: 'tel_assistent' },
    {name: 'tel_car' },
    {name: 'tel_pager' },
    {name: 'tel_home', label: 'Phone (private)' }, //_('Phone (private)')
    {name: 'tel_fax_home', label: 'Fax (private)'}, //_('Fax (private)')
    {name: 'tel_cell_private', label: 'Mobile (private)' }, //_('Mobile (private)')
    {name: 'tel_other' },
    {name: 'tel_prefer'},
    {name: 'email', label: 'E-Mail' }, //_('E-Mail')
    {name: 'email_home', label: 'E-Mail (private)' }, //_('E-Mail (private)')
    {name: 'url', label: 'Web'}, //_('Web')
    {name: 'url_home', label: 'Web (private)' }, //_('Web (private)')
    {name: 'freebusy_uri'},
    {name: 'calendar_uri'},
    {name: 'note', label: 'Description' }, //_('Description')
    {name: 'tz'},
    {name: 'lon'},
    {name: 'lat'},
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
    {name: 'notes'},
    {name: 'relations'},
    {name: 'customfields'},
    {name: 'type'}
];

/**
 * @namespace   Tine.Addressbook.Model
 * @class       Tine.Addressbook.Model
 * @extends     Tine.Addressbook.Model.Contact
 * Model of a contact<br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Addressbook.Model.Contact = Tine.Tinebase.data.Record.create(Tine.Addressbook.Model.ContactArray, {
    appName: 'Addressbook',
    modelName: 'Contact',
    idProperty: 'id',
    titleProperty: 'n_fn',
    // ngettext('Contact', 'Contacts', n); gettext('Contacts');
    recordName: 'Contact',
    recordsName: 'Contacts',
    containerProperty: 'container_id',
    // ngettext('Addressbook', 'Addressbooks', n); gettext('Addressbooks');
    containerName: 'Addressbook',
    containersName: 'Addressbooks'
});


/**
 * get default data for a new contact
 * 
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Object} default data
 *
 *not usefull as long we don't use full records in the gird
Tine.Addressbook.Model.Contact.getDefaultData = function() { 
    var data = {};
    
    
    var app = Tine.Tinebase.appMgr.get('Addressbook');
    
    //var treeNode = app.getMainScreen().getWestPanel().getContainerTreePanel().getSelectionModel().getSelectedNode();
    var treeNode = Ext.getCmp('Addressbook_Tree') ? Ext.getCmp('Addressbook_Tree').getSelectionModel().getSelectedNode() : null;
    if (treeNode && treeNode.attributes && treeNode.attributes.containerType == 'singleContainer') {
        data.container_id = treeNode.attributes.container;
    } else {
        data.container_id = Tine.Addressbook.registry.get('defaultAddressbook');
    }
    
    return data;
};*/

/**
 * get filtermodel of contact model
 * 
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Object} filterModel definition
 */ 
Tine.Addressbook.Model.Contact.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Addressbook');
    
    var typeStore = [['contact', app.i18n._('Contact')], ['user', app.i18n._('User Account')]];
    
    return [
        {label: _('Quick search'),                                                      field: 'query',              operators: ['contains']},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Addressbook.Model.Contact},
        {label: app.i18n._('First Name'),                                               field: 'n_given' },
        {label: app.i18n._('Last Name'),                                                field: 'n_family'},
        {label: app.i18n._('Company'),                                                  field: 'org_name'},
        {label: app.i18n._('Phone'),                                                    field: 'telephone',          operators: ['contains']},
        {label: app.i18n._('Job Title'),                                                field: 'title'},
        {label: app.i18n._('Job Role'),                                                 field: 'role'},
        {label: app.i18n._('Note'),                                                     field: 'note'},
        {filtertype: 'tinebase.tag', app: app},
        //{label: app.i18n._('Birthday'),    field: 'bday', valueType: 'date'},
        {label: app.i18n._('Street') + ' (' + app.i18n._('Company Address') + ')',      field: 'adr_one_street',     defaultOperator: 'equals'},
        {label: app.i18n._('Postal Code') + ' (' + app.i18n._('Company Address') + ')', field: 'adr_one_postalcode', defaultOperator: 'equals'},
        {label: app.i18n._('City') + '  (' + app.i18n._('Company Address') + ')',       field: 'adr_one_locality'},
        {label: app.i18n._('Street') + ' (' + app.i18n._('Private Address') + ')',      field: 'adr_two_street',     defaultOperator: 'equals'},
        {label: app.i18n._('Postal Code') + ' (' + app.i18n._('Private Address') + ')', field: 'adr_two_postalcode', defaultOperator: 'equals'},
        {label: app.i18n._('City') + ' (' + app.i18n._('Private Address') + ')',        field: 'adr_two_locality'},
        {label: app.i18n._('Type'), defaultValue: 'contact', valueType: 'combo',        field: 'type',               store: typeStore},
        {label: app.i18n._('Last modified'),                                            field: 'last_modified_time', valueType: 'date'},
        {label: app.i18n._('Last modifier'),                                            field: 'last_modified_by', valueType: 'user'},
        {label: app.i18n._('Creation Time'),                                            field: 'creation_time', valueType: 'date'},
        {label: app.i18n._('Creator'),                                                  field: 'created_by', valueType: 'user'}
    ];
};
    
/**
 * default timesheets backend
 */
Tine.Addressbook.contactBackend = new Tine.Tinebase.data.RecordProxy({
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
