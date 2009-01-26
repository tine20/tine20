/*
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Model.js 3083 2008-06-25 15:51:22Z twadewitz $
 *
 */

Ext.ns('Tine.Voipmanager', 'Tine.Voipmanager.Model.Snom');

/*
 *  SNOM
 */


/**
 * @type {Array}
 * Voipmanager model fields
 */
Tine.Voipmanager.Model.Snom.PhoneArray = Tine.Tinebase.Model.genericFields.concat([ 
    {name: 'id'},
    {name: 'macaddress'},
    {name: 'description'},
    {name: 'location_id'},
    {name: 'template_id'},
    {name: 'settings_id'},    
    {name: 'ipaddress'},
    {name: 'last_modified_time'},
    {name: 'current_software'},
    {name: 'current_model'},
    {name: 'settings_loaded_at'},
    {name: 'firmware_checked_at'},
    {name: 'location'},
    {name: 'template'},
    {name: 'redirect_event'},
    {name: 'redirect_number'},
    {name: 'redirect_time'},
    {name: 'http_client_info_sent'},    
    {name: 'http_client_user'},    
    {name: 'http_client_pass'},
    {name: 'setting_id'},
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
    {name: 'call_waiting'},
    {name: 'web_language_writable'},
    {name: 'language_writable'},
    {name: 'display_method_writable'},
    {name: 'call_waiting_writable'},
    {name: 'mwi_notification_writable'},
    {name: 'mwi_dialtone_writable'},
    {name: 'headset_device_writable'},
    {name: 'message_led_other_writable'},
    {name: 'global_missed_counter_writable'},
    {name: 'scroll_outgoing_writable'},
    {name: 'show_local_line_writable'},
    {name: 'show_call_status_writable'},
    {name: 'lines'}
]);

/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Snom.Phone = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Snome.PhoneArray, {
    appName: 'Voipmanager',
    modelName: 'Phone',
    idProperty: 'id',
    titleProperty: 'macaddress',
    // ngettext('Phone', 'Phones', n);
    recordName: 'Phone',
    recordsName: 'Phones',
    containerProperty: 'phone_id',
    // ngettext('phones list', 'phones lists', n);
    containerName: 'phones list',
    containersName: 'phones lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('macaddress')) : false;
    }
});
Tine.Voipmanager.Model.Snom.Phone.getDefaultData = function() { 
    return {
        
    }
};




Tine.Voipmanager.Model.Snom.LocationArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'firmware_interval'},
    {name: 'update_policy'},
    {name: 'registrar'},
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
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Snom.Location = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Snome.LocationArray, {
    appName: 'Voipmanager',
    modelName: 'Location',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Location', 'Locations', n);
    recordName: 'Location',
    recordsName: 'Locations',
    containerProperty: 'location_id',
    // ngettext('locations list', 'locations lists', n);
    containerName: 'locations list',
    containersName: 'locations lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('name')) : false;
    }
});
Tine.Voipmanager.Model.Snom.Location.getDefaultData = function() { 
    return {
        
    }
};




Tine.Voipmanager.Model.Snom.TemplateArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'keylayout_id'},
    {name: 'setting_id'},
    {name: 'software_id'}
]);
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Snom.Template = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Snome.TemplateArray, {
    appName: 'Voipmanager',
    modelName: 'Template',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Template', 'Templates', n);
    recordName: 'Template',
    recordsName: 'Templates',
    containerProperty: 'template_id',
    // ngettext('templates list', 'templates lists', n);
    containerName: 'templates list',
    containersName: 'templates lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('name')) : false;
    }
});
Tine.Voipmanager.Model.Snom.Template.getDefaultData = function() { 
    return {
     
    }
};




Tine.Voipmanager.Model.Snom.SoftwareArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'softwareimage_snom300'},
    {name: 'softwareimage_snom320'},
    {name: 'softwareimage_snom360'},
    {name: 'softwareimage_snom370'}   
]);
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Snom.Software = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Snome.SoftwareArray, {
    appName: 'Voipmanager',
    modelName: 'Software',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Software', 'Softwares', n);
    recordName: 'Software',
    recordsName: 'Softwares',
    containerProperty: 'software_id',
    // ngettext('softwares list', 'softwares lists', n);
    containerName: 'softwares list',
    containersName: 'softwares lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('name')) : false;
    }
});
Tine.Voipmanager.Model.Snom.Software.getDefaultData = function() { 
    return {
        
    }
};




Tine.Voipmanager.Model.Snom.SoftwareImageArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'model'},
    {name: 'softwareimage'}
]);
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Snom.SoftwareImage = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Snome.SoftwareImageArray, {
    appName: 'Voipmanager',
    modelName: 'SoftwareImage',
    idProperty: 'model',
    titleProperty: 'softwareimage',
    // ngettext('SoftwareImage', 'SoftwareImages', n);
    recordName: 'SoftwareImage',
    recordsName: 'SoftwareImages',
    containerProperty: 'softwareImage_model',
    // ngettext('softwareImages list', 'softwareImages lists', n);
    containerName: 'softwareImages list',
    containersName: 'softwareImages lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('model')) : false;
    }
});
Tine.Voipmanager.Model.Snom.SoftwareImage.getDefaultData = function() { 
    return {
        
    }
};




