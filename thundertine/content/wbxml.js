/*

Copyright (C) 2010 by Santa Noel

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; version 2
of the License ONLY.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

var wbxml = {

  /*
   * THIS TAGS ARE HIGHLY SPECIFIC FOR MS-ActiveSync. THEY'RE NOT USABLE FOR wbxml STANDARD IN GENERAL!!
   */

  codePages: Array(
	'AirSync', 'Contacts', 'Email', 'AirNotify', 'Calendar', 'Move', 'ItemEstimate', 'FolderHierarchy',
	'MeetingResponse', 'Tasks', 'ResolveRecipients', 'ValidateCert', 'Contacts2', 'Ping', 'Provision', 'Search',
	'Gal', 'AirSyncBase', 'Settings', 'DocumentLibrary', 'ItemOperations', 'ComposeMail', 'Email2', 'Notes'
  ),

  tags: Array(
	'Sync',
	'Responses',
	'Add',
	'Change',
	'Delete',
	'Fetch',
	'SyncKey',
	'ClientId',
	'ServerId',
	'Status',
	'Collection',
	'Class',
	'Version',
	'CollectionId',
	'GetChanges',
	'MoreAvailable',
	'WindowSize',
	'Commands',
	'Options',
	'FilterType',
	'Truncation',
	'RTFTruncation',
	'Conflict',
	'Collections',
	'ApplicationData',
	'DeletesAsMoves',
	'NotifyGUID',
	'Supported',
	'SoftDelete',
	'MIMESupport',
	'MIMETruncation',
	'Wait',
	'Limit',
	'Partial',
	'ConversationMode',
	'MaxItems',
	'HeartbeatInterval', 
	'Contacts_Anniversary',
	'Contacts_AssistantName',
	'Contacts_AssistantTelephoneNumber',
	'Contacts_Birthday',
	'Contacts_Business2PhoneNumber',
	'Contacts_BusinessCity',
	'Contacts_BusinessCountry',
	'Contacts_BusinessPostalCode',
	'Contacts_BusinessState',
	'Contacts_BusinessStreet',
	'Contacts_BusinessFaxNumber',
	'Contacts_BusinessPhoneNumber',
	'Contacts_CarPhoneNumber',
	'Contacts_Categories',
	'Contacts_Category',
	'Contacts_Children',
	'Contacts_Child',
	'Contacts_CompanyName',
	'Contacts_Department',
	'Contacts_Email1Address',
	'Contacts_Email2Address',
	'Contacts_Email3Address',
	'Contacts_FileAs',
	'Contacts_FirstName',
	'Contacts_Home2PhoneNumber',
	'Contacts_HomeCity',
	'Contacts_HomeCountry',
	'Contacts_HomePostalCode',
	'Contacts_HomeState',
	'Contacts_HomeStreet',
	'Contacts_HomeFaxNumber',
	'Contacts_HomePhoneNumber',
	'Contacts_JobTitle',
	'Contacts_LastName',
	'Contacts_MiddleName',
	'Contacts_MobilePhoneNumber',
	'Contacts_OfficeLocation',
	'Contacts_OtherCity',
	'Contacts_OtherCountry',
	'Contacts_OtherPostalCode',
	'Contacts_OtherState',
	'Contacts_OtherStreet',
	'Contacts_PagerNumber',
	'Contacts_RadioPhoneNumber',
	'Contacts_Spouse',
	'Contacts_Suffix',
	'Contacts_Title',
	'Contacts_Webpage',
	'Contacts_YomiCompanyName',
	'Contacts_YomiFirstName',
	'Contacts_YomiLastName',
	'Contacts_CompressedRTF',
	'Contacts_Picture',
	'Contacts_Alias',
	'Contacts_WeightedRank',
	'AirSyncBase_BodyPreference',
	'AirSyncBase_Type',
	'AirSyncBase_TruncationSize',
	'AirSyncBase_AllOrNone',
	'AirSyncBase_Body',
	'AirSyncBase_Data',
	'AirSyncBase_EstimatedDataSize',
	'AirSyncBase_Truncated',
	'AirSyncBase_Attachments',
	'AirSyncBase_Attachment',
	'AirSyncBase_DisplayName',
	'AirSyncBase_FileReference',
	'AirSyncBase_Method',
	'AirSyncBase_ContentId',
	'AirSyncBase_ContentLocation',
	'AirSyncBase_IsInline',
	'AirSyncBase_NativeBodyType',
	'AirSyncBase_ContentType',
	'AirSyncBase_Preview',
	'FolderHierarchy_DisplayName',
	'FolderHierarchy_ServerId',
	'FolderHierarchy_ParentId',
	'FolderHierarchy_Type',
	'FolderHierarchy_Status',
	'FolderHierarchy_Changes',
	'FolderHierarchy_Add',
	'FolderHierarchy_Delete',
	'FolderHierarchy_Update',
	'FolderHierarchy_SyncKey',
	'FolderHierarchy_FolderCreate',
	'FolderHierarchy_FolderDelete',
	'FolderHierarchy_FolderUpdate',
	'FolderHierarchy_FolderSync',
	'FolderHierarchy_Count',
	'Contacts2_CustomerId',
	'Contacts2_GovernmentId',
	'Contacts2_IMAddress',
	'Contacts2_IMAddress2',
	'Contacts2_IMAddress3',
	'Contacts2_ManagerName',
	'Contacts2_CompanyMainPhone',
	'Contacts2_AccountName',
	'Contacts2_NickName',
	'Contacts2_MMS'
  ), 

  tokens: Array(
	// AirSync
	0x05, 0x06, 0x07, 0x08, 0x09, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E,
	0x0F, 0x10, 0x11, 0x12, 0x13, 0x14, 0x15, 0x16, 0x17, 0x18,
	0x19, 0x1A, 0x1B, 0x1C, 0x1D, 0x1E, 0x1F, 0x20, 0x21, 0x22,
	0x23, 0x24, 0x25, 0x26, 0x27, 0x28, 0x29, 
	// Contacts
	0x05, 0x06, 0x07, 0x08, 0x0C, 0x0D, 0x0E, 0x0F, 0x10, 0x11,
	0x12, 0x13, 0x14, 0x15, 0x16, 0x17, 0x18, 0x19, 0x1A, 0x1B,
	0x1C, 0x1D, 0x1E, 0x1F, 0x20, 0x21, 0x22, 0x23, 0x24, 0x25,
	0x26, 0x27, 0x28, 0x29, 0x2A, 0x2B, 0x2C, 0x2D, 0x2E, 0x2F,
	0x30, 0x31, 0x32, 0x33, 0x34, 0x35, 0x36, 0x37, 0x38, 0x39,
	0x3A, 0x3B, 0x3C, 0x3D, 0x3E,
	// AirSyncBase
	0x05, 0x06, 0x07, 0x08, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E, 0x0F,
	0x10, 0x11, 0x12, 0x13, 0x14, 0x15, 0x16, 0x17, 0x18,
	// FolderHierarchy
	0x07, 0x08, 0x09, 0x0A, 0x0C, 0x0E, 0x0F, 0x10, 0x11, 0x12,
	0x13, 0x14, 0x15, 0x16, 0x17, 
	// Contacts2
	0x05, 0x06, 0x07, 0x08, 0x09, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E
  ),

  wbxml_codepage: 0,

  doWbxml: function(domstring) {  
	if (typeof domstring == 'object')
		domstring = this.domStr(domstring);
	var parser = Components.classes["@mozilla.org/xmlextras/domparser;1"]
		.createInstance(Components.interfaces.nsIDOMParser);
	var doc = parser.parseFromString(domstring, "text/xml");

	var header = String.fromCharCode(0x03,0x01,0x6A,0x00); 
	/*var header = String.fromCharCode( // mit DTD
		0x03,0x00,0x00,0x6A,0x1c,0x2d,0x2f,0x2f,0x41,0x49,0x52,0x53,0x59,0x4e,0x43,0x2f,
		0x2f,0x44,0x54,0x44,0x20,0x41,0x69,0x72,0x53,0x79,0x6e,0x63,0x2f,0x2f,0x45,0x4e,
		0x00
	);*/ 
	this.wbxml_codepage = 0; 
	var wbxml = header + this._node2wbxml(doc.firstChild);

	return wbxml;	
  },

  _node2wbxml: function(dom) { 
	// strange elements from DOM-string converting destroy rest of tree -> pass out
	if (this.tags.indexOf(dom.nodeName) < 0) 
		return "";
	var nwbxml = ''; 
	// page of current tag
	var nodeArr = dom.nodeName.split('_'); 
	if (typeof nodeArr[1] == 'undefined') 
		var aPage = 0;
	else
		var aPage = this.codePages.indexOf(nodeArr[0]); 
	if(aPage != this.wbxml_codepage) { 
		// change codePage
		nwbxml = nwbxml + String.fromCharCode(0x00) + String.fromCharCode(aPage); 
		this.wbxml_codepage = aPage; 
	}
	// open tag
	var token = this.tokens[this.tags.indexOf(dom.nodeName)]; 

	if(dom.childNodes.length > 0)
		token = token + 0x40; 
	nwbxml = nwbxml + String.fromCharCode(token);
	// childs
	if (dom.childNodes.length > 0) { 
		for (var i=0; i<dom.childNodes.length; i++) {
			if(dom.childNodes[i].nodeName == '#text')
				nwbxml = nwbxml + String.fromCharCode(0x03) + this.utf8Encode(dom.childNodes[i].nodeValue) + String.fromCharCode(0x00);
			else
				nwbxml = nwbxml + this._node2wbxml(dom.childNodes[i]);
		}	
	}
	// close tag (if children inside)
	if (dom.childNodes.length > 0) {
		nwbxml = nwbxml + String.fromCharCode(0x01); 
	}
	return nwbxml;
  },

  doXml: function(wbxml) {
	// check for wbxml input in ms airsync dialect
	if (typeof wbxml == "undefined" || String(wbxml).substr(0, 4) != String.fromCharCode(0x03,0x01,0x6A,0x00))
		return null;

	var page = 0; var cmd = ''; 
	var xml = '<?xml version="1.0" encoding="utf-8"?>'; var lastTags = new Array();
	for(var i=4; i<wbxml.length; i++) {
		var c = wbxml.charCodeAt(i); 
		// following bytes
		if (cmd != '') {
			if (cmd == 'selectPage') {
				page = c; 
				cmd = ''; 
				continue;
			}
			else if (cmd == 'inString') {
				if (c == 0x00) {
					cmd = '';
					// replace forbidden xml characters
					inString = inString.replace('<', '&lt;');
					inString = inString.replace('>', '&gt;');
					/* inString = inString.replace('"', '&quot;');
					inString = inString.replace('&', '&amp;');
					inString = inString.replace("'", '&apos;'); */
					xml = xml + inString;
				}
				else
					inString = inString + wbxml[i];
				continue;
			}
		}
		// actual bytes
		if (c==0x00) { 
			// code page changes
			cmd = 'selectPage';
			continue;
		}
		else if (c==0x03) {
			// string follows
			var inString = '';
			cmd = 'inString';
			continue;
		}
		else if (c==0x01) {
			// end tag
			xml = xml + '</' + lastTags.pop()+ '>';
		}
		else if (c>=0x05) { 
			// remove type addition from tags
			if(c > 0xC0) c = c - 0xC0;
			else if(c > 0x80) c = c - 0x80;
			else if(c > 0x40) c = c - 0x40;
			// find tag
			var acp = this.codePages[page]; 
			var acp_i = 0;
			var tag = this.tags[this.tokens.indexOf(c, acp_i)]; 
			// scan all tokens until the one for the right codepage is found
			if(page > 0) {
				while (acp_i < this.tags.length && tag.substr(0, acp.length) != acp ) { 
					tag = this.tags[this.tokens.indexOf(c, acp_i)]; 
					//if(tag.substr(0, acp.length) != acp) 
					acp_i = this.tokens.indexOf(c, acp_i) + 1; 
				} 
			} 
			lastTags.push(tag); 
			xml = xml + '<' + tag + '>'; 
		}	
		
	} 
	// final tag
	if (lastTags.length > 0)
		xml = xml + '</' + lastTags.pop()+ '>'; 
	// make Dom out of it
	try {
		var parser = Components.classes["@mozilla.org/xmlextras/domparser;1"]
			.createInstance(Components.interfaces.nsIDOMParser);
		return parser.parseFromString(xml, "text/xml");
	}
	catch (err) {
		helper.prompt("The server didn't response valid dom structure \n" + err);
	}
  }, 

  domStr: function(dom) {
	var serializer = new XMLSerializer();
	return serializer.serializeToString(dom);
  }, 

  utf8Encode: function (string) {
	string = string.replace(/\r\n/g,"\n"); 
	var utf8string = "";
	for (var n = 0; n < string.length; n++) {
		var c = string.charCodeAt(n);
		if (c < 128) {
			utf8string += String.fromCharCode(c);
		}
		else if((c > 127) && (c < 2048)) {
			utf8string += String.fromCharCode((c >> 6) | 192);
			utf8string += String.fromCharCode((c & 63) | 128);
		}
		else {
			utf8string += String.fromCharCode((c >> 12) | 224);
			utf8string += String.fromCharCode(((c >> 6) & 63) | 128);
			utf8string += String.fromCharCode((c & 63) | 128);
		}
	}
	return utf8string;
  },
 
  utf8Decode: function (utf8string) {
	var string = "";
	var i = 0; var c = c1 = c2 = 0;
	while ( i < utf8string.length ) {
		c = utf8string.charCodeAt(i);
		if (c < 128) {
			utf8string += String.fromCharCode(c);
			i++;
		}
		else if((c > 191) && (c < 224)) {
			c2 = utf8string.charCodeAt(i+1);
			string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
			i += 2;
		}
		else {
			c2 = utf8string.charCodeAt(i+1);
			c3 = utf8string.charCodeAt(i+2);
			utf8string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
			i += 3;
		}
	}
	return string;
  }, 

  httpRequest: function(xml, command) { 
	// set default function values
	if (typeof command == 'undefined')
		command = 'Sync'; 

	if (typeof xml == 'string')
		var wbxml = this.doWbxml(xml);
	else {
		var serializer = new XMLSerializer();
		var wbxml = this.doWbxml( serializer.serializeToString(xml) );
	}

	// request
	var req = new XMLHttpRequest(); 
	req.mozBackgroundRequest = true; 
	req.open("POST", config.url+'?Cmd='+command+'&User='+config.user+'&DeviceId=ThunderTine'+config.deviceId+'&DeviceType='+config.deviceType, true);
	req.overrideMimeType('application/vnd.ms-sync.wbxml'); 
    req.setRequestHeader("Content-Type", 'application/vnd.ms-sync.wbxml');
	req.setRequestHeader("Authorization", 'Basic '+btoa(config.user+':'+config.pwd));
	req.setRequestHeader("MS-ASProtocolVersion", '2.5');
	req.setRequestHeader("Content-Length", wbxml.length); 
	req.onload = function () {
		if (req.readyState == 4) {
			if (req.status == 200) {
				if(req.getResponseHeader('X-API')!='http://www.tine20.org/apidocs/tine20/') 
					helper.prompt(ttine.strings.getString('notTine'));
				sync.dispatch(req);
			}
			else 
				sync.failed('http', req);
		} 
	}
	req.sendAsBinary(wbxml);
  }

}

