/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of a grant
 */
Tine.Addressbook.Model.ContactPersonalGrants = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'},
    {name: 'readGrant',    type: 'boolean'},
    {name: 'addGrant',      type: 'boolean'},
    {name: 'editGrant',      type: 'boolean'},
    {name: 'deleteGrant',    type: 'boolean'},
    {name: 'privateGrant',      type: 'boolean'},
    {name: 'exportGrant',     type: 'boolean'},
    {name: 'syncGrant',         type: 'boolean'},
    {name: 'adminGrant',        type: 'boolean'},
    {name: 'freebusyGrant',      type: 'boolean'},
    {name: 'downloadGrant',        type: 'boolean'},
    {name: 'publishGrant',    type: 'boolean'},
    {name: 'privateDataGrant',        type: 'boolean'},
], {
    appName: 'Addressbook',
    modelName: 'ContactPersonalGrants',
    idProperty: 'id',
    titleProperty: 'account_name',
    // ngettext('Contact Personal Grant', 'Contact Personal Grants', n); gettext('Contact Personal Grant');
    recordName: 'Contact Personal Grant',
    recordsName: 'Contact Personal Grants'
});

// register grants for calendar containers
Tine.widgets.container.GrantsManager.register('Addressbook_Model_Contact', function(container) {
    var _ = window.lodash,
        me = this,
        grantsModelName = _.get(container, 'xprops.Tinebase.Container.GrantsModel', 'Tinebase_Model_Grants');

    if (grantsModelName == 'Addressbook_Model_ContactPersonalGrants') {
        var grants = Tine.widgets.container.GrantsManager.defaultGrants(container);
        grants.push('privateDataGrant');
        return grants;
        
    } else {
        var grants = Tine.widgets.container.GrantsManager.defaultGrants(container);

        return grants;
    }
});
