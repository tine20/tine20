<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Json Container class
 * 
 * @package     Tinebase
 */
class Tinebase_Json_UserRegistration
{
	
	/**
	 * suggests a username
	 *
	 * @param array $regData
	 * @return string
	 */
	public function suggestUsername ( $regData ) 
	{
		
	}

	/**
	 * checks if username is unique
	 *
	 * @param string $username
	 * @return bool
	 */
	public function checkUniqueUsername ( $username ) 
	{
		
	}

	/**
	 * registers a new user
	 *
	 * @param array $regData
	 * @return bool
	 */
	public function registerUser ( $regData ) 
	{
		$account = new Tinebase_Account_Model_FullAccount($regData);
		$contact = new Addressbook_Model_Contact($regData);
		
	}
}
?>