Tine.Voipmanager.Model.Snom.LineArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'asteriskline_id'},
    {name: 'id'},
    {name: 'idletext'},
    {name: 'lineactive'},
    {name: 'linenumber'},
    {name: 'snomphone_id'}
]);
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Snom.Line = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Snome.LineArray, {
    appName: 'Voipmanager',
    modelName: 'Line',
    idProperty: 'id',
    titleProperty: 'linenumber',
    // ngettext('Line', 'Lines', n);
    recordName: 'Line',
    recordsName: 'Lines',
    containerProperty: 'line_id',
    // ngettext('lines list', 'lines lists', n);
    containerName: 'lines list',
    containersName: 'lines lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('linenumber')) : false;
    }
});
Tine.Voipmanager.Model.Snom.Line.getDefaultData = function() { 
    return {
        
    }
};



Tine.Voipmanager.Model.Snom.SettingArray = Tine.Tinebase.Model.genericFields.concat([
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
    {name: 'call_waiting'},
    {name: 'web_language_writable'},
    {name: 'language_writable'},
    {name: 'display_method_writable'},
    {name: 'call_waiting_writable'},
    {name: 'mwi_notification_writable'},
    {name: 'mwi_dialtone_writable'},
    {name: 'headset_device_writable'},
    {name: 'message_led_other_writable'},
    {name: 'global_missed_counter_writable'},
    {name: 'scroll_outgoing_writable'},
    {name: 'show_local_line_writable'},
    {name: 'show_call_status_writable'}
]);
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Snom.Setting = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Snome.SettingArray, {
    appName: 'Voipmanager',
    modelName: 'Setting',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Setting', 'Settings', n);
    recordName: 'Setting',
    recordsName: 'Settings',
    containerProperty: 'setting_id',
    // ngettext('settings list', 'settings lists', n);
    containerName: 'settings list',
    containersName: 'settings lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('name')) : false;
    }
});
Tine.Voipmanager.Model.Snom.Setting.getDefaultData = function() { 
    return {
        
    }
};




Tine.Voipmanager.Model.Snom.OwnerArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'accountDisplayName'}
]);
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Snom.Owner = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Snom.OwnerArray, {
    appName: 'Voipmanager',
    modelName: 'Owner',
    idProperty: 'account_id',
    titleProperty: 'accountDisplayName',
    // ngettext('Owner', 'Owners', n);
    recordName: 'Owner',
    recordsName: 'Owners',
    containerProperty: 'account_id',
    // ngettext('owners list', 'owners lists', n);
    containerName: 'owners list',
    containersName: 'owners lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('accountDisplayName')) : false;
    }
});
Tine.Voipmanager.Model.Snom.Owner.getDefaultData = function() { 
    return {
        account_id: Tine.Tinebase.registry.get('currentAccount')
    }
};




/*
 *  ASTERISK
 */

Ext.namespace('Tine.Voipmanager.Model.Asterisk');

Tine.Voipmanager.Model.Asterisk.SipPeerArray = Tine.Tinebase.Model.genericFields.concat([
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
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Asterisk.SipPeer = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Asterisk.SipPeerArray, {
    appName: 'Voipmanager',
    modelName: 'SipPeer',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('SipPeer', 'SipPeers', n);
    recordName: 'SipPeer',
    recordsName: 'SipPeers',
    containerProperty: 'sipPeer_id',
    // ngettext('sipPeers list', 'sipPeers lists', n);
    containerName: 'sipPeers list',
    containersName: 'sipPeers lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('name')) : false;
    }
});
Tine.Voipmanager.Model.Asterisk.SipPeer.getDefaultData = function() { 
    return {
        
    }
};




Tine.Voipmanager.Model.Asterisk.ContextArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
]);
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Asterisk.Context = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Asterisk.ContextArray, {
    appName: 'Voipmanager',
    modelName: 'SipPeer',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Context', 'Contexts', n);
    recordName: 'Context',
    recordsName: 'Contexts',
    containerProperty: 'context_id',
    // ngettext('contexts list', 'contexts lists', n);
    containerName: 'contexts list',
    containersName: 'contexts lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('name')) : false;
    }
});
Tine.Voipmanager.Model.Asterisk.Context.getDefaultData = function() { 
    return {
        
    }
};





Tine.Voipmanager.Model.Asterisk.VoicemailArray = Tine.Tinebase.Model.genericFields.concat([
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
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Asterisk.Voicemail = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Asterisk.VoicemailArray, {
    appName: 'Voipmanager',
    modelName: 'Voicemail',
    idProperty: 'id',
    titleProperty: 'context',
    // ngettext('Voicemail', 'Voicemails', n);
    recordName: 'Voicemail',
    recordsName: 'Voicemails',
    containerProperty: 'voicemail_id',
    // ngettext('voicemails list', 'voicemails lists', n);
    containerName: 'voicemails list',
    containersName: 'voicemails lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('context')) : false;
    }
});
Tine.Voipmanager.Model.Asterisk.Voicemail.getDefaultData = function() { 
    return {
        
    }
};




Tine.Voipmanager.Model.Asterisk.MeetmeArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'confno'},
    {name: 'pin'},
	{name: 'adminpin'}
]);
/**
 * @type {Tine.Tinebase.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.Asterisk.Meetme = Tine.Tinebase.Record.create(Tine.Voipmanager.Model.Asterisk.MeetmeArray, {
    appName: 'Voipmanager',
    modelName: 'Meetme',
    idProperty: 'id',
    titleProperty: 'confno',
    // ngettext('Meetme', 'Meetmes', n);
    recordName: 'Meetme',
    recordsName: 'Meetmes',
    containerProperty: 'meetme_id',
    // ngettext('meetmes list', 'meetmes lists', n);
    containerName: 'meetmes list',
    containersName: 'meetmes lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('confno')) : false;
    }
});
Tine.Voipmanager.Model.Asterisk.Meetme.getDefaultData = function() { 
    return {
        
    }
};