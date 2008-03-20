<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
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
     * the registrations table
     *
     * @var Tinebase_Db_Table
     */
    protected $registrationsTable;
    	
	/**
     * the config
     *
     * @var Zend_Config_Ini 
     */
	private $_config = NULL;

	/**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        // get config
        try {
            $this->_config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'registration');
        } catch (Zend_Config_Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' no config for registration found! '. $e->getMessage());
        }
        
        // create table object
        $this->registrationsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'registrations'));
    }
    
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
	 * @param 	string 	$_username
	 * @return 	bool	true if username is unique
	 * 
	 */
	public function checkUniqueUsername ( $_username ) 
	{		
		// if exists -> return false
		try {
			Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' call getAccountByLoginName with username '.$_username);
			
			// get account with this username from db
			$account = Tinebase_Account::getInstance()->getAccountByLoginName($_username);
			return false;
		} catch ( Exception $e ) {
			return true;
		}
		
	}

	/**
	 * registers a new user
	 *
	 * @param 	array 	$regData 		json data from registration frontend
	 * @param	bool	$_sendMail		send registration mail
	 * @return 	bool
	 * 
	 */
	public function registerUser ( $regData, $_sendMail = true ) 
	{
		
		Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' call registerUser with regData: '. print_r($regData, true));

		// validate unique username
		//@todo 	move to frontend later on
		if ( !$this->checkUniqueUsername ($regData['accountLoginName']) ) {
			throw ( new Exception('username already exists') );
		}
		
		// validate email address
		if ( isset($this->_config->emailValidation) && $this->_config->emailValidation == 'zend' ) {
			// zend email validation isn't working on a 64bit os at the moment (v1.5.0)
			require_once 'Zend/Validate/EmailAddress.php';
			$validator = new Zend_Validate_EmailAddress();
			if ( $validator->isValid($regData['accountEmailAddress']) == false ) {
			    // email is invalid; print the reasons
			    //$debugMessage = __METHOD__ . '::' . __LINE__ . ' invalid registration email address: '. $regData['accountEmailAddress']."\n";
			    $debugMessage = 'Invalid registration email address: '. $regData['accountEmailAddress']. '( ';
			    foreach ($validator->getMessages() as $message) {
			        $debugMessage .= $message." ";
			    }
			    $debugMessage .= ')';
			    //Zend_Registry::get('logger')->debug($debugMessage);
			    
			    // throw exception
			    throw ( new Exception('invalid registration email address: '.$debugMessage) );  
			}
		}
				
		// add more required fields to regData
		// get default values from config.ini if available
		$regData['accountStatus'] = ( isset($this->_config->accountStatus) ) ? $this->_config->accountStatus : 'enabled'; 
		$regData['accountDisplayName'] = $regData['accountFirstName'].' '.$regData['accountLastName']; 
		$regData['accountFullName'] = $regData['accountDisplayName']; 
		
		// get groupbyname
		$primaryGroupName = ( isset($this->_config->accountPrimaryGroup) ) ? $this->_config->accountPrimaryGroup : 'Users';
		$primaryGroup = Tinebase_Group::getInstance()->getGroupByName( $primaryGroupName );
		$regData['accountPrimaryGroup'] = $primaryGroup->getId();

		// add expire date (user has 1 day to click on the activation link)
		if ( isset($this->_config->expires) && $this->_config->expires > 0 ) {
			$regData['accountExpires'] = new Zend_Date ();
			// add 'expires' from config hours
			$timeToAdd = $this->_config->expires.":00:00";
			Zend_Registry::get('logger')->debug("this account expires in $timeToAdd hours ...");
			$regData['accountExpires']->add($timeToAdd, Zend_Date::TIMES);
		} else {
			Zend_Registry::get('logger')->debug("this account never expires.");
			$regData['accountExpires'] = NULL;
		}
		
		// get model & save user data (account & contact) via the Account and Addressbook controllers
		$account = new Tinebase_Account_Model_FullAccount($regData);
		Tinebase_Account::getInstance()->addAccount ( $account );
		Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' saved user account '.$regData['accountLoginName']);
		
		// generate password and save it
		$regData['password'] = $this->generatePassword();
		Tinebase_Auth::getInstance()->setPassword($regData['accountLoginName'], $regData['password'], $regData['password']);
		
        // create hash from username
        $regData['accountLoginNameHash'] = md5($regData['accountLoginName']);

        // save in registrations table 
        $registration = new Tinebase_Account_Model_Registration ( array( "login_name" => $regData['accountLoginName'],
	            														 "login_hash" => $regData['accountLoginNameHash'],
	            														 "email" =>  $regData['accountEmailAddress'],
        																  ) );
        $registration = $this->addRegistration($registration);
        
		// send mail?
		if ( $_sendMail ) {
			$result = $this->sendRegistrationMail( $regData, $registration );
		} else {
			$result = true;
		}
		
		return $result;
		
	}
	
	/**
	 * create user hash, send registration mail and save registration in database
	 *
	 * @param 	array $_regData
	 * @param 	Tinebase_Account_Model_Registration $_registration
	 * @return 	bool
	 *
	 * @access	protected
	 * 
	 */
	protected function sendRegistrationMail ( $_regData, $_registration ) 
	{		

        $registration = $_registration;
        $updateRegistration = false;
		
		$mail = new Tinebase_Mail('UTF-8');
        
        $mail->setSubject("Welcome to Tine 2.0");
        
        $recipientName = $_regData['accountDisplayName'];
        $recipientEmail = $_regData['accountEmailAddress'];
        $hashedUsername = $_regData['accountLoginNameHash'];
        
        // get plain and html message from views
        // @todo translate mail texts
       	$view = new Zend_View();
        $view->setScriptPath(dirname(dirname(__FILE__)). DIRECTORY_SEPARATOR .'views');
       	       	                
        // set texts and values
        $view->mailTextWelcome = "Welcome to Tine 2.0";
        // if expires = 0 -> no activation link in email
        if ( isset($this->_config->expires) && $this->_config->expires > 0 && isset($_SERVER['SERVER_NAME']) ) {        	
        	$view->mailActivationLink = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].
        		'?method=Tinebase.activateAccount&id='.$hashedUsername;
            
        	// deactivate registration
            $registration->status = 'waitingforactivation';
            $updateRegistration = true;
        }
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

        $result = false;
        
        if( !empty($recipientEmail) ) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' send registration email to ' . $recipientEmail);

            $mail->addTo($recipientEmail, $recipientName);
        
            if ( $mail->send() ) {
            
	            // update registration table with "mail sent"
	           	$registration->email_sent = 1;
	            $updateRegistration = true;
	           	
            	$result = true;
            }
        }
        
        if ( $updateRegistration ) {
	        $this->updateRegistration( $registration );
        }    
		
        return $result;
	}
		
	/**
	 * send lost password mail
	 *
	 * @param 	string $_username
	 * @return 	bool
	 * 
	 * @todo 	add more texts to mail views & translate mails
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
        $view->setScriptPath(dirname(dirname(__FILE__)). DIRECTORY_SEPARATOR .'views');
       	        
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
	 * @access	private
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
	
	/**
	 * activate user account
	 *
	 * @param 	string $_login_hash
	 * @return	Tinebase_Account_Model_FullAccount
	 * 
	 */
	public function activateAccount ( $_login_hash ) 
	{		
		
       	// get registration by hash
       	$registration = $this->getRegistrationByHash ( $_login_hash );
		
		// set new status in DB (registration)
		$registration->status = 'activated';
       	$this->updateRegistration ( $registration );

       	// get account by username
		$account = Tinebase_Account::getInstance()->getFullAccountByLoginName($registration['login_name']);

		// set new expire_date in DB (account)
		Tinebase_Account::getInstance()->setExpiryDate($account['accountId'], NULL);
		
		return $account;	
	}
	
	/**
	 * generate captcha
	 *
	 * @return 	image	the captcha image
	 * 
	 * @todo 	save security code in db/session
	 */
	public function generateCaptcha () 
	{	
		// get security code (use password generator)
		$security_code = $this->generatePassword(4);
		
       	//Set the image width and height
        $width = 120;
        $height = 20;

        // Create the image resource
        $image = ImageCreate($width, $height);  
		
        // get colors, black background, set security code, add some lines
        $white = ImageColorAllocate($image, 255, 255, 255);
        $black = ImageColorAllocate($image, 0, 0, 0);
        $grey = ImageColorAllocate($image, 204, 204, 204);
        ImageFill($image, 0, 0, $black);
        ImageString($image, 4, 40, 4, $security_code, $white);
        ImageRectangle($image,0,0,$width-1,$height-1,$grey);
        imageline($image, 0, $height/2, $width, $height/2, $grey);
        imageline($image, $width/2, 0, $width/2, $height, $grey);

        return $image;
	}
	
	
	/********************************************************************
	 * SQL functions follow
	 */
	
	/**
	 * add new registration
	 *
	 * @param	Tinebase_Account_Model_Registration	$_registration
	 * @return 	Tinebase_Account_Model_Registration the new registration object
	 * 
	 * @access	protected
	 */
	protected function addRegistration ( $_registration ) 
	{

        if(!$_registration->isValid()) {
            throw(new Exception('invalid registration object'));
        }

        $registrationData = array (
        	"login_name" 	=> $_registration->login_name,
            "login_hash" 	=> $_registration->login_hash,
            "email" 		=> $_registration->email,
        	"date" 		=> Zend_Date::now()->getIso(),
        	"status"		=> "justregistered",
        );
        
        // add new account
        $this->registrationsTable->insert($registrationData);          

        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' added new registration entry with hash ' . $_registration->login_hash);
        
        return $this->getRegistrationByHash($_registration->login_hash);
		
	}

	/**
	 * update registration
	 *
	 * @param	Tinebase_Account_Model_Registration	$_registration
	 * @return 	Tinebase_Account_Model_Registration the updated registration object
	 * 
	 */
	public function updateRegistration ( Tinebase_Account_Model_Registration $_registration ) 
	{

        if(!$_registration->isValid()) {
            throw(new Exception('invalid registration object'));
        }

        $registrationData = array (
        	"login_name" 	=> $_registration->login_name,
            "login_hash" 	=> $_registration->login_hash,
            "email" 		=> $_registration->email,
        	"date" 		=> ($_registration->date instanceof Zend_Date ? $_registration->date->getIso() : NULL),
        	"status"		=> $_registration->status,
        	"email_sent"	=> $_registration->email_sent,
        );
        //--   
        $where = array(
            $this->registrationsTable->getAdapter()->quoteInto('id = ?', $_registration->id)
        );
        
        $result = $this->registrationsTable->update($registrationData, $where);
                
       	return $this->getRegistrationByHash($_registration->login_hash);
	}
	
	/**
	 * delete registration by username
	 *
	 * @param	string $_username
	 * @return	int		number of rows affected
	 */
	public function deleteRegistrationByLoginName ( $_username ) 
	{
		
        $where = Zend_Registry::get('dbAdapter')->quoteInto('login_name = ?', $_username);
        
        $result = $this->registrationsTable->delete($where);

        return $result;
	}
	
		
	/**
     * get registration by hash
     *
     * @param string $_hash the hash (md5 coded username) from the registration mail
     * @return Tinebase_Account_Model_Registration the registration object
     *
     */
    public function getRegistrationByHash($_hash)
    {
       	$db = Zend_Registry::get('dbAdapter');

       	$select = $db->select()
       				->from(SQL_TABLE_PREFIX . 'registrations' )
       				->where('login_hash = ?', $_hash );
    	
        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);

    	if ( $row === false ) {
        	throw ( new Tinebase_Record_Exception_NotDefined('registration entry not found error') );
            //Zend_Registry::get('logger')->debug(__CLASS__ . ":\n" . $e);
    	}        
        
		Zend_Registry::get('logger')->debug( __METHOD__ . '::' . __LINE__ . "Tinebase_Account_Model_Registration::row values: \n" .
                print_r($row,true));
        
        try {
            $registration = new Tinebase_Account_Model_Registration();
            $registration->setFromArray($row);
        } catch (Exception $e) {
        	$validation_errors = $registration->getValidationErrors();
            Zend_Registry::get('logger')->debug( __METHOD__ . '::' . __LINE__ . $e->getMessage() . "\n" .
                "Tinebase_Account_Model_Registration::validation_errors: \n" .
                print_r($validation_errors,true));
            throw ($e);
        }

        return $registration;
    }
    
}
