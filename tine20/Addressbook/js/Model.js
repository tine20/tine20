/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine*/
 
Ext.ns('Tine.Addressbook.Model');

// TODO: move this into model definition and replace uscases (?) with getter fn
Tine.Addressbook.Model.ContactArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id', ommitDuplicateResolveing: true},
    {name: 'tid', ommitDuplicateResolveing: true},
    {name: 'private', ommitDuplicateResolveing: true},
    {name: 'cat_id', ommitDuplicateResolveing: true},
    {name: 'n_family', label: 'Last Name', group: 'Name' },//_('Last Name') _('Name')
    {name: 'n_given', label: 'First Name', group: 'Name' }, //_('First Name')
    {name: 'n_middle', label: 'Middle Name', group: 'Name' }, //_('Middle Name')
    {name: 'n_prefix', label: 'Title', group: 'Name' }, //_('Title')
    {name: 'n_suffix', label: 'Suffix', group: 'Name'}, //_('Suffix')
    {name: 'n_fn', label: 'Display Name', group: 'Name', ommitDuplicateResolveing: true }, //_('Display Name')
    {name: 'n_fileas', group: 'Name', ommitDuplicateResolveing: true },
    {name: 'bday', label: 'Birthday', type: 'date', dateFormat: Date.patterns.ISO8601Long }, //_('Birthday')
    {name: 'org_name', label: 'Company', group: 'Company' }, //_('Company')
    {name: 'org_unit', label: 'Unit', group: 'Company' }, //_('Unit')
    {name: 'salutation', label: 'Salutation', group: 'Name' }, //_('Salutation')
    {name: 'title', label: 'Job Title', group: 'Company' }, //_('Job Title')
    {name: 'role', label: 'Job Role', group: 'Company' }, //_('Job Role')
    {name: 'assistent', group: 'Company', ommitDuplicateResolveing: true},
    {name: 'room', label: 'Room', group: 'Company' }, //_('Room')
    {name: 'adr_one_street', label: 'Street (Company Address)', group: 'Company Address' }, //_('Street (Company Address)')  _('Company Address')
    {name: 'adr_one_street2', label: 'Street 2 (Company Address)', group: 'Company Address' }, //_('Street 2 (Company Address)')
    {name: 'adr_one_locality', label: 'City (Company Address)', group: 'Company Address' }, //_('City (Company Address)')
    {name: 'adr_one_region', label: 'Region (Company Address)', group: 'Company Address' }, //_('Region (Company Address)')
    {name: 'adr_one_postalcode', label: 'Postal Code (Company Address)', group: 'Company Address' }, //_('Postal Code (Company Address)')
    {name: 'adr_one_countryname', label: 'Country (Company Address)', group: 'Company Address' }, //_('Country (Company Address)')
    {name: 'adr_one_lon', group: 'Company Address', ommitDuplicateResolveing: true },
    {name: 'adr_one_lat', group: 'Company Address', ommitDuplicateResolveing: true },
    {name: 'label', ommitDuplicateResolveing: true},
    {name: 'adr_two_street', label: 'Street (Private Address)', group: 'Private Address' }, //_('Street (Private Address)')  _('Private Address')
    {name: 'adr_two_street2', label: 'Street 2 (Private Address)', group: 'Private Address' }, //_('Street 2 (Private Address)')
    {name: 'adr_two_locality', label: 'City (Private Address)', group: 'Private Address' }, //_('City (Private Address)')
    {name: 'adr_two_region', label: 'Region (Private Address)', group: 'Private Address' }, //_('Region (Private Address)')
    {name: 'adr_two_postalcode', label: 'Postal Code (Private Address)', group: 'Private Address' }, //_('Postal Code (Private Address)')
    {name: 'adr_two_countryname', label: 'Country (Private Address)', group: 'Private Address' }, //_('Country (Private Address)')
    {name: 'adr_two_lon', group: 'Private Address', ommitDuplicateResolveing: true},
    {name: 'adr_two_lat', group: 'Private Address', ommitDuplicateResolveing: true},
    {name: 'tel_work', label: 'Phone', group: 'Company Communication' }, //_('Phone') _('Company Communication') 
    {name: 'tel_cell', label: 'Mobile', group: 'Company Communication' }, //_('Mobile')
    {name: 'tel_fax', label: 'Fax', group: 'Company Communication' }, //_('Fax')
    {name: 'tel_assistent', group: 'contact_infos', ommitDuplicateResolveing: true },
    {name: 'tel_car', group: 'contact_infos', ommitDuplicateResolveing: true },
    {name: 'tel_pager', group: 'contact_infos', ommitDuplicateResolveing: true },
    {name: 'tel_home', label: 'Phone (private)', group: 'Private Communication' }, //_('Phone (private)') _('Private Communication') 
    {name: 'tel_fax_home', label: 'Fax (private)', group: 'Private Communication' }, //_('Fax (private)')
    {name: 'tel_cell_private', label: 'Mobile (private)', group: 'Private Communication' }, //_('Mobile (private)')
    {name: 'tel_other', group: 'contact_infos', ommitDuplicateResolveing: true },
    {name: 'tel_prefer', group: 'contact_infos', ommitDuplicateResolveing: true},
    {name: 'email', label: 'E-Mail', group: 'Company Communication' }, //_('E-Mail')
    {name: 'email_home', label: 'E-Mail (private)', group: 'Private Communication' }, //_('E-Mail (private)')
    {name: 'url', label: 'Web', group: 'Company Communication' }, //_('Web')
    {name: 'url_home', label: 'Web (private)', group: 'Private Communication' }, //_('Web (private)')
    {name: 'freebusy_uri', ommitDuplicateResolveing: true},
    {name: 'calendar_uri', ommitDuplicateResolveing: true},
    {name: 'note', label: 'Description' }, //_('Description')
    {name: 'tz', ommitDuplicateResolveing: true},
    {name: 'pubkey', ommitDuplicateResolveing: true},
    {name: 'jpegphoto', ommitDuplicateResolveing: true},
    {name: 'account_id', isMetaField: true},
    {name: 'tags'},
    {name: 'notes', ommitDuplicateResolveing: true},
    {name: 'relations', ommitDuplicateResolveing: true},
    {name: 'customfields', isMetaField: true},
    {name: 'type', ommitDuplicateResolveing: true}
]);

