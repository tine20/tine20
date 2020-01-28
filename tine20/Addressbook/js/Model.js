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
    {name: 'id', omitDuplicateResolving: true},
    {name: 'tid', omitDuplicateResolving: true},
    {name: 'private', omitDuplicateResolving: true},
    {name: 'cat_id', omitDuplicateResolving: true},
    {name: 'n_family', label: 'Last Name', group: 'Name' },//_('Last Name') _('Name')
    {name: 'n_given', label: 'First Name', group: 'Name' }, //_('First Name')
    {name: 'n_middle', label: 'Middle Name', group: 'Name' }, //_('Middle Name')
    {name: 'n_prefix', label: 'Title', group: 'Name' }, //_('Title')
    {name: 'n_suffix', label: 'Suffix', group: 'Name'}, //_('Suffix')
    {name: 'n_short', label: 'Short Name', group: 'Name'}, //_('Short Name')
    {name: 'n_fn', label: 'Display Name', group: 'Name', omitDuplicateResolving: true }, //_('Display Name')
    {name: 'n_fileas', group: 'Name', omitDuplicateResolving: true },
    {name: 'bday', label: 'Birthday', type: 'date', dateFormat: Date.patterns.ISO8601Long }, //_('Birthday')
    {name: 'org_name', label: 'Company', group: 'Company' }, //_('Company')
    {name: 'org_unit', label: 'Unit', group: 'Company' }, //_('Unit')
    {name: 'salutation', label: 'Salutation', group: 'Name' }, //_('Salutation')
    {name: 'title', label: 'Job Title', group: 'Company' }, //_('Job Title')
    {name: 'role', label: 'Job Role', group: 'Company' }, //_('Job Role')
    {name: 'assistent', group: 'Company', omitDuplicateResolving: true},
    {name: 'room', label: 'Room', group: 'Company' }, //_('Room')
    {name: 'adr_one_street', label: 'Street (Company Address)', group: 'Company Address' }, //_('Street (Company Address)')  _('Company Address')
    {name: 'adr_one_street2', label: 'Street 2 (Company Address)', group: 'Company Address' }, //_('Street 2 (Company Address)')
    {name: 'adr_one_locality', label: 'City (Company Address)', group: 'Company Address' }, //_('City (Company Address)')
    {name: 'adr_one_region', label: 'Region (Company Address)', group: 'Company Address' }, //_('Region (Company Address)')
    {name: 'adr_one_postalcode', label: 'Postal Code (Company Address)', group: 'Company Address' }, //_('Postal Code (Company Address)')
    {name: 'adr_one_countryname', label: 'Country (Company Address)', group: 'Company Address' }, //_('Country (Company Address)')
    {name: 'adr_one_lon', label: 'Longitude (Company Address)', group: 'Company Address', omitDuplicateResolving: true }, //_('Longitude (Company Address)')
    {name: 'adr_one_lat', label: 'Latitude (Company Address)', group: 'Company Address', omitDuplicateResolving: true }, //_('Latitude (Company Address)')
    {name: 'label', omitDuplicateResolving: true},
    {name: 'adr_two_street', label: 'Street (Private Address)', group: 'Private Address' }, //_('Street (Private Address)')  _('Private Address')
    {name: 'adr_two_street2', label: 'Street 2 (Private Address)', group: 'Private Address' }, //_('Street 2 (Private Address)')
    {name: 'adr_two_locality', label: 'City (Private Address)', group: 'Private Address' }, //_('City (Private Address)')
    {name: 'adr_two_region', label: 'Region (Private Address)', group: 'Private Address' }, //_('Region (Private Address)')
    {name: 'adr_two_postalcode', label: 'Postal Code (Private Address)', group: 'Private Address' }, //_('Postal Code (Private Address)')
    {name: 'adr_two_countryname', label: 'Country (Private Address)', group: 'Private Address' }, //_('Country (Private Address)')
    {name: 'adr_two_lon', group: 'Private Address', omitDuplicateResolving: true},
    {name: 'adr_two_lat', group: 'Private Address', omitDuplicateResolving: true},
    {name: 'preferred_address', group: 'Preferred Address', omitDuplicateResolving: true}, //_('Preferred Address')
    {name: 'tel_work', label: 'Phone', group: 'Company Communication' }, //_('Phone') _('Company Communication')
    {name: 'tel_cell', label: 'Mobile', group: 'Company Communication' }, //_('Mobile')
    {name: 'tel_fax', label: 'Fax', group: 'Company Communication' }, //_('Fax')
    {name: 'tel_assistent', group: 'contact_infos', omitDuplicateResolving: true },
    {name: 'tel_car', group: 'contact_infos', omitDuplicateResolving: true },
    {name: 'tel_pager', group: 'contact_infos', omitDuplicateResolving: true },
    {name: 'tel_home', label: 'Phone (private)', group: 'Private Communication' }, //_('Phone (private)') _('Private Communication')
    {name: 'tel_fax_home', label: 'Fax (private)', group: 'Private Communication' }, //_('Fax (private)')
    {name: 'tel_cell_private', label: 'Mobile (private)', group: 'Private Communication' }, //_('Mobile (private)')
    {name: 'tel_other', group: 'contact_infos', omitDuplicateResolving: true },
    {name: 'tel_prefer', group: 'contact_infos', omitDuplicateResolving: true},
    {name: 'email', label: 'E-Mail', group: 'Company Communication' }, //_('E-Mail')
    {name: 'email_home', label: 'E-Mail (private)', group: 'Private Communication' }, //_('E-Mail (private)')
    {name: 'url', label: 'Web', group: 'Company Communication' }, //_('Web')
    {name: 'url_home', label: 'Web (private)', group: 'Private Communication' }, //_('Web (private)')
    {name: 'freebusy_uri', omitDuplicateResolving: true},
    {name: 'calendar_uri', omitDuplicateResolving: true},
    {name: 'note', label: 'Description' }, //_('Description')
    {name: 'tz', omitDuplicateResolving: true},
    {name: 'pubkey', omitDuplicateResolving: true},
    {name: 'jpegphoto', omitDuplicateResolving: true},
    {name: 'account_id', omitDuplicateResolving: true},
    {name: 'tags'},
    {name: 'notes', omitDuplicateResolving: true},
    {name: 'relations', omitDuplicateResolving: true},
    {name: 'customfields', omitDuplicateResolving: true},
    {name: 'attachments', omitDuplicateResolving: true},
    {name: 'paths', omitDuplicateResolving: true},
    {name: 'type', omitDuplicateResolving: true},
    {name: 'memberroles', omitDuplicateResolving: true},
    {name: 'industry', omitDuplicateResolving: true},
    {name: 'groups', label: 'Groups'} //_('Groups')
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
    copyOmitFields: ['account_id', 'type', 'relations'],
    
    /**
     * returns true if record has an email address
     * @return {Boolean}
     */
    hasEmail: function() {
        return this.get('email') || this.get('email_home');
    },
    
    /**
     * returns true preferred email if available
     * @return {String}
     */
    getPreferredEmail: function(preferred) {
        var preferred = preferred || 'email',
            other = preferred == 'email' ? 'email_home' : 'email';
            
        return (this.get(preferred) || this.get(other));
    },

    getTitle: function() {
        var result = this.get('n_fn');

        var tinebaseApp = new Tine.Tinebase.Application({
            appName: 'Tinebase'
        });
        if (tinebaseApp.featureEnabled('featureShowAccountEmail')) {
            var email = this.getPreferredEmail();
            if (email) {
                result += ' (' + email + ')';
            }
        }

        return result;
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
    
    var filters = [
        {label: i18n._('Quick Search'),                                                      field: 'query',              operators: ['contains']},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Addressbook.Model.Contact},
        {filtertype: 'addressbook.listMember', app: app},
        {filtertype: 'addressbook.listRoleMember', app: app},
        {label: app.i18n._('Title'),                                                    field: 'n_prefix' },
        {label: app.i18n._('First Name'),                                               field: 'n_given' },
        {label: app.i18n._('Last Name'),                                                field: 'n_family'},
        {label: app.i18n._('Middle Name'),                                              field: 'n_middle'},
        {label: app.i18n._('Short Name'),                                               field: 'n_short'}, 
        {label: app.i18n._('Company'),                                                  field: 'org_name'},
        {label: app.i18n._('Unit'),                                                     field: 'org_unit'},
        {label: app.i18n._('Phone'),                                                    field: 'telephone',          operators: ['contains']},
        {label: app.i18n._('Job Title'),                                                field: 'title'},
        {label: app.i18n._('Description'),                                              field: 'note', valueType: 'fulltext'},
        {label: app.i18n._('E-Mail'),                                                   field: 'email_query',        operators: ['contains']},
        {filtertype: 'tinebase.tag', app: app},
//        {label: app.i18n._('Job Role'),                                                 field: 'role'},
//        {label: app.i18n._('Birthday'),    field: 'bday', valueType: 'date'},
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
        {label: i18n._('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
        {label: i18n._('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
        {label: i18n._('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
        {label: i18n._('Created By'),                                                        field: 'created_by',         valueType: 'user'},
        {
            label: app.i18n._('Salutation'),
            field: 'salutation',
            filtertype: 'tine.widget.keyfield.filter',
            app: app,
            keyfieldName: 'contactSalutation',
            defaultOperator: 'in'
        }
    ];
    
    if (Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureIndustry')) {
        filters.push({filtertype: 'foreignrecord', 
            app: app,
            foreignRecordClass: Tine.Addressbook.Model.Industry,
            ownField: 'industry'
        });
    }
    return filters;
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
    {name: 'seq'},
    {name: 'last_modified_by'},
    {name: 'last_modified_time'},
    {name: 'is_deleted'},
    {name: 'deleted_time'},
    {name: 'deleted_by'},
    {name: 'name'},
    {name: 'description'},
    {name: 'members'},
    {name: 'memberroles'},
    {name: 'email'},
    {name: 'type'},
    {name: 'list_type'},
    {name: 'group_id'},
    {name: 'emails'},
    {name: 'notes', omitDuplicateResolving: true},
    {name: 'paths', omitDuplicateResolving: true},
    {name: 'relations', omitDuplicateResolving: true},
    {name: 'customfields', omitDuplicateResolving: true},
    {name: 'tags'}
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

Tine.Addressbook.Model.List.getFilterModel = function() {
    
    var app = Tine.Tinebase.appMgr.get('Addressbook');
    
    var typeStore = [['list', app.i18n._('List')], ['user', app.i18n._('User Account')]];
    
    return [
        {label: i18n._('Quick Search'),                                                      field: 'query',              operators: ['contains']},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Addressbook.Model.Contact},
        {filtertype: 'addressbook.listMember', app: app, field: 'contact'},
        {label: app.i18n._('Name'),                                               field: 'name' },
        {label: app.i18n._('Description'),                                                      field: 'description',        valueType: 'text'},
        {label: i18n._('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
        {label: i18n._('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
        {label: i18n._('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
        {label: i18n._('Created By'),                                                        field: 'created_by',         valueType: 'user'},
        {filtertype: 'tinebase.tag', app: app}
    ];
};

/**
 * default list backend
 */
Tine.Addressbook.listBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Addressbook',
    modelName: 'List',
    recordClass: Tine.Addressbook.Model.List
});

/**
 * email address model
 */
Tine.Addressbook.Model.EmailAddress = Tine.Tinebase.data.Record.create([
   {name: 'n_fileas'},
   {name: 'emails'},
   {name: 'email'},
   {name: 'email_home'}
], {
    appName: 'Addressbook',
    modelName: 'EmailAddress',
    titleProperty: 'name',
    // ngettext('Email Address', 'Email Addresses', n); gettext('Email Addresses');
    recordName: 'Email Address',
    recordsName: 'Email Addresses',
    containerProperty: 'container_id',
    // ngettext('Addressbook', 'Addressbooks', n); gettext('Addressbooks');
    containerName: 'Addressbook',
    containersName: 'Addressbooks',
    copyOmitFields: ['group_id'],

    getPreferredEmail: function(preferred) {
        var emails = this.get("emails");
        if (! this.get("email") && ! this.get("email_home")) {
            return this.get("emails");
        } else {
            var preferred = preferred || 'email',
            other = preferred == 'email' ? 'email_home' : 'email';
            return (this.get(preferred) || this.get(other));
        }
    }
});


/**
 * get filtermodel of emailaddress model
 * 
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Array} filterModel definition
 */ 
Tine.Addressbook.Model.EmailAddress.getFilterModel = function() {
    return [
        {label: i18n._('Quick search'),       field: 'query',              operators: ['contains']}
    ];
};

/**
 * ListRole model
 */
Tine.Addressbook.Model.ListRole = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'members'},
    {name: 'description'}
], {
    appName: 'Addressbook',
    modelName: 'ListRole',
    titleProperty: 'name',
    // ngettext('List Function', 'List Functions', n); gettext('List Functions');
    recordName: 'List Function',
    recordsName: 'List Functions'
});

/**
 * get filtermodel of ListRole model
 *
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Array} filterModel definition
 */
Tine.Addressbook.Model.ListRole.getFilterModel = function() {
    return [
        {label: i18n._('Quick search'),       field: 'query',              operators: ['contains']}
    ];
};

/**

/**
 * Industry model
 */
Tine.Addressbook.Model.Industry = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
], {
    appName: 'Addressbook',
    modelName: 'Industry',
    titleProperty: 'name',
    // ngettext('Industry', 'Industries', n); gettext('Industries');
    recordName: 'Industry',
    recordsName: 'Industries'
});

/**
 * get filtermodel of Industry model
 *
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Array} filterModel definition
 */
Tine.Addressbook.Model.Industry.getFilterModel = function() {
    return [
        {label: i18n._('Quick search'),       field: 'query',              operators: ['contains']}
    ];
};

/**
 * Structure (fake) model
 */
Tine.Addressbook.Model.Structure = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
], {
    appName: 'Addressbook',
    modelName: 'Structure',
    titleProperty: 'name',
    // ngettext('Structure', 'Structures', n); gettext('Structures');
    recordName: 'Structure',
    recordsName: 'Structures'
});
