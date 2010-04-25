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
require_once( 'class.factoryK2T.php' );

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

/**
 * register autoloading for tine20 client
 */
require_once(PATH_site . 'typo3conf/ext/user_kontakt2tine/pi1/tine20_client/loader.php');

class tx_DynamicFlexFormFieldsK2T extends tslib_pibase 
{
	var $TineClientLoader;
	var $UID;
	var $flexform;
	var $prefixId = 'tx_DynamicFlexFormFieldsK2T';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_DynamicFlexFormFieldsK2T.php';	// Path to this script relative to the extension dir.
	var $extKey = 'user_kontakt2tine';	// The extension key.
   
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
	 function getContainers($config)
	{
	if (!empty($this->UID)) {
		$this->flexform = $this->getXML($this->UID);
		
		try
		{
			// open connection
			$client = new Tinebase_Connection(
			    $this->pi_getFFvalue($this->flexform, 'tinehost'),
			    $this->pi_getFFvalue($this->flexform, 'tinehostlogin'), 
                $this->pi_getFFvalue($this->flexform, 'tinehostpassword')
		    );
			// login to tine2.0
			$client->login();
		}
		catch (Exception $e) 
		{
			$config['items'][] = array('+' . $this->UID . 'Can not log in - no access throught this data');
			$config['items'][] = array($this->pi_getFFvalue($this->flexform, 'tinehostlogin') );
			$config['items'][] = array($this->pi_getFFvalue($this->flexform, 'tinehostpassword'));
			$config['items'][] = array($e->getMessage());
			//exit;
		}
		try 
		{
			// get all contacts of the user
			$addressbook = new Addressbook_Service($client);
		}
		catch (Exception $e) 
		{
		//var_dump($e);
		}
		
		
		try { 
		    //TODO: add this again when tine20_client is ready
			//$container = $client->getContainer();
		
		} catch(Exception $e) {
		//	echo "can not get container" . $e;
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
		
		// bring it to typo3- interna formatting
		// rubish, fix charset and data structure!
		if (is_array($container)) 
		{
			foreach ($container as $key => $val)
			{
				$config['items'][$val['id']] = array('[' . $val['id'] . ']' . utf8_decode($val['name']),  '[' . $val['id'] . ']' . utf8_decode($val['name']) );
			}
		}
	}
	
	// unset first element 
	unset($config['items'][0]);
	//print_r($config['items');
	// login succeded, but no data available'
	if (empty($config['items']))
	{
		$config['items'][0] = array( 'login succeded, but no data available' , 'login succeded, but no data available');
	}
	if (empty($this->UID)){
		$config['items'][0] = array( 'system unsufficent', 'system unsufficent');
	}
	return $config;
	}
	
}

?>