<?php
/***************************************************************
*  Copyright notice
*
* @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'user_kontakt2tine' for the 'user_kontakt2tine' extension.
 *
 * @author  Matthias Greiling <typo3@metaways.de>
 * @comment this plugin is designed for TINE20 http://www.tine20.org
 * @version     $Id$
 */
 
if (!defined ('TYPO3_MODE'))   die ('Access denied.');

require_once( PATH_site . 'typo3conf/ext/user_kontakt2tine/config_inc.php' );

require_once( PATH_site . 'typo3conf/ext/user_kontakt2tine/classes/class.tx_DynamicFlexFormFieldsK2T.php' );

if (TYPO3_MODE=="BE")  $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["user_kontakt2tine_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.user_kontakt2tine_pi1_wizicon.php';

# Vor jeder �nderung eins $TCA Bereiches im Front End m�ssen wir es sicherheitshalber laden.
t3lib_div::loadTCA('tt_content');

# Wir blenden die Standard Felder layout,select_key,pages,recursive  von Plugins aus
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY]='header,layout,select_key,pages,recursive';

# Daf�r blenden wir das tt_content Feld pi_flexform ein
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY]='pi_flexform';


# Wir definieren die Datei, die unser Flexform Schema enth�lt
t3lib_extMgm::addPiFlexFormValue($_EXTKEY, '
<T3DataStructure>
  <ROOT>
    <type>array</type>
      <el>  

		<title>
		  <TCEforms>
			<label>Titel</label>
			  <config>
				<type>input</type>
				<size>50</size>
			  </config>
		  </TCEforms>
        </title>	
	  '.user_kontakt2tine_linkliste().'
	  	<text>
		  <TCEforms>
			<label>Text</label>
				<config>
					<type>text</type>
					<cols>50</cols>
					<rows>50</rows>
				</config>	
		  </TCEforms>
        </text>	
		 <optionemail>
		  <TCEforms>
			<label>E-Mail Benachrichtigung</label>
			  <config>
				<type>check</type>
				<default>0</default>
			  </config>
		  </TCEforms>
        </optionemail>		
		<email>
		  <TCEforms>
			<label>E-Mail</label>
			  <config>
				<type>input</type>
				<size>50</size>
			  </config>
		  </TCEforms>
        </email>
		<emailbetreff>
		  <TCEforms>
			<label>E-Mail-Betreff</label>
			  <config>
				<type>input</type>
				<size>50</size>
			  </config>
		  </TCEforms>
        </emailbetreff>
		
		<optioncheck>
		  <TCEforms>
			<label>Bild mit Zahlen-Code</label>
			  <config>
				<type>check</type>
				<default>0</default>
			  </config>
		  </TCEforms>
        </optioncheck>	
		
	  	
		<tinehost>
		  <TCEforms>
			<label>URL Tine2.0 installed</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>http://demo.tine20.org</default>
			  </config>
		  </TCEforms>
        </tinehost>	
		
		<tinehostlogin>
		  <TCEforms>
			<label>Login to Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>tine20demo</default>
			  </config>
		  </TCEforms>
        </tinehostlogin>		
		
		<tinehostpassword>
		  <TCEforms>
			<label>Password to Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>demo</default>
			  </config>
		  </TCEforms>
        </tinehostpassword>	
		<addressbook>
			<TCEforms>
				<label>Addressbook</label>
					<config>
						<type>select</type>
						<size>5</size>
						<maxitems>99</maxitems>
						<items>
							<n0>
								<n0></n0>
								<n1></n1>
							</n0>
						</items>
						<itemsProcFunc><![CDATA[tx_DynamicFlexFormFieldsK2T->getContainers]]></itemsProcFunc>
					</config>
			</TCEforms>
		</addressbook>	
		<danke>
			<TCEforms>
				<label>Danke-Seite</label>
				<config>
					<type>group</type>
					<internal_type>db</internal_type>
					<allowed>pages</allowed>
					<size>1</size>
					<maxitems>1</maxitems>
					<minitems>0</minitems>
					<show_thumbs>0</show_thumbs>
				</config>
			</TCEforms>
		</danke>

		
      </el>
  </ROOT>        
</T3DataStructure>');

# Das Plugin im Backend aktivieren
t3lib_extMgm::addPlugin(Array('LLL:EXT:user_kontakt2tine/locallang_db.php:tt_content.list_type', $_EXTKEY),'list_type');
?>
