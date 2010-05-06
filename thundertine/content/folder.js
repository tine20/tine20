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

var folder = {

  /*
   *
   * update FolderHierarchy
   *
   */

  update: function() {
	// ask folders
	var folderRequest = '<?xml version="1.0" encoding="UTF-8"?>'+"\n"+
		'<FolderHierarchy_FolderSync><FolderHierarchy_SyncKey>' + 
		config.folderSyncKey + 
		'</FolderHierarchy_SyncKey></FolderHierarchy_FolderSync>';
	wbxml.httpRequest(folderRequest, 'FolderSync'); 
  }, 

  updateFinish: function(req) { 
	var response = wbxml.doXml(req.responseText); 
	if (response == false)
		return false;
	if (config.folderSyncKey == 0) {
		config.folderIds = Array();
		config.folderNames = Array();
		config.folderTypes = Array(); 
	} 
	for (var i = 0; i < response.firstChild.children.length; i++) {
		// status
		if (response.firstChild.children[i].nodeName == 'Status' && response.firstChild.children[i].firstChild.nodeValue != '1') {
			helper.prompt(errortxt.folder['code'+response.firstChild.children[i].firstChild.nodeValue]);
			return false;
		}
		else if (response.firstChild.children[i].nodeName == 'FolderHierarchy_SyncKey')
			config.folderSyncKey = response.firstChild.children[i].firstChild.nodeValue;
		else if (response.firstChild.children[i].nodeName == 'FolderHierarchy_Changes' && response.firstChild.children[i].children.length > 0) { 
			for (var c=0; c<response.firstChild.children[i].children.length; c++) {
				var node = response.firstChild.children[i].children[c];
				var tag = node.nodeName; 
				if (tag == 'FolderHierarchy_Add') { 
					for (var f=0; f<node.children.length; f++) { 
						var subtag_name = node.children[f].nodeName;
						var subtag_value = node.children[f].firstChild.nodeValue;
						if (subtag_name == 'FolderHierarchy_ServerId')
							config.folderIds.push(subtag_value);
						else if (subtag_name == 'FolderHierarchy_DisplayName')
							config.folderNames.push(subtag_value);
						else if (subtag_name == 'FolderHierarchy_Type')
							config.folderTypes.push(subtag_value); 
					}
				}
				else if (tag == 'FolderHierarchy_Update') {
					for (var f=0; f<node.children.length; f++) {
						var subtag_name = node.children[f].nodeName;
						var subtag_value = node.children[f].firstChild.nodeValue;
						if (subtag_name == 'FolderHierarchy_ServerId')
							var folder_id = subtag_value;
						else if (subtag_name == 'FolderHierarchy_DisplayName')
							var folder_name = subtag_value;
					}
					config.folderNames[config.folderIds.indexOf(folder_id)] = folder_name;
					
				}
				else if (tag == 'FolderHierarchy_Delete') {
					for (var f=0; f<node.children.length; f++) {
						var subtag_name = node.children[f].nodeName;
						var subtag_value = node.children[f].firstChild.nodeValue;
						if (subtag_name == 'FolderHierarchy_ServerId') {
							config.folderNames.splice(config.folderIds.indexOf(subtag_value), 1);
							config.folderTypes.splice(config.folderIds.indexOf(subtag_value), 1);
							config.folderIds.splice(config.folderIds.indexOf(subtag_value), 1);
						}
					}
				}
			}
		} 
	}
	return true;
  },

  /*
   * before sync make sure folder still exists
   */
  stillExists: function() {
	if (config.folderIds.indexOf(config.contactsRemoteFolder) >= 0)
		return true;
	else
		return false;
  }, 

  /*
   * preference GUI needs this
   */
  listFolderIds: function(type) {
	var resArr = Array();
	for (i in config.folderIds) {
		if(type=='Contacts' && (config.folderTypes[i]==9 || config.folderTypes[i]==14))
			resArr.push(config.folderIds[i]);
	}
	if (resArr.length > 0)
		return resArr;		
	else
		return false;		
  },

  listFolderNames: function(type) {
	var resArr = Array();
	for (i in config.folderIds) {
		if(type=='Contacts' && (config.folderTypes[i]==9 || config.folderTypes[i]==14))
			resArr.push(config.folderNames[i]);
	}
	if (resArr.length > 0)
		return resArr;		
	else
		return false;
  }

}
