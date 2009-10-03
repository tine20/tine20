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

Ext.ns('Tine.Voipmanager', 'Tine.Voipmanager.Model');

/*
 *  SNOM
 */


/**
 * @type {Array}
 * Voipmanager model fields
 */
Tine.Voipmanager.Model.SnomPhoneArray = Tine.Tinebase.Model.genericFields.concat([ 
    {name: 'id'},
    {name: 'macaddress'},
    {name: 'description'},
    {name: 'location_id'},
    {name: 'template_id'},
    {name: 'settings_id'},    
    {name: 'ipaddress'},
    {name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'current_software'},
    {name: 'current_model'},
    {name: 'settings_loaded_at', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'firmware_checked_at', type: 'date', dateFormat: Date.patterns.ISO8601Long},
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
    {name: 'lines'},
    {name: 'rights'}
]);

/**
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.SnomPhone = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.SnomPhoneArray, {
    appName: 'Voipmanager',
    modelName: 'SnomPhone',
    idProperty: 'id',
    titleProperty: 'description',
    // ngettext('Phone', 'Phones', n);
    recordName: 'SnomPhone',
    recordsName: 'SnomPhones',
    containerProperty: 'phone_id',
    // ngettext('phones list', 'phones lists', n);
    containerName: 'phones list',
    containersName: 'phones lists',
    getTitle: function() {
        return this.get('description') ? (this.get('description') + ' ' + this.get('macaddress')) : false;
    }
});
Tine.Voipmanager.Model.SnomPhone.getDefaultData = function() { 
    return {
        
    };
};




Tine.Voipmanager.Model.SnomLocationArray = Tine.Tinebase.Model.genericFields.concat([
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
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.SnomLocation = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.SnomLocationArray, {
    appName: 'Voipmanager',
    modelName: 'SnomLocation',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Location', 'Locations', n);
    recordName: 'SnomLocation',
    recordsName: 'SnomLocations',
    containerProperty: 'location_id',
    // ngettext('locations list', 'locations lists', n);
    containerName: 'locations list',
    containersName: 'locations lists',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});
Tine.Voipmanager.Model.SnomLocation.getDefaultData = function() { 
    return {
        
    };
};




Tine.Voipmanager.Model.SnomTemplateArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'keylayout_id'},
    {name: 'setting_id'},
    {name: 'software_id'}
]);
/**
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.SnomTemplate = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.SnomTemplateArray, {
    appName: 'Voipmanager',
    modelName: 'SnomTemplate',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Template', 'Templates', n);
    recordName: 'SnomTemplate',
    recordsName: 'SnomTemplates',
    containerProperty: 'template_id',
    // ngettext('templates list', 'templates lists', n);
    containerName: 'templates list',
    containersName: 'templates lists',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});
Tine.Voipmanager.Model.SnomTemplate.getDefaultData = function() { 
    return {
     
    };
};




Tine.Voipmanager.Model.SnomSoftwareArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'softwareimage_snom300'},
    {name: 'softwareimage_snom320'},
    {name: 'softwareimage_snom360'},
    {name: 'softwareimage_snom370'}   
]);
/**
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.SnomSoftware = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.SnomSoftwareArray, {
    appName: 'Voipmanager',
    modelName: 'SnomSoftware',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Software', 'Softwares', n);
    recordName: 'SnomSoftware',
    recordsName: 'SnomSoftwares',
    containerProperty: 'software_id',
    // ngettext('softwares list', 'softwares lists', n);
    containerName: 'softwares list',
    containersName: 'softwares lists',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});
Tine.Voipmanager.Model.SnomSoftware.getDefaultData = function() { 
    return {
        
    };
};




Tine.Voipmanager.Model.SnomSoftwareImageArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'model'},
    {name: 'softwareimage'}
]);
/**
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.SnomSoftwareImage = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.SnomSoftwareImageArray, {
    appName: 'Voipmanager',
    modelName: 'SnomSoftwareImage',
    idProperty: 'model',
    titleProperty: 'softwareimage',
    // ngettext('SoftwareImage', 'SoftwareImages', n);
    recordName: 'SnomSoftwareImage',
    recordsName: 'SnomSoftwareImages',
    containerProperty: 'softwareImage_model',
    // ngettext('softwareImages list', 'softwareImages lists', n);
    containerName: 'softwareImages list',
    containersName: 'softwareImages lists',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});
Tine.Voipmanager.Model.SnomSoftwareImage.getDefaultData = function() { 
    return {
        
    };
};




Tine.Voipmanager.Model.SnomLineArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'asteriskline_id'},
    {name: 'id'},
    {name: 'idletext'},
    {name: 'lineactive'},
    {name: 'linenumber'},
    {name: 'snomphone_id'},
    {name: 'name'}
]);
/**
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.SnomLine = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.SnomLineArray, {
    appName: 'Voipmanager',
    modelName: 'SnomLine',
    idProperty: 'id',
    titleProperty: 'linenumber',
    // ngettext('Line', 'Lines', n);
    recordName: 'SnomLine',
    recordsName: 'SnomLines',
    containerProperty: 'line_id',
    // ngettext('lines list', 'lines lists', n);
    containerName: 'lines list',
    containersName: 'lines lists',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});
Tine.Voipmanager.Model.SnomLine.getDefaultData = function() { 
    return {
        
    };
};



Tine.Voipmanager.Model.SnomSettingArray = Tine.Tinebase.Model.genericFields.concat([
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
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.SnomSetting = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.SnomSettingArray, {
    appName: 'Voipmanager',
    modelName: 'SnomSetting',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Setting', 'Settings', n);
    recordName: 'SnomSetting',
    recordsName: 'SnomSettings',
    containerProperty: 'setting_id',
    // ngettext('setting list', 'settings lists', n);
    containerName: 'setting list',
    containersName: 'setting lists',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});
Tine.Voipmanager.Model.SnomSetting.getDefaultData = function() { 
    return {
        
    };
};


/**
 * Model of a right
 */
