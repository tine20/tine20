<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Json interface to Tinebase
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Json
{
	
	/**
	 * register dependend classes
	 */
	public static function setJsonServers($_server)
	{
	    $_server->setClass('Tinebase_Json_Container', 'Tinebase_Container');
	    $_server->setClass('Tinebase_Json_UserRegistration', 'Tinebase_UserRegistration');
	}
	
    /**
     * get list of translated country names
     *
     * @return array list of countrys
     */
    public function getCountryList()
    {
        $locale = Zend_Registry::get('locale');

        $countries = $locale->getCountryTranslationList();
        asort($countries);
        foreach($countries as $shortName => $translatedName) {
            $results[] = array(
				'shortName'         => $shortName, 
				'translatedName'    => $translatedName
            );
        }

        $result = array(
			'results'	=> $results
        );

        return $result;
    }
    
    public function getAccounts($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Tinebase_Account::getInstance()->getAccounts($filter, $sort, $dir, $start, $limit)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                //$result['totalcount'] = $backend->getCountByAddressbookId($addressbookId, $filter);
            }
        }
        
        return $result;
    }

    /**
     * change password of user 
     *
     * @param string $oldPw the old password
     * @param string $newPw the new password
     * @return array
     */
    public function changePassword($oldPassword, $newPassword)
    {
        $response = array(
            'success'      => TRUE
        );
        
        try {
            Tinebase_Controller::getInstance()->changePassword($oldPassword, $newPassword, $newPassword);
        } catch (Exception $e) {
            $response = array(
                'success'      => FALSE,
                'errorMessage' => "new password could not be set!"
            );   
        }
        
        return $response;
        
/*        
        $auth = Zend_Auth::getInstance();        
              
        $oldIsValid = Tinebase_Controller::getInstance()->isValidPassword($auth->getIdentity(), $oldPw);              

        if ($oldIsValid === true) {
            $_account   = Tinebase_Account::getInstance();
            $result     = $_account->setPassword(Zend_Registry::get('currentAccount')->getId(), $newPw);
            
            if($result == 1) {
                $res = array(
    				'success'      => TRUE);                
            } else {
                 $res = array(
    				'success'      => FALSE,
	    			'errorMessage' => "new password could'nt be set!");   
            }
        } else {
            $res = array(
				'success'      => FALSE,
				'errorMessage' => "old password is wrong!");
        }
        
        return $res;*/
    }    
    
    
    /**
     * authenticate user by username and password
     *
     * @param string $username the username
     * @param string $password the password
     * @return array
     */
    public function login($username, $password)
    {
        if (Tinebase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR']) === true) {
            $response = array(
				'success'       => TRUE,
                'account'       => Zend_Registry::get('currentAccount')->getPublicAccount()->toArray(),
                'welcomeMessage' => "Welcome to Tine 2.0!"
			);
        } else {
            $response = array(
				'success'      => FALSE,
				'errorMessage' => "Wrong username or password!"
			);
        }

        return $response;
    }

    /**
     * destroy session
     *
     * @return array
     */
    public function logout()
    {
        Tinebase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
        
        $result = array(
			'success'=> true,
        );

        return $result;
    }
}
