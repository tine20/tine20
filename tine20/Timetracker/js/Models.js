/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Timetracker.Model');

/**
 * Model of a grant
 */
Tine.Timetracker.Model.TimeaccountGrant = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'},
    {name: 'bookOwnGrant',        type: 'boolean'},
    {name: 'viewAllGrant',        type: 'boolean'},
    {name: 'bookAllGrant',        type: 'boolean'},
    {name: 'manageBillableGrant', type: 'boolean'},
    {name: 'exportGrant',         type: 'boolean'},
    {name: 'adminGrant',          type: 'boolean'}
], {
    appName: 'Timetracker',
    modelName: 'TimeaccountGrant',
    idProperty: 'id',
    titleProperty: 'account_name',
    // ngettext('Timeaccount Grant', 'Timeaccount Grants', n); gettext('Timeaccount Grant');
    recordName: 'Timeaccount Grant',
    recordsName: 'Timeaccount Grants'
});

Ext.override(Tine.widgets.container.GrantsGrid, {
    'bookOwnGrantTitle': 'Book Own', // _('Book Own')
    'bookOwnGrantDescription': 'The grant to add Timesheets to this Timeaccount', // _('The grant to add Timesheets to this Timeaccount')
    'viewAllGrantTitle': 'View All', // _('View All')
    'viewAllGrantDescription': 'The grant to view Timesheets of other users', // _('The grant to view Timesheets of other users')
    'bookAllGrantTitle': 'Book All', // _('Book All')
    'bookAllGrantDescription': 'The grant to add Timesheets for other users', // _('The grant to add Timesheets for other users')
    'manageBillableGrantTitle': 'Manage Clearing', // _('Manage Clearing')
    'manageBillableGrantDescription': 'The grant to manage clearing of Timesheets', // _('The grant to manage clearing of Timesheets')
});
