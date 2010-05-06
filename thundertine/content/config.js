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

var config = {

  my_id: "thundertine@santa.noel",
  user: '',
  pwd: '',

  contactsSyncKey: 0,
  contactsLocalFolder: null,
  contactsRemoteFolder: null, 

  folderSyncKey: 0, 
  folderIds: Array(), 
  folderNames: Array(), 
  folderTypes: Array(),

  picDir: 'Photos', 

  // Array with TineSyncId of contacts to track deleted cards
  managedCards: Array(),

  write: function() {
	/*
	 * all preferences which are not subject to change with every sync are in   
	 * the thunderbird preferences and can only be edited within options dialog
	 */
	var doc = document.implementation.createDocument("", "", null);
	var config = doc.createElement("config");
	// normal keys
	var keys = new Array('contactsSyncKey', 'folderSyncKey');
	for(var i in keys) { 
		var dom = doc.createElement(keys[i]);
			var domtext = doc.createTextNode( this[keys[i]] );
			dom.appendChild(domtext);
		config.appendChild(dom);
	}
	// array folders
	var folders = doc.createElement('folders');
	for (var i=0; i<this.folderIds.length; i++) {
		folders.appendChild( doc.createElement('folder') );
		folders.lastChild.appendChild( doc.createElement('id') );
		folders.lastChild.lastChild.appendChild( doc.createTextNode(this.folderIds[i]) );
		folders.lastChild.appendChild( doc.createElement('name') );
		folders.lastChild.lastChild.appendChild( doc.createTextNode(this.folderNames[i]) );
		folders.lastChild.appendChild( doc.createElement('type') );
		folders.lastChild.lastChild.appendChild( doc.createTextNode(this.folderTypes[i]) );
	}
	config.appendChild( folders );
	// managedCards
	var dom = doc.createElement('managedCards');
		var domtext = '';
	for (i in this.managedCards)
		domtext = domtext + this.managedCards[i]+',';
	domtext = domtext.substr(0, domtext.length-1);
	dom.appendChild( doc.createTextNode(domtext) );
	config.appendChild(dom);

	doc.appendChild(config); 
	helper.dom2file("thundertine.xml", doc);
  }, 

  read: function() { 
	// data in thunderbird config
	var prefs = Components.classes["@mozilla.org/preferences-service;1"]
			.getService(Components.interfaces.nsIPrefService);
	prefs = prefs.getBranch("extensions.ttine.");
	/*
	 * IMPORTANT: If extension is loaded the very first time a unique id is required!
	 */
	if (prefs.getCharPref("deviceId") == '') {
		prefs.setCharPref("deviceId", (new Date()).getTime());
	}
	config.user = prefs.getCharPref("user");
	config.deviceId = prefs.getCharPref("deviceId");
	config.deviceType = (prefs.getBoolPref('iPhone')? 'iPhone' : 'ThunderTine');
	config.url = (prefs.getBoolPref('hostSsl')? 'https://' : 'http://') + prefs.getCharPref('host') + '/Microsoft-Server-ActiveSync'; 
	config.interval = prefs.getIntPref("syncInterval") * 60 * 1000; // in milliseconds
	config.syncBeforeClose = prefs.getBoolPref('syncBeforeClose');
	config.checkFolderBefore = prefs.getBoolPref('checkFolderBefore');
	config.contactsLocalFolder = prefs.getCharPref("contactsLocalFolder");
	config.contactsRemoteFolder = prefs.getCharPref("contactsRemoteFolder");
	config.contactsLimitPictureSize = prefs.getBoolPref("contactsLimitPictureSize");
	// get password
	var passwordManager = Components.classes["@mozilla.org/login-manager;1"]
		.getService(Components.interfaces.nsILoginManager);
	var username = config.user = prefs.getCharPref("user");
	if ( prefs.getCharPref('host') != '' && username != '') {
		var logins = passwordManager.findLogins({}, config.url, null, 'Tine 2.0 Active Sync');  
		for (var i = 0; i < logins.length; i++) { 
			if (logins[i].username == username) {
				config.pwd = logins[i].password;
			    this.initialized = true;
				break;
			}
		}
	}

	// data in file
	var doc = helper.file2dom("thundertine.xml");
	if(doc==false) 
		return false;

	for (var i=0; i<doc.firstChild.children.length; i++) {
		if (doc.firstChild.children[i].nodeName == 'folders') {
			if(doc.firstChild.children[i].children.length > 0) {
				this.folderIds = Array();
				this.folderNames = Array();
				this.folderTypes = Array();
				for (var a=0; a<doc.firstChild.children[i].children.length; a++) {
					var folder = doc.firstChild.children[i].children[a];
						for (var f=0; f<folder.children.length; f++) {
							var tag = folder.children[f].nodeName;
							var value = folder.children[f].firstChild.nodeValue;
							if (tag == 'id')
								this.folderIds.push(value);
							else if (tag == 'name')
								this.folderNames.push(value);
							else if (tag == 'type')
								this.folderTypes.push(value);
						}
				}
			}
		}
		else if (doc.firstChild.children[i].nodeName == 'managedCards') {
			if(doc.firstChild.children[i].firstChild != null)
				this['managedCards'] = doc.firstChild.children[i].firstChild.nodeValue.split(',');
			else 
				this['managedCards'] = Array();
		}
		else if (doc.firstChild.children[i].firstChild != null)
			this[doc.firstChild.children[i].nodeName] = doc.firstChild.children[i].firstChild.nodeValue;
			
	}

	// make sure there is a folder for Photos
	var dir = Components.classes["@mozilla.org/file/directory_service;1"]
		.getService(Components.interfaces.nsIProperties)
		.get("ProfD", Components.interfaces.nsIFile);
	dir.append(this.picDir);
	if( !dir.exists() || !dir.isDirectory() ) {   // if it doesn't exist, create
		dir.create(Components.interfaces.nsIFile.DIRECTORY_TYPE, 0755);
	}


	// make sure all important things are known
	var ab = parent.ab.listAbs();
	if (
		this.folderIds.indexOf(this.contactsRemoteFolder) >= 0 &&
		ab.indexOf(config.contactsLocalFolder) >= 0 &&
		config.user != '' &&
		config.deviceId != '' &&
		config.contactsLocalFolder != '' &&
		config.contactsRemoteFolder != ''
		
	)
		return true;
	else
		return false;
  }, 

  minimumConfig: function() {
	if (config.url != '' &&
		config.user != '' &&
		config.contactsLocalFolder != '' &&
		config.contactsRemoteFolder != '')
		return true;
	else
		return false;
  }

}
