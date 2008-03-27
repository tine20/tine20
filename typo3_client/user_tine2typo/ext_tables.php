<?php
if (!defined ('TYPO3_MODE'))   die ('Access denied.');

if (TYPO3_MODE=="BE")  $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["user_tine2typo_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.user_tine2typo_pi1_wizicon.php';

# Vor jeder Änderung eins $TCA Bereiches im Front End müssen wir es sicherheitshalber laden.
t3lib_div::loadTCA('tt_content');

# Wir blenden die Standard Felder layout,select_key,pages,recursive  von Plugins aus
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY]='header,layout,select_key,pages,recursive';

# Dafür blenden wir das tt_content Feld pi_flexform ein
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY]='pi_flexform';


function user_tine2typo_contacts()
{// to be continued
/*	$this->pi_setPiVarDefaults();
		pi_loadLL();
		$pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		
		 // parse XML data into php array
		pi_initPIflexForm();
				$path = array(
					get_include_path(),
					PATH_site . 'typo3conf/ext/user_tine2typo/pi1/',
				);
	
				set_include_path(implode(PATH_SEPARATOR, $path));

				// zend auto loader will load any classes - can not use it
											
				require_once( 'TineClient/Connection.php');
				require_once( 'TineClient/Service/Abstract.php');
				require_once( 'Zend/Http/Client.php');
				require_once( 'Addressbook/Model/Contact.php');
				require_once( 'Addressbook/Service.php');
				
				try
				{
					// open connection
					$client = new TineClient_Connection($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehost'));
					//$client->setDebugEnabled(true);
					TineClient_Service_Abstract::setDefaultConnection($client);

					// login to tine2.0
					$client->login(
								$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehostlogin'), 
								$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehostpassword')
								);
				}
				catch (Exception $e) 
				{
					echo "can not login";
					//var_dump($e);
					//exit;
				}
				try 
				{
					// write addressbook entry
				 
					//$contact = new Addressbook_Model_Contact($contactData);
					$addressbook = new Addressbook_Service();
					$Contact = $addressbook->getContact(38);
	
				}
				catch (Exception $e) 
				{
					var_dump($e);
					exit;
				}
				
				try
				{
					$client->logout();
				}
				catch (Exception $e) 
				{
					var_dump($e);
				
				}
				
				
				$ausgabe = '<tinehostcontact>
		  <TCEforms>
			<label>Password to Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>' . $Contact['n_family'] . '</default>
			  </config>
		  </TCEforms>
        </tinehostcontact>	';
				return $ausgabe;

*/
}








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
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:company</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:company</numIndex>
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
                  <numIndex index="8" type="array">
                    <numIndex index="0">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_countryname</numIndex>
                    <numIndex index="1">LLL:EXT:user_tine2typo/locallang_db.xml:adr_one_countryname</numIndex>
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
		 <tinehost>
		  <TCEforms>
			<label>URL Tine2.0 installed</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>http://localhost/tine/index.php</default>
			  </config>
		  </TCEforms>
        </tinehost>	
		 ' . user_tine2typo_contacts() . '
		<tinehostlogin>
		  <TCEforms>
			<label>Login to Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>tine20admin</default>
			</config>
		  </TCEforms>
        </tinehostlogin>		
		
		<tinehostpassword>
		  <TCEforms>
			<label>Password to Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>lars</default>
			  </config>
		  </TCEforms>
        </tinehostpassword>	
		<containerType>
		  <TCEforms>
			<label>Container Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>1</default>
			  </config>			  
		  </TCEforms>
        </containerType>			
		<tinehostcontainer>
		  <TCEforms>
			<label>Container Tine2.0 Host</label>
			  <config>
				<type>input</type>
				<size>50</size>
				<default>1</default>
			  </config>			  
		  </TCEforms>
        </tinehostcontainer>	
      </el>
  </ROOT>        
</T3DataStructure>');

# Das Plugin im Backend aktivieren
t3lib_extMgm::addPlugin(Array('LLL:EXT:user_tine2typo/locallang_db.php:tt_content.list_type', $_EXTKEY),'list_type');
?>
