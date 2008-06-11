/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager.Model');

Tine.Voipmanager.Model.Phone = Ext.data.Record.create([
    {name: 'id'},
    {name: 'macaddress'},
    {name: 'location_id'},
    {name: 'template_id'},
    {name: 'ipaddress'},
    {name: 'last_modified_time'},
    {name: 'description'},
    {name: 'location'},
    {name: 'template'}
]);



Tine.Voipmanager.Model.Location = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'firmware_interval'},
    {name: 'firmware_status'},
    {name: 'update_policy'},
    {name: 'setting_server'},
    {name: 'admin_mode'},
    {name: 'admin_mode_password'},
    {name: 'ntp_server'},
    {name: 'webserver_type'},
    {name: 'https_port'},
    {name: 'http_user'},
    {name: 'http_pass'}
]);


Tine.Voipmanager.Model.Template = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'model'},
    {name: 'keylayout_id'},
    {name: 'setting_id'},
    {name: 'software_id'}
]);


Tine.Voipmanager.Model.Software = Ext.data.Record.create([
    {name: 'id'},
    {name: 'description'},
    {name: 'model'},
    {name: 'softwareimage'}
]);
