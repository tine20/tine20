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
    {name: 'description'},
    {name: 'location_id'},
    {name: 'template_id'},
    {name: 'ipaddress'},
    {name: 'last_modified_time'},
    {name: 'current_software'},
    {name: 'current_model'},
    {name: 'settings_loaded_at'},
    {name: 'firmware_checked_at'},
    {name: 'lines'}
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
    {name: 'ntp_refresh'},
    {name: 'timezone'},
    {name: 'webserver_type'},
    {name: 'http_port'},
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


Tine.Voipmanager.Model.Line = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'accountcode'},
    {name: 'amaflags'},
    {name: 'callgroup'},
    {name: 'callerid'},
    {name: 'canreinvite'},
    {name: 'context'},
    {name: 'defaultip'},
    {name: 'dtmfmode'},
    {name: 'fromuser'},
    {name: 'fromdomain'},
    {name: 'fullcontact'},
    {name: 'host'},
    {name: 'insecure'},
    {name: 'language'},
    {name: 'mailbox'},
    {name: 'md5secret'},
    {name: 'nat'},
    {name: 'deny'},
    {name: 'permit'},
    {name: 'mask'},
    {name: 'pickupgroup'},
    {name: 'port'},
    {name: 'qualify'},
    {name: 'restrictcid'},
    {name: 'rtptimeout'},
    {name: 'rtpholdtimeout'},
    {name: 'secret'},
    {name: 'type'},
    {name: 'username'},
    {name: 'disallow'},
    {name: 'allow'},
    {name: 'musiconhold'},
    {name: 'regseconds'},
    {name: 'ipaddr'},
    {name: 'regexten'},
    {name: 'cancallforward'},
    {name: 'setvar'},
    {name: 'notifyringing'},
    {name: 'useclientcode'},
    {name: 'authuser'},
    {name: 'call-limit'},
    {name: 'busy-level'}
]);
