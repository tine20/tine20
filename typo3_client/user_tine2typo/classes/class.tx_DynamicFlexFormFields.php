<?php

require_once( 'class.factory.php' );


if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) {
				define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
} elseif (@is_dir(PATH_site.'tslib/')) {
				define('PATH_tslib', PATH_site.'tslib/');
} else {

				// define path to tslib/ here:
				$configured_tslib_path = '';

				// example:
				// $configured_tslib_path = '/var/www/mysite/typo3/sysext/cms/tslib/';

				define('PATH_tslib', $configured_tslib_path);
}

if (PATH_tslib=='') {
				die('Cannot find tslib/. Please set path by defining $configured_tslib_path in '.basename(PATH_thisScript).'.');
}
	

require_once(PATH_tslib . 'class.tslib_pibase.php');


class tx_DynamicFlexFormFields extends tslib_pibase 
{
	var $UID;
	var $flexform;
	var $prefixId = 'tx_DynamicFlexFormFields';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_DynamicFlexFormFields.php';	// Path to this script relative to the extension dir.
	var $extKey = 'user_tine2typo';	// The extension key.

	
	public function __construct()
	{
		$preUID = explode('tt_content',$GLOBALS[_SERVER][QUERY_STRING]);
		$preUID2 = explode(',', $preUID[1]);
		$this->UID = trim(str_replace('][', '', $preUID2[0])) * 1 ;
	}
	
	public function getXML( $_uid )
	{
		$config = "-";
		try
		{
			$dbh = Factory::createDB( 'PDO' );
			
			$sql = '
				SELECT pi_flexform
				FROM tt_content
				WHERE uid > ' . $_uid ;
			
			$stmt = $dbh->prepare( $sql );
			$stmt->execute();
			$row = $stmt->fetch( PDO::FETCH_ASSOC ) ;
			$config = $row['pi_flexform'];
			
			$dbh = null;
		}
		catch( PDOException $pe ) {
			print_r( "PDO-Message" .  $pe->getMessage() . ' - ' . $pe->errorInfo );
		}
		catch( Exception $e ) {
			// seems not to be able to PDO
			include( PATH_site . 'typo3conf/localconf.php' );
			
			$mysql_conn = mysql_connect($typo_db_host, $typo_db_username, $typo_db_password);
			@mysql_select_db($typo_db);
			$sql = "SELECT `pi_flexform` FROM `tt_content` WHERE `uid` = '" . $_uid . "';";
			$result = @mysql_query($sql) or die ($sql);
			
			$fetch = @mysql_fetch_array($result);
			$config = $fetch['pi_flexform'];
			
			@mysql_close($mysql_conn);
			}
		
		 $xml = t3lib_div::xml2array($config);
		
		
		return $xml;
	}





	function getNames($config)
	{
	//print_r($this->UID);
	if (!empty($this->UID)) {
		
	
		$this->flexform = $this->getXML($this->UID);
	
		//print_r( $this->pi_getFFvalue($this->flexform, 'tinehostlogin'));
		

		// zend auto loader will load any classes - can not use it
									
		require_once( PATH_site . 'typo3conf/ext/user_tine2typo/pi1/TineClient/Connection.php');
		require_once( PATH_site . 'typo3conf/ext/user_tine2typo/pi1/TineClient/Service/Abstract.php');
		require_once( PATH_site . 'typo3conf/ext/user_tine2typo/pi1/Zend/Http/Client.php');
		require_once( PATH_site . 'typo3conf/ext/user_tine2typo/pi1/Addressbook/Model/Contact.php');
		require_once( PATH_site . 'typo3conf/ext/user_tine2typo/pi1/Addressbook/Service.php');
		
		try
		{
			// open connection
			$client = new TineClient_Connection($this->pi_getFFvalue($this->flexform, 'tinehost'));
			//$client->setDebugEnabled(true);
			TineClient_Service_Abstract::setDefaultConnection($client);

			// login to tine2.0
			$client->login(
						$this->pi_getFFvalue($this->flexform, 'tinehostlogin'), 
						$this->pi_getFFvalue($this->flexform, 'tinehostpassword')
						);
		}
		catch (Exception $e) 
		{
			$config['items'][] = array('Mit diesen Daten kann ich mich nicht einloggen');
			$config['items'][] = array($this->pi_getFFvalue($this->flexform, 'tinehostlogin') );
			$config['items'][] = array($this->pi_getFFvalue($this->flexform, 'tinehostpassword'));
			$config['items'][] = array($e->getMessage());
			//var_dump($e);
			//exit;
		}
		try 
		{
			// write addressbook entry
		 
			//$contact = new Addressbook_Model_Contact($contactData);
			$addressbook = new Addressbook_Service();
			
			$Contact = $addressbook->getAllContacts();
			//print_r($Contact);
		}
		catch (Exception $e) 
		{
		//	var_dump($e);
		
		}
		
		try
		{
			$client->logout();
		}
		catch (Exception $e) 
		{
		//	var_dump($e);
		
		}
//		print_r($Contact);
		foreach ($Contact as $key => $val)
		{
			$config['items'][$key] = array('[' . $key . ']' . utf8_decode($val),  '[' . $key . ']' . utf8_decode($val) );
		}
		
	}

	unset($config['items'][0]);
	return $config;
	}
	
}

?>