Tine.Voipmanager.Model.SnomPhoneRight = Ext.data.Record.create([
    {name: 'id'},
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'}
]);

/*
 * @deprecated
 */
/*

Tine.Voipmanager.Model.SnomOwnerArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'}

    //{name: 'accountDisplayName'}
]);
Tine.Voipmanager.Model.SnomOwner = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.SnomOwnerArray, {
    appName: 'Voipmanager',
    modelName: 'SnomOwner',
    idProperty: 'account_id',
    titleProperty: 'account_name',
    // ngettext('Owner', 'Owners', n);
    recordName: 'SnomOwner',
    recordsName: 'SnomOwners',
    containerProperty: 'account_id',
    // ngettext('owners list', 'owners lists', n);
    containerName: 'owners list',
    containersName: 'owners lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('account_name')) : false;
    }
});
Tine.Voipmanager.Model.SnomOwner.getDefaultData = function() { 
    return {
        account_id: Tine.Tinebase.registry.get('currentAccount')
    }
};
*/



/*
 *  ASTERISK
 */



Tine.Voipmanager.Model.AsteriskSipPeerArray = Tine.Tinebase.Model.genericFields.concat([
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
    {name: 'defaultuser'},
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
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.AsteriskSipPeer = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.AsteriskSipPeerArray, {
    appName: 'Voipmanager',
    modelName: 'AsteriskSipPeer',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('SipPeer', 'SipPeers', n);
    recordName: 'AsteriskSipPeer',
    recordsName: 'AsteriskSipPeers',
    containerProperty: 'sipPeer_id',
    // ngettext('sipPeers list', 'sipPeers lists', n);
    containerName: 'sipPeers list',
    containersName: 'sipPeers lists',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});
Tine.Voipmanager.Model.AsteriskSipPeer.getDefaultData = function() { 
    return {
        
    };
};




Tine.Voipmanager.Model.AsteriskContextArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
]);
/**
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.AsteriskContext = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.AsteriskContextArray, {
    appName: 'Voipmanager',
    modelName: 'AsteriskContext',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Context', 'Contexts', n);
    recordName: 'AsteriskContext',
    recordsName: 'AsteriskContexts',
    containerProperty: 'context_id',
    // ngettext('contexts list', 'contexts lists', n);
    containerName: 'contexts list',
    containersName: 'contexts lists',
    getTitle: function() {
        return this.get('name') ? this.get('name') : false;
    }
});
Tine.Voipmanager.Model.AsteriskContext.getDefaultData = function() { 
    return {
        
    };
};





Tine.Voipmanager.Model.AsteriskVoicemailArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'context_id'},
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
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.AsteriskVoicemail = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.AsteriskVoicemailArray, {
    appName: 'Voipmanager',
    modelName: 'AsteriskVoicemail',
    idProperty: 'id',
    titleProperty: 'mailbox',
    // ngettext('Voicemail', 'Voicemails', n);
    recordName: 'AsteriskVoicemail',
    recordsName: 'AsteriskVoicemails',
    containerProperty: 'voicemail_id',
    // ngettext('voicemails list', 'voicemails lists', n);
    containerName: 'voicemails list',
    containersName: 'voicemails lists',
    getTitle: function() {
        return this.get('mailbox') ? this.get('mailbox') : false;
    }
});
Tine.Voipmanager.Model.AsteriskVoicemail.getDefaultData = function() { 
    return {
        
    };
};




Tine.Voipmanager.Model.AsteriskMeetmeArray = Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'confno'},
    {name: 'pin'},
	{name: 'adminpin'}
]);
/**
 * @type {Tine.Tinebase.data.Record}
 * Voipmanager record definition
 */
Tine.Voipmanager.Model.AsteriskMeetme = Tine.Tinebase.data.Record.create(Tine.Voipmanager.Model.AsteriskMeetmeArray, {
    appName: 'Voipmanager',
    modelName: 'AsteriskMeetme',
    idProperty: 'id',
    titleProperty: 'confno',
    // ngettext('Meetme', 'Meetmes', n);
    recordName: 'AsteriskMeetme',
    recordsName: 'AsteriskMeetmes',
    containerProperty: 'meetme_id',
    // ngettext('meetmes list', 'meetmes lists', n);
    containerName: 'meetmes list',
    containersName: 'meetmes lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('confno')) : false;
    }
});
Tine.Voipmanager.Model.AsteriskMeetme.getDefaultData = function() { 
    return {
        
    };
};