/**
 * @namespace   Tine.Addressbook.Model
 * @class       Tine.Addressbook.Model.Contact
 * @extends     Tine.Tinebase.data.Record
 * @constructor
 * Model of a contact<br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
    containersName: 'Addressbooks',
    copyOmitFields: ['account_id', 'type'],
    
    /**
     * returns true if record has an email address
     * @return {Boolean}
     */
    hasEmail: function() {
        return this.get('email') || this.get('email_home');
    },
    
    /**
     * returns true prefered email if available
     * @return {String}
     */
    getPreferedEmail: function(prefered) {
        var prefered = prefered || 'email',
            other = prefered == 'email' ? 'email_home' : 'email';
            
        return (this.get(prefered) || this.get(other));
    }
});

/**
 * get filtermodel of contact model
 * 
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Array} filterModel definition
 */ 
Tine.Addressbook.Model.Contact.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Addressbook');
    
    var typeStore = [['contact', app.i18n._('Contact')], ['user', app.i18n._('User Account')]];
    
    return [
        {label: _('Quick search'),                                                      field: 'query',              operators: ['contains']},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Addressbook.Model.Contact},
        {filtertype: 'addressbook.listMember', app: app},
        {label: app.i18n._('First Name'),                                               field: 'n_given' },
        {label: app.i18n._('Last Name'),                                                field: 'n_family'},
        {label: app.i18n._('Company'),                                                  field: 'org_name'},
        {label: app.i18n._('Unit'),                                                     field: 'org_unit'},
        {label: app.i18n._('Phone'),                                                    field: 'telephone',          operators: ['contains']},
        {label: app.i18n._('Job Title'),                                                field: 'title'},
        {label: app.i18n._('Job Role'),                                                 field: 'role'},
        {label: app.i18n._('Note'),                                                     field: 'note'},
        {label: app.i18n._('E-Mail'),                                                   field: 'email_query',        operators: ['contains']},
        {filtertype: 'tinebase.tag', app: app},
        //{label: app.i18n._('Birthday'),    field: 'bday', valueType: 'date'},
        {label: app.i18n._('Street') + ' (' + app.i18n._('Company Address') + ')',      field: 'adr_one_street',     defaultOperator: 'equals'},
        {label: app.i18n._('Region') + ' (' + app.i18n._('Company Address') + ')',      field: 'adr_one_region',     defaultOperator: 'equals'},
        {label: app.i18n._('Postal Code') + ' (' + app.i18n._('Company Address') + ')', field: 'adr_one_postalcode', defaultOperator: 'equals'},
        {label: app.i18n._('City') + '  (' + app.i18n._('Company Address') + ')',       field: 'adr_one_locality'},
        {label: app.i18n._('Country') + '  (' + app.i18n._('Company Address') + ')',    field: 'adr_one_countryname', valueType: 'country'},
        {label: app.i18n._('Street') + ' (' + app.i18n._('Private Address') + ')',      field: 'adr_two_street',     defaultOperator: 'equals'},
        {label: app.i18n._('Region') + ' (' + app.i18n._('Private Address') + ')',      field: 'adr_two_region',     defaultOperator: 'equals'},
        {label: app.i18n._('Postal Code') + ' (' + app.i18n._('Private Address') + ')', field: 'adr_two_postalcode', defaultOperator: 'equals'},
        {label: app.i18n._('City') + ' (' + app.i18n._('Private Address') + ')',        field: 'adr_two_locality'},
        {label: app.i18n._('Country') + '  (' + app.i18n._('Private Address') + ')',    field: 'adr_two_countryname', valueType: 'country'},
        {label: app.i18n._('Type'), defaultValue: 'contact', valueType: 'combo',        field: 'type',               store: typeStore},
        {label: app.i18n._('Last modified'),                                            field: 'last_modified_time', valueType: 'date'},
        {label: app.i18n._('Last modifier'),                                            field: 'last_modified_by', 	 valueType: 'user'},
        {label: app.i18n._('Creation Time'),                                            field: 'creation_time', 	 valueType: 'date'},
        {label: app.i18n._('Creator'),                                                  field: 'created_by', 		 valueType: 'user'}
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
 * list model
 */
Tine.Addressbook.Model.List = Tine.Tinebase.data.Record.create([
   {name: 'id'},
   {name: 'container_id'},
   {name: 'created_by'},
   {name: 'creation_time'},
   {name: 'last_modified_by'},
   {name: 'last_modified_time'},
   {name: 'is_deleted'},
   {name: 'deleted_time'},
   {name: 'deleted_by'},
   {name: 'name'},
   {name: 'description'},
   {name: 'members'},
   {name: 'email'},
   {name: 'type'},
   {name: 'group_id'}
], {
    appName: 'Addressbook',
    modelName: 'List',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('List', 'Lists', n); gettext('Lists');
    recordName: 'List',
    recordsName: 'Lists',
    containerProperty: 'container_id',
    // ngettext('Addressbook', 'Addressbooks', n); gettext('Addressbooks');
    containerName: 'Addressbook',
    containersName: 'Addressbooks',
    copyOmitFields: ['group_id']
});
