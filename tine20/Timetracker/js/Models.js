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
Tine.Timetracker.Model.TimeaccountGrant = Ext.data.Record.create([
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
]);
