/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Model.js 3083 2008-06-25 15:51:22Z twadewitz $
 *
 */

Ext.namespace('Tine.Voipmanager.Model.Snom');

Tine.Voipmanager.Model.Snom.Phone = Ext.data.Record.create([
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
    {name: 'location'},
    {name: 'template'}
]);



Tine.Voipmanager.Model.Snom.Location = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'firmware_interval'},
    {name: 'firmware_status'},
    {name: 'update_policy'},
    {name: 'setting_server'},
    {name: 'base_download_url'},
    {name: 'admin_mode'},
    {name: 'admin_mode_password'},
    {name: 'ntp_server'},
    {name: 'ntp_refresh'},
    {name: 'timezone'},
    {name: 'webserver_type'},
    {name: 'http_port'},
    {name: 'https_port'},
    {name: 'http_user'},
    {name: 'http_pass'},
    {name: 'tone_scheme'},
    {name: 'date_us_format'},
    {name: 'time_24_format'}
]);


Tine.Voipmanager.Model.Snom.Template = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'model'},
    {name: 'keylayout_id'},
    {name: 'setting_id'},
    {name: 'software_id'}
]);


Tine.Voipmanager.Model.Snom.Software = Ext.data.Record.create([
    {name: 'id'},
    {name: 'description'},
    {name: 'softwareimage_snom300'},
    {name: 'softwareimage_snom320'},
    {name: 'softwareimage_snom360'},
    {name: 'softwareimage_snom370'}
    
]);

Tine.Voipmanager.Model.Snom.SoftwareImage = Ext.data.Record.create([
    {name: 'model'},
    {name: 'softwareimage'}
]);

Tine.Voipmanager.Model.Snom.Line = Ext.data.Record.create([
    {name: 'asteriskline_id'},
    {name: 'id'},
    {name: 'idletext'},
    {name: 'lineactive'},
    {name: 'linenumber'},
    {name: 'snomphone_id'}
]);

Tine.Voipmanager.Model.Snom.Setting = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},        
    {name: 'web_language'},
    {name: 'language'},
    {name: 'display_method'},
    {name: 'mwi_notification'},
    {name: 'mwi_dialtone'},
    {name: 'headset_device'},
    {name: 'message_led_other'},
    {name: 'global_missed_counter'},
    {name: 'scroll_outgoing'},
    {name: 'show_local_line'},
    {name: 'show_call_status'},
    {name: 'redirect_event'},
    {name: 'redirect_number'},
    {name: 'redirect_always_on_code'},
    {name: 'redirect_always_off_code'},
    {name: 'redirect_busy_number'},
    {name: 'redirect_busy_on_code'},
    {name: 'redirect_busy_off_code'},
    {name: 'redirect_time'},
    {name: 'redirect_time_number'},
    {name: 'redirect_time_on_code'},
    {name: 'redirect_time_off_code'},
    {name: 'dnd_on_code'},
    {name: 'dnd_off_code'},
    {name: 'ringer_headset_device'},
    {name: 'ring_sound'},
    {name: 'alert_internal_ring_text'},
    {name: 'alert_internal_ring_sound'},
    {name: 'alert_external_ring_text'},
    {name: 'alert_external_ring_sound'},
    {name: 'alert_group_ring_text'},
    {name: 'alert_group_ring_sound'},
    {name: 'friends_ring_sound'},
    {name: 'family_ring_sound'},
    {name: 'colleagues_ring_sound'},
    {name: 'vip_ring_sound'},
    {name: 'custom_melody_url'},
    {name: 'auto_connect_indication'},
    {name: 'auto_connect_type'},
    {name: 'privacy_out'},
    {name: 'privacy_in'},
    {name: 'presence_timeout'},
    {name: 'enable_keyboard_lock'},
    {name: 'keyboard_lock'},
    {name: 'keyboard_lock_pw'},
    {name: 'keyboard_lock_emergency'},
    {name: 'emergency_proxy'},
    {name: 'call_waiting'}
]);

Ext.namespace('Tine.Voipmanager.Model.Asterisk');

Tine.Voipmanager.Model.Asterisk.SipPeer = Ext.data.Record.create([
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

Tine.Voipmanager.Model.Asterisk.Context = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
]);

Tine.Voipmanager.Model.Asterisk.Voicemail = Ext.data.Record.create([
    {name: 'id'},
    {name: 'context'},
    {name: 'mailbox'},
    {name: 'password'},
    {name: 'fullname'},
    {name: 'email'},
    {name: 'pager'},
    {name: 'tz'},
    {name: 'attach'},
    {name: 'saycid'},
    {name: 'dialout'},
    {name: 'callback'},
    {name: 'review'},
    {name: 'operator'},
    {name: 'envelope'},
    {name: 'sayduration'},
    {name: 'saydurationm'},
    {name: 'sendvoicemail'},
    {name: 'delete'},
    {name: 'nextaftercmd'},
    {name: 'forcename'},
    {name: 'forcegreetings'},
    {name: 'hidefromdir'}
]);

