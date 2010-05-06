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

  var prefs = Components.classes["@mozilla.org/preferences-service;1"]
  		.getService(Components.interfaces.nsIPrefService);
  prefs = prefs.getBranch("extensions.ttine.");

  var deviceId = prefs.getCharPref('deviceId');

	// if under Windows Button Cancel is pressed, remember pwd and config
	var oldpwd = '';
	var oldConfig = { }

  var ttine = { } 
  ttine.strings = window.opener.document.getElementById("ttine-strings");

  function onopen() {
	// save current values to restore it on cancel
	oldConfig = config;
	// get password and clear it in manager (to store it at close again)
	var passwordManager = Components.classes["@mozilla.org/login-manager;1"]
		.getService(Components.interfaces.nsILoginManager);
	var url = 
		(document.getElementById('hostSsl').checked ? 'https://' : 'http://') + 
		document.getElementById('host').value + '/Microsoft-Server-ActiveSync'; 
	var nsLoginInfo = new Components.Constructor("@mozilla.org/login-manager/loginInfo;1",
		Components.interfaces.nsILoginInfo, "init");
	var username = document.getElementById('user').value;
	if ( document.getElementById('host').value != '' && username != '') {
		var logins = passwordManager.findLogins({}, url, null, 'Tine 2.0 Active Sync');  
		for (var i = 0; i < logins.length; i++) { 
			if (logins[i].username == username) {
				var loginInfo = new nsLoginInfo(
					url, null, 'Tine 2.0 Active Sync', document.getElementById('user').value, 
					logins[i].password, '', ''
				);
				passwordManager.removeLogin(loginInfo); 
				document.getElementById('password').value = logins[i].password;
				oldpwd = logins[i].password;
				break;
			}
		}
	}
	// load contacts pane
	localAbs();
	remoteFolders();
  }

  function onclose(ok) {
	// linux close button or Windows OK Button pressed
	if (document.getElementById('ThundertinePreferences').instantApply || ok) { 
		var passwordManager = Components.classes["@mozilla.org/login-manager;1"]
			.getService(Components.interfaces.nsILoginManager);
		var url = 
			(document.getElementById('hostSsl').checked ? 'https://' : 'http://') + 
			document.getElementById('host').value + '/Microsoft-Server-ActiveSync';
		var nsLoginInfo = new Components.Constructor("@mozilla.org/login-manager/loginInfo;1",
			Components.interfaces.nsILoginInfo, "init");
		var loginInfo = new nsLoginInfo(
			url, null, 'Tine 2.0 Active Sync', document.getElementById('user').value, 
			document.getElementById('password').value, '', ''
		);
		// if not empty -> store password
		if ( document.getElementById('host').value != '' &&
			document.getElementById('user').value != '' &&
			document.getElementById('password').value ) {
			passwordManager.addLogin(loginInfo); 
		}
		// store (valid) folder settings
		if (document.getElementById('localContactsFolder').value != null)
			prefs.setCharPref('contactsLocalFolder', document.getElementById('localContactsFolder').value);
		else
			prefs.setCharPref('contactsLocalFolder', ''); 
		if (document.getElementById('remoteContactsFolder').value != null)
			prefs.setCharPref('contactsRemoteFolder', document.getElementById('remoteContactsFolder').value);
		else {
			prefs.setCharPref('contactsRemoteFolder', '');
			config.folderSyncKey = 0;
			config.folderIds = Array();
			config.folderNames = Array();
			config.folderTypes = Array();
		} 
	}
	// Windows cancel pressed -> remember old password and settings
	else {
		if (oldpwd != '') {
			var passwordManager = Components.classes["@mozilla.org/login-manager;1"]
				.getService(Components.interfaces.nsILoginManager);
			var url = 
				(prefs.getBoolPref('hostSsl') ? 'https://' : 'http://') + 
				prefs.getCharPref('host') + '/Microsoft-Server-ActiveSync';
			var nsLoginInfo = new Components.Constructor("@mozilla.org/login-manager/loginInfo;1",
				Components.interfaces.nsILoginInfo, "init");
			var loginInfo = new nsLoginInfo(
				url, null, 'Tine 2.0 Active Sync', prefs.getCharPref('user'), 
				oldpwd, '', ''
			);
			passwordManager.addLogin(loginInfo);
		}
		// old settings
		config = oldConfig;
	}
	config.write();
  }

  function localAbs() {
	while (document.getElementById('localContactsFolder').children.length > 0)
		document.getElementById('localContactsFolder').removeChild(document.getElementById('localContactsFolder').firstChild);
	let abManager = Components.classes["@mozilla.org/abmanager;1"] 
		.getService(Components.interfaces.nsIAbManager);
	let allAddressBooks = abManager.directories;
	while (allAddressBooks.hasMoreElements()) {  
		let addressBook = allAddressBooks.getNext();
		if (addressBook instanceof Components.interfaces.nsIAbDirectory && 
			!addressBook.isRemote && !addressBook.isMailList && addressBook.fileName != 'history.mab') {
			var ab = document.createElement('listitem');
			ab.setAttribute('label', addressBook.dirName);
			ab.setAttribute('value', addressBook.URI); 
			document.getElementById('localContactsFolder').appendChild(ab);
			if (prefs.getCharPref('contactsLocalFolder') == addressBook.URI)
				document.getElementById('localContactsFolder').selectedItem = ab;
		}
	}
	if(document.getElementById('localContactsFolder').selectedIndex < 0)
		document.getElementById('localContactsFolder').selectedIndex = 0;
  }

  function remoteFolders() { 
	while (document.getElementById('remoteContactsFolder').children.length > 0)
		document.getElementById('remoteContactsFolder').removeChild(document.getElementById('remoteContactsFolder').firstChild);
	config.url = (document.getElementById('hostSsl').checked ? 'https://' : 'http://') + 
		document.getElementById('host').value + '/Microsoft-Server-ActiveSync';
	config.deviceType = (document.getElementById('iPhone').checked? 'iPhone' : 'ThunderTine');
	config.user = document.getElementById('user').value;
	config.pwd = document.getElementById('password').value;
	config.deviceId = deviceId; 
	config.folderSyncKey = 0;
	if (config.minimumConfig())
		sync.execute(Array('start', 'folderSync_Options', 'finish'));
  } 

  function remoteFoldersFinish() {
	var remoteIds = folder.listFolderIds('Contacts'); 
	var remoteNames = folder.listFolderNames('Contacts'); 
	for (var i = 0; i < remoteNames.length; i++) {
		var ab = document.createElement('listitem');
		ab.setAttribute('label', remoteNames[i]);
		ab.setAttribute('value', remoteIds[i]); 
		document.getElementById('remoteContactsFolder').appendChild(ab);
		if (prefs.getCharPref('contactsRemoteFolder') == remoteIds[i])
			document.getElementById('remoteContactsFolder').selectedItem = ab;
	}
	if(document.getElementById('remoteContactsFolder').selectedIndex < 0)
		document.getElementById('remoteContactsFolder').selectedIndex = 0;
  }

  function newAb() {
	window.openDialog("chrome://messenger/content/addressbook/abAddressBookNameDialog.xul", "", "chrome,modal=yes,resizable=no,centerscreen", null);
	localAbs();
  }

  function reInit() {
	if (helper.ask(ttine.strings.getString('reinitializeFolder')))
		ab.doClearExtraFields(document.getElementById('localContactsFolder').value);
  }


