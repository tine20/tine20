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
 * @author      Matthias Greiling <typo3@metaways.de>
 * @comment     this plugin is designed for TINE20 http://www.tine20.org
 * @version     $Id$
 */
 
require_once( 'class.factory.php' );

if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) 
{
	define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
} 
elseif (@is_dir(PATH_site.'tslib/')) 
{
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
   
	/**
     *Get UID of flexform saved data for prefilled form
     */
	public function __construct()
	{
		$preUID = str_replace('%5D%5B', '', explode('tt_content',$GLOBALS[_SERVER][QUERY_STRING]));
		$preUID2 = explode(',', $preUID[1]);
		$this->UID = trim(str_replace('][', '', $preUID2[0])) * 1 ;
	}
	
	/**
     *Get flexform saved data for prefilled form
     *
     * @param int $_uid
     * @return array
     */
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

   
	/**
     *Get flexform saved data for prefilled form
     *
     * @param array (origin ext_tables)
     * @return array
     */
	 function getNames($config)
	 {
	 if (!empty($this->UID)) {
		$this->flexform = $this->getXML($this->UID);
		
        /**
         * register autoloading for tine20 client
         */
        require_once(PATH_site . 'typo3conf/ext/user_tine2typo/pi1/tine20_client/loader.php');
		
		try
		{
			// open connection
			$connection = new Tinebase_Connection(
			    $this->pi_getFFvalue($this->flexform, 'tinehost'),
			    $this->pi_getFFvalue($this->flexform, 'tinehostlogin'), 
                $this->pi_getFFvalue($this->flexform, 'tinehostpassword')
			);
			//$connection->debugEnabled = true;
			// login to tine2.0
			$connection->login();
		}
		catch (Exception $e) 
		{
			$config['items'][] = array('Can not log in - no access throught this data');
			$config['items'][] = array($this->pi_getFFvalue($this->flexform, 'tinehostlogin') );
			$config['items'][] = array($this->pi_getFFvalue($this->flexform, 'tinehostpassword'));
			$config['items'][] = array($e->getMessage());
			//exit;
		}
		try 
		{
			// get all contacts of the user
			$addressbook = new Addressbook_Service($connection);
			$contacts = $addressbook->getAllContacts();
		}
		catch (Exception $e) 
		{
		
		}
		
		try
		{
			$connection->logout();
		}
		catch (Exception $e) 
		{
		
		}
		
		// bring it to typo3- interna formatting
		foreach ($contacts as $contact)
		{
			$config['items'][] = array('[' . $contact->id . ']' . $contact->n_fileas, '[' . $contact->id . ']' . $contact->n_fileas);
		}
	}
	
	// unset first element 
	unset($config['items'][0]);
	//print_r($config['items');
	// login succeded, but no data available'
	if (empty($config['items']))
	{
		$config['items'][0] = array( 'login succeded, but no data available', 'login succeded, but no data available');
	}
	if (empty($this->UID)){
		$config['items'][0] = array( 'system unsufficent', 'system unsufficent');
	}
	return $config;
	}
	
}

?>