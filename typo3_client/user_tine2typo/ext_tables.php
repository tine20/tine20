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
 * Plugin 'user_tine2typo' for the 'user_tine2typo' extension.
 *
 * @author  Matthias Greiling <typo3@metaways.de>
 * @comment this plugin is designed for TINE20 http://www.tine20.org
 * @version     $Id$
 */
 
if (!defined ('TYPO3_MODE')) die ('Access denied.');

require_once( PATH_site . 'typo3conf/ext/user_tine2typo/classes/class.tx_DynamicFlexFormFields.php' );

if (TYPO3_MODE=="BE")  $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["user_tine2typo_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.user_tine2typo_pi1_wizicon.php';

# Vor jeder �nderung eins $TCA Bereiches im Front End m�ssen wir es sicherheitshalber laden.
t3lib_div::loadTCA('tt_content');

# Wir blenden die Standard Felder layout,select_key,pages,recursive  von Plugins aus
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY]='header,layout,select_key,pages,recursive';

# Daf�r blenden wir das tt_content Feld pi_flexform ein
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY]='pi_flexform';


# Wir definieren die Datei, die unser Flexform Schema enth�lt
t3lib_extMgm::addPiFlexFormValue($_EXTKEY, '<T3DataStructure>
  <ROOT>
    <type>array</type>
      <el>  

		<title>
		  <TCEforms>
			<label>Titel</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>Tine 2.0 Addressbook</default>
			  </config>
		  </TCEforms>
        </title>	
	 
	  	<text>
		  <TCEforms>
			<label>Text</label>
				<config>
					<type>text</type>
					<cols>50</cols>
					<rows>5</rows>
					<default>Proudly present fancy addressbook entires out of tine 2.0</default>
				</config>	
		  </TCEforms>
        </text>	
		<selection>
			<TCEforms>
              <label>LLL:EXT:user_tine2typo/locallang_db.xml:what_to_display</label>
                <config>
                <type>select</type>
                <items type="array">
                  <numIndex index="0" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:n_family</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:n_family</numIndex>
                  </numIndex>
                  <numIndex index="1" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:n_given</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:n_given</numIndex>
                  </numIndex>
                  <numIndex index="2" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:org_name</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:org_name</numIndex>
                  </numIndex>
                  <numIndex index="3" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_locality</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_locality</numIndex>
                  </numIndex> 
                  <numIndex index="4" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_street</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_street</numIndex>
                  </numIndex>  
                  <numIndex index="5" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_postalcode</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_postalcode</numIndex>
                  </numIndex>    
                  <numIndex index="6" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:tel_work</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:tel_work</numIndex>
                  </numIndex>     
                  <numIndex index="7" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:email</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:email</numIndex>
                  </numIndex>      
                  <numIndex index="8" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:note</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:note</numIndex>
                  </numIndex>      
                  <numIndex index="9" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_countryname</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_countryname</numIndex>
                  </numIndex>             
				  
                  <numIndex index="10" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:jpegphoto</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:jpegphoto</numIndex>
                  </numIndex>             
                </items>          
                <maxitems>100</maxitems>
                <size>6</size>
                <multiple>1</multiple>
                <selectedListStyle>width:150px</selectedListStyle>
                <itemListStyle>width:150px</itemListStyle>
                </config>
		  </TCEforms>
		 </selection>
		<category>
			<TCEforms>
				<label>Bereich</label>
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
						<itemsProcFunc><![CDATA[tx_DynamicFlexFormFields->getNames]]></itemsProcFunc>
					</config>
			</TCEforms>
		</category>
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
		
      </el>
  </ROOT>        
</T3DataStructure>');

# Das Plugin im Backend aktivieren
t3lib_extMgm::addPlugin(Array('LLL:EXT:user_tine2typo/locallang_db.php:tt_content.list_type', $_EXTKEY),'list_type');
?>
