<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Json interface to Egwbase
 */
class Egwbase_Json
{
    /**
     * get list of translated country names
     *
     * @return array list of countrys
     */
    function getCountryList()
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

    /**
     * authenticate user by username and password
     *
     * @param string $username the username
     * @param string $password the password
     * @return array
     */
    function login($username, $password)
    {
        $egwBaseNamespace = new Zend_Session_Namespace('egwbase');

        $auth = Zend_Auth::getInstance();

        $authAdapter = Egwbase_Auth::factory(Egwbase_Auth::SQL);

        $authAdapter->setIdentity($username)
            ->setCredential($password);
        	
        $result = $auth->authenticate($authAdapter);

        if ($result->isValid()) {
            $egwBaseNamespace->isAutenticated = TRUE;
            $egwBaseNamespace->currentAccount = $authAdapter->getResultRowObject(NULL, array('account_pwd'));

            $response = array(
				'success'        => TRUE,
                'welcomeMessage' => "Some welcome message!"
			);
					
			$accesslog = new Egwbase_AccessLog();
            
			$accesslog->addLoginEntry(
    			session_id(),
    			$username,
    			$_SERVER['REMOTE_ADDR'],
    			$result->getCode(),
                $egwBaseNamespace->currentAccount->account_id
    		);
        } else {
            $egwBaseNamespace->isAutenticated = FALSE;

            $response = array(
				'success'      => FALSE,
				'errorMessage' => "Wrong username or passord!"
			);

			$accesslog = new Egwbase_AccessLog();
			$accesslog->addLoginEntry(
    			session_id(),
    			$username,
    			$_SERVER['REMOTE_ADDR'],
    			$result->getCode()
			);
            $accesslog->addLogoutEntry(
                session_id(),
                $_SERVER['REMOTE_ADDR']
            );
            
            Zend_Session::destroy();
            
			sleep(2);
        }


        return $response;
    }

    /**
     * destroy session
     *
     * @return array
     */
    function logout()
    {
        if (Zend_Registry::isRegistered('currentAccount')) {
            $currentAccount = Zend_Registry::get('currentAccount');
    
            $accesslog = new Egwbase_AccessLog();
            $accesslog->addLogoutEntry(
                session_id(),
                $_SERVER['REMOTE_ADDR'],
                $currentAccount->account_id
            );
        }
        
        Zend_Session::destroy();

        $result = array(
			'success'=> true,
        );

        return $result;
    }
}
?>