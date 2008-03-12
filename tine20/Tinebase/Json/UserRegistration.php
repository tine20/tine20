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
	 * @param 	array $regData		json data from registration frontend
	 * @return 	string
	 * 
	 * @todo 	add other methods for building username
	 */
	public function suggestUsername ( $regData ) 
	{
		$regDataArray = Zend_Json_Decoder::decode($regData);

		//-- get method from config (email, firstname+lastname, other strings)
		
		// build username from firstname (first char) & lastname
		$suggestedUsername = substr($regDataArray['accountFirstName'],0,1).$regDataArray['accountLastName'];
		
		return $suggestedUsername;
	}

	/**
	 * checks if username is unique
	 *
	 * @param 	string $username
	 * @return 	bool
	 * 
	 * @todo 	test function
	 * @todo	getAccountByLoginName not working yet (error if account doesn't exist)
	 */
	public function checkUniqueUsername ( $username ) 
	{
		$username = Zend_Json_Decoder::decode($username);
		
		// if exists -> return false
		//-- is it ok to use the try/catch mechanism or should we implement a accountExists-function in the account controller/model?
		try {
			Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' call getAccountByLoginName with username '.$username);
			
			// get account with this username from db
			//-- still an error if account doesn't exist
			$account = Tinebase_Account::getInstance()->getAccountByLoginName($username);
			return false;
		} catch ( Exception $e ) {
			return true;
		}
		
		/*if ( empty($account) ) {
			return true;
		} else {
			return false;	
		}*/
	}

	/**
	 * registers a new user
	 *
	 * @param 	array $regData 		json data from registration frontend
	 * @return 	bool
	 * 
	 * @todo 	test function
	 */
	public function registerUser ( $regData ) 
	{

		$regData = Zend_Json_Decoder::decode($regData);
		
		// get models
		$account = new Tinebase_Account_Model_FullAccount($regData);
		$contact = new Addressbook_Model_Contact($regData);

		// save user data (account & contact) via the Account and Addressbook controllers
		//Tinebase_Account::getInstance()->saveAccount ( $account );
		// use new function: addAccount	(saves the contact as well)
		Tinebase_Account::getInstance()->addAccount ( $account );

		//-- no longer needed?
		//-- set account id in contact first
 		//Addressbook_Controller::getInstance()->saveContact ( $contact );
 		
		// send mail
		if ( $this->sendRegistrationMail( $regData ) ) {
			return true;			
		} else {
			return false;
		}
		
	}
	
	/**
	 * send registration mail
	 *
	 * @param 	array $_regData
	 * @return 	bool
	 * 
	 * @todo 	add more texts to mail views
	 * @todo	set correct activation link
	 * @todo	translate mails
	 */
	protected function sendRegistrationMail ( $_regData ) 
	{

		$mail = new Tinebase_Mail('UTF-8');
        
        $mail->setSubject("Welcome to Tine 2.0");
        
        $recipientName = $_regData['accountFirstName']." ".$_regData['accountLastName'];

        // get plain and html message from views
        //-- translate text and insert correct link
       	$view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->mailTextWelcome = "Welcome to Tine 2.0";
        $view->mailActivationLink = '<a href="http://www.tine20.org">activate!</a>';
        
        $messagePlain = $view->render('registrationMailPlain.php');       
        $mail->setBodyText($messagePlain);

        $messageHtml = $view->render('registrationMailHtml.php');
        if($messageHtml !== NULL) {
            $mail->setBodyHtml($messageHtml);
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
	 * @param 	string $_username
	 * @return 	bool
	 * 
	 * @todo 	add more texts to mail views
	 * @todo	translate mails
	 * @todo 	test!
	 */
	public function sendLostPasswordMail ($_username) 
	{
		// get full account
		$fullAccount = Tinebase_Account::getInstance()->getFullAccountByLoginName($_username);
				
		// generate new password
		$newPassword = $this->generatePassword();
		
		// save new password in account
		Tinebase_Auth::getInstance()->setPassword($_username, $newPassword, $newPassword);
		
		// send lost password mail		
		$mail = new Tinebase_Mail('UTF-8');        
        $mail->setSubject("New password for Tine 2.0");
        
        // get name from account
        //$recipientName = $fullAccount->accountFirstName." ".$fullAccount->accountLastName;
        $recipientName = $fullAccount->accountFullName;
        
        // get email from account
        $recipientEmail = $fullAccount->accountEmailAddress;

        // get plain and html message from views
        //-- translate text and insert correct link
       	$view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->mailTextWelcome = "We generated a new password for you ...";
        $view->newPassword = $newPassword;
        
        $messagePlain = $view->render('lostpwMailPlain.php');       
        $mail->setBodyText($messagePlain);

        $messageHtml = $view->render('lostpwMailHtml.php');
        if($messageHtml !== NULL) {
            $mail->setBodyHtml($messageHtml);
        }
        
        $mail->addHeader('X-MailGenerator', 'Tine 2.0');
        $mail->setFrom('webmaster@tine20.org', 'Tine 2.0 Webmaster');

        if( !empty($recipientEmail) ) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' send lost password email to ' . $recipientEmail);

            $mail->addTo($recipientEmail, $recipientName);
        
            $mail->send();
            
            return true;
        }
		
        return false;
	}

	/**
	 * generate new random password
	 *
	 * @param	int	$length
	 * @return 	string
	 * 
	 * @todo 	test!
	 */
	private function generatePassword ( $length = 8 ) 
	{
		$somestring = md5(uniqid());
		
		$begin = rand(0,strlen($somestring)-$length);
		return ( substr($somestring, $begin, $length) );
	}
}
