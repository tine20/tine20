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
	 * @param array $_regData
	 * @return string
	 * 
	 * @todo add other methods for building username
	 */
	public function suggestUsername ( $_regData ) 
	{
		//-- get method from config (email, firstname+lastname, other strings)
		
		// build username from firstname (first char) & lastname
		return substr($_regData['firstname'],0,1).$_regData['lastname'];
	}

	/**
	 * checks if username is unique
	 *
	 * @param string $username
	 * @return bool
	 * 
	 * @todo test function
	 */
	public function checkUniqueUsername ( $_username ) 
	{
		// get account with this username from db
		$accountsController = Tinebase_Account::getInstance();
		$account = $accountsController->getAccountByLoginName($_username);
		
		// if exists -> return false
		if ( empty($account) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * registers a new user
	 *
	 * @param array $_regData
	 * @return bool
	 * 
	 * @todo test function
	 */
	public function registerUser ( $_regData ) 
	{
		// get models
		$account = new Tinebase_Account_Model_FullAccount($_regData);
		$contact = new Addressbook_Model_Contact($_regData);

		// save user data (account & contact) via the Account and Addressbook controllers
		Tinebase_Account::getInstance()->saveAccount ( $account );		
 		Addressbook_Controller::getInstance()->saveContact ( $contact );
 		
		// send mail
		this.sendRegistrationMail( $_regData );
	}
	
	/**
	 * send registration mail
	 *
	 * @param array $_regData
	 * @return bool
	 * 
	 * @todo testen
	 */
	protected function sendRegistrationMail ( $_regData ) 
	{

		$mail = new Tinebase_Mail('UTF-8');
        
        $mail->setSubject("Welcome to Tine 2.0");
        
        $recipientName = $_regData['firstname']." ".$_regData['lastname'];

        //-- get plain and html message from ??
        $messagePlain = "Welcome $recipientName to Tine 2.0\n";
        $messageHtml = NULL;
        
        $mail->setBodyText($messagePlain);

        if($_messageHtml !== NULL) {
            $mail->setBodyHtml($_messageHtml);
        }
        
        $mail->addHeader('X-MailGenerator', 'Tine 2.0');
        $mail->setFrom('webmaster@tine20.org', 'Tine 2.0 Webmaster');

        if( !empty($_regData['email']) ) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' send registration email to ' . $_regData['email']);

            $mail->addTo($_regData['email'], $recipientName);
        
            $mail->send();
            
            return true;
        }
		
        return false;
	}

	/**
	 * send lost password mail
	 *
	 * @param array $_regData
	 * @return bool
	 * 
	 * @todo implement function
	 */
	public function sendLostPasswordMail ($_regData) 
	{
		//-- generate new password
		//-- send lost password mail		
		//-- add generic sendMail function ?
	}
	
}
?>
