<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * -- not working yet --
 * @todo		finish class
 * @todo		test it!
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
	 * 
	 * @todo add other methods for building username
	 */
	public function suggestUsername ( $regData ) 
	{
		//-- get method from config (email, firstname+lastname, other strings)
		
		// build username from firstname (first char) & lastname
		return substr($regData['firstname'],0,1).$regData['lastname'];
	}

	/**
	 * checks if username is unique
	 *
	 * @param string $username
	 * @return bool
	 */
	public function checkUniqueUsername ( $username ) 
	{
		// get account with this username from db
		$accountsController = Tinebase_Account::getInstance();
		$account = $accountsController->getAccountByLoginName($username);
		
		// if exists -> return false
		return empty($account);
	}

	/**
	 * registers a new user
	 *
	 * @param array $regData
	 * @return bool
	 * 
	 * @todo finish function
	 */
	public function registerUser ( $regData ) 
	{
		// get models
		$account = new Tinebase_Account_Model_FullAccount($regData);
		$contact = new Addressbook_Model_Contact($regData);

		//-- save user data
		
		// send mail
		this.sendRegistrationMail();
	}
	
	/**
	 * send registration mail
	 *
	 * @return bool
	 * 
	 * @todo implement function
	 */
	protected function sendRegistrationMail () 
	{
		//-- send registration mail		
	}

	/**
	 * send lost password mail
	 *
	 * @return bool
	 * 
	 * @todo implement function
	 */
	public function sendLostPasswordMail () 
	{
		//-- generate new password
		//-- send lost password mail		
	}
	
}
?>