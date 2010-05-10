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

var ab = {
  
  // mapping table for ActiveSync (nodeName) -> Thunderbird (nodeValue)
  map: '<?xml version="1.0" encoding="utf-8"?>\
<card>\
	<Contacts_FileAs>DisplayName</Contacts_FileAs>\
	<Contacts_FirstName>FirstName</Contacts_FirstName>\
	<Contacts_LastName>LastName</Contacts_LastName>\
	<Contacts_MiddleName>NickName</Contacts_MiddleName>\
	<Contacts_Email1Address>PrimaryEmail</Contacts_Email1Address>\
	<Contacts_Email2Address>SecondEmail</Contacts_Email2Address>\
	<Contacts_HomeStreet>HomeAddress</Contacts_HomeStreet>\
	<Contacts_HomeCity>HomeCity</Contacts_HomeCity>\
	<Contacts_HomeState>HomeState</Contacts_HomeState>\
	<Contacts_HomePostalCode>HomeZipCode</Contacts_HomePostalCode>\
	<Contacts_HomeCountry>HomeCountry</Contacts_HomeCountry>\
	<Contacts_BusinessStreet>WorkAddress</Contacts_BusinessStreet>\
	<Contacts_BusinessCity>WorkCity</Contacts_BusinessCity>\
	<Contacts_BusinessState>WorkState</Contacts_BusinessState>\
	<Contacts_BusinessPostalCode>WorkZipCode</Contacts_BusinessPostalCode>\
	<Contacts_BusinessCountry>WorkCountry</Contacts_BusinessCountry>\
	<Contacts_CompanyName>Company</Contacts_CompanyName>\
	<Contacts_Department>Department</Contacts_Department>\
	<Contacts_JobTitle>JobTitle</Contacts_JobTitle>\
	<Contacts_OfficeLocation>Custom1</Contacts_OfficeLocation>\
	<Contacts_HomePhoneNumber>HomePhone</Contacts_HomePhoneNumber>\
	<Contacts_BusinessPhoneNumber>WorkPhone</Contacts_BusinessPhoneNumber>\
	<Contacts_BusinessFaxNumber>FaxNumber</Contacts_BusinessFaxNumber>\
	<Contacts_HomeFaxNumber>PagerNumber</Contacts_HomeFaxNumber>\
	<Contacts_MobilePhoneNumber>CellularNumber</Contacts_MobilePhoneNumber>\
	<Contacts_Birthday>%Birthday</Contacts_Birthday>\
	<Contacts_Webpage>WebPage1</Contacts_Webpage>\
	<Contacts_Suffix>Custom2</Contacts_Suffix>\
	<Contacts_Picture>%Picture</Contacts_Picture>\
</card>',

  mapDom: function() { 
	var parser = Components.classes["@mozilla.org/xmlextras/domparser;1"]
		.createInstance(Components.interfaces.nsIDOMParser); 
	return parser.parseFromString(this.map, "text/xml"); 
  },

  getSpecialAbValue: function(card, field) {
	// create special fields (those marked in map with a "%") for ActiveSync
	var ret = '';
	if (field.substr(0,1) == '%')
		field = field.substr(1, field.length-1);
	// Anniversary isn't supported by Tine 2.0
	// Birthday
	if (field=='Birthday') {
		if (card.getProperty("BirthYear", "0") >0 && card.getProperty("BirthMonth", "0") >0 && card.getProperty("BirthDay", "0") >0) {
			var aHours = 0;
			// Tine 2.0 manipulates dates from iPhones (subtract 12 hours)
			if (config.deviceType == 'iPhone')
				aHours = 12;
			var dLoc = new Date(
				card.getProperty("BirthYear", "0000"),
				(card.getProperty("BirthMonth", "01") -1), // Month in js is from 0 to 11
				card.getProperty("BirthDay", "00"),
				aHours,00,00,000
			);
			var rYear = dLoc.getUTCFullYear();
			var rMonth = dLoc.getUTCMonth()+1;
				if (rMonth<10) rMonth = '0'+rMonth;
			var rDay = (dLoc.getUTCDate()>9) ? dLoc.getUTCDate() : '0'+dLoc.getUTCDate();
			var rHour = (dLoc.getUTCHours()>9) ? dLoc.getUTCHours() : '0'+dLoc.getUTCHours();
			var rMinute = (dLoc.getUTCMinutes()>9) ? dLoc.getUTCMinutes() : '0'+dLoc.getUTCMinutes();
			var ret = rYear+'-'+rMonth+'-'+rDay+'T'+rHour+':'+rMinute+':00.000Z';
		}
		else
			var ret = ''; 
	}
	// Picture
	else if (field=='Picture') {
		var photo = card.getProperty("PhotoName", ""); 
		if (card.getProperty("PhotoType", "") == 'file' && 
			(photo.substr(photo.length-4, 4) == '.jpg' || photo.substr(photo.length-5, 5) == '.jpeg') ) {
			// get folder for pictures
			var file = Components.classes["@mozilla.org/file/directory_service;1"]
						         .getService(Components.interfaces.nsIProperties)
						         .get("ProfD", Components.interfaces.nsIFile);
			file.append(config.picDir);
			file.append(photo); 
			if( file.exists() && !file.isDirectory() ) {
				var fstream = Components.classes["@mozilla.org/network/file-input-stream;1"]
					.createInstance(Components.interfaces.nsIFileInputStream); 
				fstream.init(file, 0x01, 0444, 0);
				var stream = Components.classes["@mozilla.org/binaryinputstream;1"]
					.createInstance(Components.interfaces.nsIBinaryInputStream);
				stream.setInputStream(fstream);
				var base64 = btoa(stream.readBytes(stream.available())); 
				// Specifikation limits size to 48KB
				if (base64.length < 49152 || config.contactsLimitPictureSize == false)
					ret = base64;
				else
					ret = '';
			}
		}
		else
			ret = null;
	}
	return ret;
  },

  setSpecialAbValue: function(card, tbField, asValue) {
	if (tbField=='Birthday' && asValue != '') {
		var asDate = new Date(asValue.substr(0,4), asValue.substr(5,2)-1, asValue.substr(8,2), asValue.substr(11,2), asValue.substr(14,2) );
		var locDate = new Date(asDate.getTime() + (asDate.getTimezoneOffset() * 60000 * -1));
		var aMonth = (locDate.getMonth()+1);
		if (aMonth<10) aMonth = '0' + aMonth;
		var aDay = locDate.getDate();
		if (aDay < 10) aDay = '0' + aDay;
		tbDate = locDate.getFullYear() + '-' + aMonth + '-' + aDay; 
		card.setProperty("BirthYear", tbDate.substr(0,4) );
		card.setProperty("BirthMonth", tbDate.substr(5,2) );
		card.setProperty("BirthDay", tbDate.substr(8,2) );
	}
	else if (tbField=='Picture') { 
		// delete image
		if (asValue == '') {
			var file = Components.classes["@mozilla.org/file/directory_service;1"]
						         .getService(Components.interfaces.nsIProperties)
						         .get("ProfD", Components.interfaces.nsIFile);
			file.append(config.picDir);
			file.append(card.getProperty("PhotoName", "")); 
			if( file.exists() && !file.isDirectory() )
				file.remove(false);
			card.setProperty("PhotoName", "" );
			card.setProperty("PhotoType", "" );
			card.setProperty("PhotoURI", "" );
			card.deleteProperty("PhotoName");
			card.deleteProperty("PhotoType");
			card.deleteProperty("PhotoURI");
		}
		// modify or new
		else {
			var photo = card.getProperty("PhotoName", ""); 
			if (photo == '') {
				photo = this.uniqueId() + '.jpg';
				card.setProperty("PhotoName", photo );
			}
			var foStream = Components.classes["@mozilla.org/network/file-output-stream;1"]
				.createInstance(Components.interfaces.nsIFileOutputStream);
			var file = Components.classes["@mozilla.org/file/directory_service;1"]
						         .getService(Components.interfaces.nsIProperties)
						         .get("ProfD", Components.interfaces.nsIFile);
			file.append(config.picDir);
			file.append(photo); 
			foStream.init(file, 0x02 | 0x08 | 0x20, 0600, 0);   // write, create, truncate
				var binary = atob(asValue);
				foStream.write(binary, binary.length);
			foStream.close();
			card.setProperty("PhotoType", "file" );
			var filePath = 'file:///' + file.path.replace(/\\/g, '\/').replace(/^\s*\/?/, '').replace(/\ /g, '%20');
			card.setProperty("PhotoURI", filePath );
		}
	}
  },

  commandsDom: function() { 

	let abManager = Components.classes["@mozilla.org/abmanager;1"]
		.getService(Components.interfaces.nsIAbManager);
	
	try {
		let addressBook = abManager.getDirectory(config.contactsLocalFolder);
		if (addressBook.fileName && !addressBook.isRemote && !addressBook.isMailList) { 
			var cardArr = new Array();
			let cards = addressBook.childCards;
			// go for new and changed cards
			while (cards.hasMoreElements()) { 
				let card = cards.getNext();
				if (card instanceof Components.interfaces.nsIAbCard) { 
					var doc = document.implementation.createDocument("", "", null);
					var tineId = card.getProperty("TineSyncId", "");
					// unsynced (or left out) cards
					if (tineId == "" || tineId.substr(0,7) == 'client-' ) {
						var clientId = 'client-'+this.uniqueId();
						var cardDom = this.asDom(card, clientId); 
					}
					else
						var cardDom = this.asDom(card); 
					if (cardDom != null) {
						// unsyncted cards need a preliminary id
						if (tineId == "" || tineId.substr(0,7) == 'client-' ) {
							card.setProperty("TineSyncId", clientId);
							addressBook.modifyCard(card);
						}
						cardArr.push( doc.appendChild(cardDom) );
					} 
				}
			}
			// add cards which doesn't exist anymore
			for (var i=0; i < config.managedCards.length; i++) {
				let card = addressBook.getCardFromProperty("TineSyncId", config.managedCards[i], false); 
				if(card == null) {
					var doc = document.implementation.createDocument("", "", null);
					var cardDom = this.asDelDom(config.managedCards[i]);
					cardArr.push( doc.appendChild(cardDom) );
				}
			}
			

			if (cardArr.length > 0)
				return cardArr;
			else
				return null;
		}
	}
	catch (err) {
		helper.prompt("Cannot access addressbook. Please edit ThunderTine options\n" + err);
	}
	return null;
  },

  supportedDom: function() {
	var doc = document.implementation.createDocument("", "", null);
	var data = doc.createElement('Supported');
	var mapDom = this.mapDom();
	for (var i=0; i<mapDom.firstChild.children.length; i++) {
		data.appendChild( doc.createElement(mapDom.firstChild.children[i].nodeName) );
	} 
	return data;
  },

  asDom: function(card, clientId) {
	var doc = document.implementation.createDocument("", "", null);
	// read card data
	var md5text = '';
	var mapDom = this.mapDom();
	var data = doc.createElement('ApplicationData');
	for (var i=0; i<mapDom.firstChild.children.length; i++) {
		var asField = mapDom.firstChild.children[i].nodeName;
		var tbField = mapDom.firstChild.children[i].firstChild.nodeValue;
		if(tbField.substr(0,1) != '%') 
			var tbValue = card.getProperty(tbField, "");
		else {
			var tbValue = this.getSpecialAbValue(card, tbField);
		}
		if (tbValue == null)
			data.appendChild( doc.createElement(asField) );
		else if (tbValue != '') {
			var field = doc.createElement(asField);
			field.appendChild( doc.createTextNode(tbValue) );
			data.appendChild( field );
			md5text = md5text + tbValue;
		}
	} 
	// calculate meta data and build command
	var md5 = this.md5hash(md5text);
	var tineId = card.getProperty("TineSyncId", "");
	if (tineId == "" || tineId.substr(0,7) == 'client-' ) {
		var command = doc.createElement('Add');
		command.appendChild( doc.createElement('ClientId') );
		command.lastChild.appendChild( doc.createTextNode(clientId) ); 
	}
	else if (card.getProperty("TineSyncMD5", "") != md5) {
		var command = doc.createElement('Change');
		command.appendChild( doc.createElement('ServerId') );
		command.lastChild.appendChild( doc.createTextNode( card.getProperty("TineSyncId", "") ) );
	}
	// build command container
	if (typeof command == 'undefined')
		return null;
	else {
		command.appendChild(data);
		return command;
	}
  },

  asDelDom: function(id) {
	var doc = document.implementation.createDocument("", "", null);
	var command = doc.createElement('Delete');
	command.appendChild( doc.createElement('ServerId') );
	command.lastChild.appendChild( doc.createTextNode(id) );
	return command;
  }, 

  md5hash: function(md5input) {
	var md5 = Components.classes["@mozilla.org/security/hash;1"]
			.createInstance(Components.interfaces.nsICryptoHash);
	var converter = Components.classes["@mozilla.org/intl/scriptableunicodeconverter"]
			.createInstance(Components.interfaces.nsIScriptableUnicodeConverter);
	converter.charset = "UTF-8";
	var converterResult = {};
	var md5data = converter.convertToByteArray(md5input, converterResult);
	md5.init(md5.MD5);
	md5.update(md5data, md5data.length);
	var md5temp = md5.finish(false);
	var md5output = '';
	for(var i=0; i<md5temp.length-1; i++)
		md5output = md5output + md5temp.charCodeAt(i).toString(16); 
	return md5output;
  }, 

  uniqueId: function() {
	// wait for two miliseconds to make sure id is unique
	var endTime = (new Date()).getTime()+1;
	while((new Date()).getTime()<endTime){}; 
	return this.md5hash(endTime);
  }, 

  listAbs: function() {
	var resArr = Array();
	let abManager = Components.classes["@mozilla.org/abmanager;1"]
		.getService(Components.interfaces.nsIAbManager);
	
	// find book
	let allAddressBooks = abManager.directories; 
	while (allAddressBooks.hasMoreElements()) { 
		let addressBook = allAddressBooks.getNext();
		// found right book -> read cards
		if (addressBook instanceof Components.interfaces.nsIAbDirectory && !addressBook.isRemote && addressBook.fileName != 'history.mab') 
			resArr.push(addressBook.URI);
	}

	return resArr;
  }, 

  responseCard: function(tineSyncId, fields, values) { 
	let abManager = Components.classes["@mozilla.org/abmanager;1"]
		.getService(Components.interfaces.nsIAbManager);
	try {
		let addressBook = abManager.getDirectory(config.contactsLocalFolder); 
		if (addressBook.fileName && !addressBook.isRemote && !addressBook.isMailList) { 
			let card = addressBook.getCardFromProperty("TineSyncId", tineSyncId, false); 
			if(card == null)
				throw "Unknown addressbook entry, with internal id "+tineSyncId; 
			// change requested fields
			for (var f = 0; f < fields.length; f++) { 
				var field = fields[f]; 
				var value = values[f];
				// if card is changed calculate new md5
				if (field == 'TineSyncMD5') {
					// read card data
					var md5text = '';
					var mapDom = this.mapDom();
					for (var i=0; i<mapDom.firstChild.children.length; i++) {
						var tbField = mapDom.firstChild.children[i].firstChild.nodeValue;
						if(tbField.substr(0,1) != '%') 
							var tbValue = card.getProperty(tbField, "");
						else 
							var tbValue = this.getSpecialAbValue(card, tbField);
						if (tbValue != '' && tbValue != null) 
							md5text = md5text + tbValue; 
					} 
					value = this.md5hash(md5text); 
				} 
				card.setProperty(field, value); 
			}
			// save changes
			addressBook.modifyCard(card); 
		}
	}
	catch (err) {
		helper.prompt("Couldn't update Addressbook entry. Please check your books.\n\n"+err);
	}
  }, 

  commandCard: function(command, id, appDataDom) { 
	let abManager = Components.classes["@mozilla.org/abmanager;1"]
		.getService(Components.interfaces.nsIAbManager);

	try {
		let addressBook = abManager.getDirectory(config.contactsLocalFolder); 
		if (addressBook.fileName && !addressBook.isRemote && !addressBook.isMailList) { 
			if(command == 'Add' || command == 'Change') {
				let card = null; 
				// If cards are resent (syncKey = 0) don't change existing (managed) cards
				if(command == 'Change' || config.managedCards.indexOf(id) >= 0 ) 
					card = addressBook.getCardFromProperty("TineSyncId", id, false); 
				else { // new card
					card = Components.classes["@mozilla.org/addressbook/cardproperty;1"]  
						.createInstance(Components.interfaces.nsIAbCard);  
					card.setProperty("TineSyncId", id);
				}
				if (card == null) 
					throw command+" of card failed.";
				// apply server data
				var md5text = '';
				var mapDom = this.mapDom(); 
				for (var i=0; i<appDataDom.children.length; i++) {
					var asField = appDataDom.children[i].nodeName;
					if (asField == 'Contacts_Picture')
						// stupid Mozilla 4kb bug -> Need extra function to retrieve nodeValue!!
						var asValue = this._largeDomValue(appDataDom.children[i]); 
					else
						var asValue = appDataDom.children[i].firstChild.nodeValue; 
					var tbFieldX = helper.doEvaluateXPath(mapDom, "//"+asField);
					if (tbFieldX.length > 0) {
						var tbField = tbFieldX[0].firstChild.nodeValue;
						if(tbField.substr(0,1) == '%')
							this.setSpecialAbValue(card, tbField.substr(1,tbField.length-1), asValue); 
						else
							card.setProperty(tbField, asValue);
						md5text = md5text + asValue; 
					}
					else {
						helper.prompt("The Server tries to change "+asField+", which isn't known to Thunderbird!");
						// ActiveSync field is unknown to Thunderbird. Save it hidden? Maybe later. Otherwise next sync will overwrite if empty.
					}
				}
				// give md5hash (otherwise it will be sent to the server again)
				card.setProperty('TineSyncMD5', this.md5hash(md5text));
				// save changes. If cards are resent (syncKey = 0) don't change existing (managed) cards
				if (command == 'Change' || config.managedCards.indexOf(id) >= 0 )
					addressBook.modifyCard(card); 
				else if (command == 'Add')
					addressBook.addCard(card);
			}
			else if(command == 'Delete') {
				let card = addressBook.getCardFromProperty("TineSyncId", id, false); 
				if (card!=null) {
					// remove picture (if it is in Tb cache only)
					this.setSpecialAbValue(card, "Picture", "");
					// remove card
					let cardsToDelete = Components.classes["@mozilla.org/array;1"]  
						.createInstance(Components.interfaces.nsIMutableArray);  
					cardsToDelete.appendElement(card, false);  
					addressBook.deleteCards(cardsToDelete);  
				}
			}
		}
	}
	catch (err) {
		helper.prompt("Server sent new cards but they couldn't be applied to the local Addressbook. \n\n"+err);
	}
  },

  // this function handles a mozilla bug. Every nodeValue is truncated to maximum of 4096 chars (bytes)!! Hate it.
  _largeDomValue: function(node) {
	if(node.firstChild.textContent && node.normalize) {
		node.normalize(node.firstChild);
		content=node.firstChild.textContent;
	}
	else if(node.firstChild.nodeValue) 
		content=node.firstChild.nodeValue;
	else 
		content=null;
	return content;
  },

  managedCards: function() {
	let abManager = Components.classes["@mozilla.org/abmanager;1"]
		.getService(Components.interfaces.nsIAbManager);

	let addressBook = abManager.getDirectory(config.contactsLocalFolder);
	let cards = addressBook.childCards;
	config.managedCards = Array();
	while (cards.hasMoreElements()) { 
		let card = cards.getNext();
		if (card instanceof Components.interfaces.nsIAbCard) {
			var tineId = card.getProperty("TineSyncId", ""); 
			if(tineId != '' && tineId.substr(0,7) != 'client-')
				config.managedCards.push(tineId);
		}
	} 
  },

  doClearExtraFields: function(uri) { 

	let abManager = Components.classes["@mozilla.org/abmanager;1"]
		.getService(Components.interfaces.nsIAbManager);
	
	let addressBook = abManager.getDirectory(uri); 
	let cards = addressBook.childCards;
	while (cards.hasMoreElements()) { 
		let card = cards.getNext();
		if (card instanceof Components.interfaces.nsIAbCard) {
			// do not manage this card anymore
			var id = card.getProperty("TineSyncMD5", null);
			if (id != null && config.managedCards.indexOf(id) >= 0)
				config.managedCards.splice(id, 1);
			// Anyhow deleting properties doesn't work. Null them instead.
			card.setProperty("TineSyncMD5", null);
			card.setProperty("TineSyncId", null); 
			addressBook.modifyCard(card); 
			card.deleteProperty("TineSyncMD5");
			card.deleteProperty("TineSyncId");
		}
	}
  }, 

  stillExists: function() { 

	let abManager = Components.classes["@mozilla.org/abmanager;1"]
		.getService(Components.interfaces.nsIAbManager);
	try {
		let addressBook = abManager.getDirectory(config.contactsLocalFolder);
		return true;
	}
	catch (err) {
		return false;
	}
  }

}
