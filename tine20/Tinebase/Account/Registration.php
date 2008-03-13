<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo		finish class
 * @todo		test it!
 * @todo		add functions from Tinebase_Http
 * @todo 		create model class for registrations table
 */

/**
 * Account Registration class (singleton pattern)
 * 
 * @package     Tinebase
 * @subpackage  Account
 */
class Tinebase_Account_Registration
{

	/**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Account_Sql
     */
    private static $_instance = NULL;
    
   /**
     * the singleton pattern
     *
     * @return Tinebase_Account_Registration
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Account_Registration;
        }
        
        return self::$_instance;
    }

    /**
	 * checks if username is unique
	 *
	 * @param 	string $_username
	 * @return 	bool
	 * 
	 * @todo 	test function
	 */
	public function checkUniqueUsername ( $_username ) 
	{		
		// if exists -> return false
		//-- is it ok to use the try/catch mechanism or should we implement a accountExists-function in the account controller/model?
		try {
			Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' call getAccountByLoginName with username '.$_username);
			
			// get account with this username from db
			//-- still an error if account doesn't exist
			$account = Tinebase_Account::getInstance()->getAccountByLoginName($_username);
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
	 * @todo	save default account values elsewhere (where?) ?
	 */
	public function registerUser ( $regData ) 
	{
		
		Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' call registerUser with regData: '. print_r($regData, true));

		// validate email address
		require_once 'Zend/Validate/EmailAddress.php';
		$validator = new Zend_Validate_EmailAddress();
		if ( $validator->isValid($regData['accountEmailAddress']) == false ) {
		    // email is invalid; print the reasons
		    $debugMessage = __METHOD__ . '::' . __LINE__ . ' invalid registration email address: '. $regData['accountEmailAddress']."\n";
		    foreach ($validator->getMessages() as $message) {
		        $debugMessage .= "$message\n";
		    }
		    Zend_Registry::get('logger')->debug($debugMessage);
		    
		    // @todo throw exception?
		    return false;
		}
				
		// add more required fields to regData
		$regData['accountStatus'] = 'A'; 
		$regData['accountPrimaryGroup'] = '-4'; //-- ?
		$regData['accountDisplayName'] = $regData['accountFirstName'].' '.$regData['accountLastName']; 
		$regData['accountFullName'] = $regData['accountDisplayName']; 

		// add expire date (user has 1 day to click on the activation link)
		$regData['accountExpires'] = new Zend_Date ();
		//  add 1 day
		$regData['accountExpires']->add('24:00:00', Zend_Date::TIMES);
		
		// get model & save user data (account & contact) via the Account and Addressbook controllers
		$account = new Tinebase_Account_Model_FullAccount($regData);
		Tinebase_Account::getInstance()->saveAccount($account);

		// generate password and save it
		$regData['password'] = $this->generatePassword();
		Tinebase_Auth::getInstance()->setPassword($regData['accountLoginName'], $regData['password'], $regData['password']);
		
		// @todo use new function: addAccount ?
		//Tinebase_Account::getInstance()->addAccount ( $account );
 				
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
	 */
	protected function sendRegistrationMail ( $_regData ) 
	{

		$mail = new Tinebase_Mail('UTF-8');
        
        $mail->setSubject("Welcome to Tine 2.0");
        
        $recipientName = $_regData['accountDisplayName'];
        $recipientEmail = $_regData['accountEmailAddress'];

        // get plain and html message from views
        // @todo translate mail texts
       	$view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        // create hash from username
        $hashed_username = md5($_regData['accountLoginName']);
        
        // set texts and values
        $view->mailTextWelcome = "Welcome to Tine 2.0";
        $view->mailActivationLink = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].
        	'?method=Tinebase.activateAccount&id='.$hashed_username;
        $view->username = $_regData['accountLoginName'];
        $view->password = $_regData['password'];
        
        $messagePlain = $view->render('registrationMailPlain.php');       
        $mail->setBodyText($messagePlain);

        $messageHtml = $view->render('registrationMailHtml.php');
        if($messageHtml !== NULL) {
            $mail->setBodyHtml($messageHtml);
        }
        
        $mail->addHeader('X-MailGenerator', 'Tine 2.0');
        $mail->setFrom('webmaster@tine20.org', 'Tine 2.0 Webmaster');

        if( !empty($recipientEmail) ) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' send registration email to ' . $recipientEmail);

            $mail->addTo($recipientEmail, $recipientName);
        
            $mail->send();
            
            // @todo save in registrations table 
            //Tinebase_Account::getInstance()->saveRegistration($_regData['accountLoginName'], $hashed_username, $recipientEmail);
            
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
	 * generate new random password [a-zA-z0-9]
	 *
	 * @param	int	$length
	 * @return 	string
	 * 
	 */
	private function generatePassword ( $length = 8 ) 
	{
		$password = "";
		for ($i = 0; $i < $length; $i++) {
	    	$rdnum = mt_rand(0,61);
		    if ($rdnum < 10) {
		        $password .= $rdnum;
		    } else if ($rdnum < 36) {
		        $password .= chr($rdnum+55);
		    } else {
		        $password .= chr($rdnum+61);
		    }
		}		
		return $password;
	}
}
