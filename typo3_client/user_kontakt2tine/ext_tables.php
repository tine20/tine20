<?php
if (!defined ('TYPO3_MODE'))   die ('Access denied.');

require_once( PATH_site . 'typo3conf/ext/user_kontakt2tine/config_inc.php' );

if (TYPO3_MODE=="BE")  $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["user_kontakt2tine_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.user_kontakt2tine_pi1_wizicon.php';

# Vor jeder Änderung eins $TCA Bereiches im Front End müssen wir es sicherheitshalber laden.
t3lib_div::loadTCA('tt_content');

# Wir blenden die Standard Felder layout,select_key,pages,recursive  von Plugins aus
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY]='header,layout,select_key,pages,recursive';

# Dafür blenden wir das tt_content Feld pi_flexform ein
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY]='pi_flexform';


# Wir definieren die Datei, die unser Flexform Schema enthält
t3lib_extMgm::addPiFlexFormValue($_EXTKEY, '<?xml version="1.0" encoding="iso-8859-1" standalone="yes" ?>
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
			  </config>
		  </TCEforms>
        </tinehost>	
		
		<tinehostlogin>
		  <TCEforms>
			<label>Login to Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
			  </config>
		  </TCEforms>
        </tinehostlogin>		
		
		<tinehostpassword>
		  <TCEforms>
			<label>Password to Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
			  </config>
		  </TCEforms>
        </tinehostpassword>	
		
		<tinehostcontainer>
		  <TCEforms>
			<label>Container Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
			  </config>
		  </TCEforms>
        </tinehostcontainer>		
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
