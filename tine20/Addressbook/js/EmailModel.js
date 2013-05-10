/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * email address model
 */
Tine.Addressbook.Model.EmailAddress = Tine.Tinebase.data.Record.create([
   {name: 'n_fileas'},
   {name: 'emails'},
   {name: 'email'},
   {name: 'email_home'},
   {name: 'n_fn'},
   {name: 'org_unit'}
   ],
   {
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

    getPreferedEmail: function(prefered) {
        var emails = this.get("emails");
        if (!this.get("email")) {
            return  this.get("emails");
        } else {
            var prefered = prefered || 'email',
            other = prefered == 'email' ? 'email_home' : 'email';
            return (this.get(prefered) || this.get(other));
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
    var app = Tine.Tinebase.appMgr.get('Addressbook');
    
    var typeStore = [['contact', app.i18n._('Contact')], ['user', app.i18n._('User Account')]];
    
    return [
        {label: _('Quick search'),       field: 'query',              operators: ['contains']}
    ];
};