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
Tine.Calendar.Model.ResourceGrants = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'},
    {name: 'resourceInviteGrant',    type: 'boolean'},
    {name: 'resourceReadGrant',      type: 'boolean'},
    {name: 'resourceEditGrant',      type: 'boolean'},
    {name: 'resourceExportGrant',    type: 'boolean'},
    {name: 'resourceSyncGrant',      type: 'boolean'},
    {name: 'resourceAdminGrant',     type: 'boolean'},
    {name: 'eventsAddGrant',         type: 'boolean'},
    {name: 'eventsReadGrant',        type: 'boolean'},
    {name: 'eventsExportGrant',      type: 'boolean'},
    {name: 'eventsSyncGrant',        type: 'boolean'},
    {name: 'eventsFreebusyGrant',    type: 'boolean'},
    {name: 'eventsEditGrant',        type: 'boolean'},
    {name: 'eventsDeleteGrant',      type: 'boolean'}
], {
    appName: 'Calendar',
    modelName: 'ResourceGrants',
    idProperty: 'id',
    titleProperty: 'account_name',
    // ngettext('Resource Grant', 'Resource Grants', n); gettext('Resource Grant');
    recordName: 'Resource Grant',
    recordsName: 'Resource Grants'
});

// register grants for calendar containers
Tine.widgets.container.GrantsManager.register('Calendar_Model_Event', function(container) {
    var _ = window.lodash,
        me = this,
        grantsModelName = _.get(container, 'xprops.Tinebase.Container.GrantsModel', 'Tinebase_Model_Grants');

    if (grantsModelName == 'Calendar_Model_ResourceGrants') {
        // resource events container
        return [
            'resourceInvite',
            'resourceRead',
            'resourceEdit',
            // 'resourceExport', // should be resource-admin?
            // 'resourceSync',   // no sync targets - let's save space
            'resourceAdmin',  // not yet used? - let's save space
            'eventsAdd',
            'eventsRead',
            'eventsExport',
            'eventsSync',
            'eventsFreebusy',
            'eventsEdit',
            'eventsDelete'
        ];

    } else {
        var grants = Tine.widgets.container.GrantsManager.defaultGrants(container);

        // normal events container
        if (container.type == 'personal') {
            grants.push('freebusy');
        }
        if (container.type == 'personal' && container.capabilites_private) {
            grants.push('private');
        }

        return grants;
    }
});

Ext.override(Tine.widgets.container.GrantsGrid, {
    resourceInviteGrantTitle: 'Invite Resource', // i18n._('Invite Resource')
    resourceInviteGrantDescription: 'The grant to invite the resource to an event', // i18n._('The grant to invite the resource to an event')
    resourceReadGrantTitle: 'Read Resource', // i18n._('Read Resource')
    resourceReadGrantDescription: 'The grant to read the resource itself', // i18n._('The grant to read the resource itself')
    resourceEditGrantTitle: 'Edit Resource', // i18n._('Edit Resource')
    resourceEditGrantDescription: 'The grant to edit the resource itself', // i18n._('The grant to edit the resource itself')
    resourceExportGrantTitle: 'Export Resource', // i18n._('Export Resource')
    resourceExportGrantDescription: 'The grant to export the resource itself', // i18n._('The grant to export the resource itself')
    resourceSyncGrantTitle: 'Sync Resource', // i18n._('Sync Resource')
    resourceSyncGrantDescription: 'The grant to synchronise the resource itself', // i18n._('The grant to synchronise the resource itself')
    resourceAdminGrantTitle: 'Resource Admin', // i18n._('Resource Admin')
    resourceAdminGrantDescription: 'The grant to administrate the resource itself', // i18n._('The grant to administrate the resource itself')
    eventsAddGrantTitle: 'Add Events', // i18n._('Add Events')
    eventsAddGrantDescription: 'The grant to directly add events into this resource calendar', // i18n._('The grant to directly add events into this resource calendar')
    eventsReadGrantTitle: 'Read Events', // i18n._('Read Events')
    eventsReadGrantDescription: 'The grant to read events from this resource calendar', // i18n._('The grant to read events from this resource calendar')
    eventsExportGrantTitle: 'Export Events', // i18n._('Export Events')
    eventsExportGrantDescription: 'The grant to export events from this resource calendar', // i18n._('The grant to export events from this resource calendar')
    eventsSyncGrantTitle: 'Sync Events', // i18n._('Sync Events')
    eventsSyncGrantDescription: 'The grant to synchronise events from this resource calendar. Needs the grant read events', // i18n._('The grant to synchronise events from this resource calendar. Need Read Events Grant')
    eventsFreebusyGrantTitle: 'Events Free Busy', // i18n._('Events Free Busy')
    eventsFreebusyGrantDescription: 'The grant to get free/busy information of events from this resource calendar', // i18n._('The grant to get free/busy information of events from this resource calendar')
    eventsEditGrantTitle: 'Edit Events', // i18n._('Edit Events')
    eventsEditGrantDescription: 'The grant to respond to event invitations of this resource and to edit events directly saved in this resource calendar', // i18n._('The grant to respond to event invitations of this resource and to edit events directly saved in this resource calendar')
    eventsDeleteGrantTitle: 'Delete Events', // i18n._('Delete Events')
    eventsDeleteGrantDescription: 'The grant to delete events directly stored in this resource calendar', // i18n._('The grant to delete events directly stored in this resource calendar')

    freebusyGrantTitle: 'Free Busy', // i18n._('Free Busy')
    freebusyGrantDescription: 'The grant to get free/busy information of events in this calendar', // i18n._('The grant to get free/busy information of events in this calendar')
    privateGrantTitle: 'Private', // i18n._('Private')
    privateGrantDescription: 'The grant to access events marked as private in this calendar', // i18n._('The grant to access events marked as private in this calendar')

});