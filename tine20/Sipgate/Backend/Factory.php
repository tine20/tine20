<?php
/**
 * Tine 2.0
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Factory.php 2 2011-04-26 17:27:39Z alex $
 *
 */

/**
 * phone backend factory class
 *
 * @package     Sipgate
 */
class Sipgate_Backend_Factory
{
	/**
	 * object instance
	 *
	 * @var Addressbook_Backend_Factory
	 */
	private static $_instance = NULL;


	/**
	 * factory function to return a selected phone backend class
	 *
	 * @param   string $_type
	 * @return  Sipgate_Backend_Interface
	 * @throws  Sipgate_Exception_InvalidArgument
	 * @throws  Sipgate_Exception_NotFound
	 */
	static public function factory()
	{

		if(isset(Tinebase_Core::getConfig()->sipgate)) {
			$sipgateConfig = Tinebase_Core::getConfig()->sipgate;
			$username   = $sipgateConfig->api_username;
			$password   = $sipgateConfig->api_password;
			$url 		= $sipgateConfig->api_url;
		} else {
			throw new Sipgate_Exception_Backend('No settings found for sipgate backend in config file!');
		}

		$instance = Sipgate_Backend_Api::getInstance($username, $password, $url);

		return $instance;
	}
}