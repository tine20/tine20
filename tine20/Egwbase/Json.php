<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Json interface to Egwbase
 * 
 * @package     Egwbase
 * @subpackage  Server
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
    
    function getAccounts($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Egwbase_Account::getInstance()->getAccounts($filter, $sort, $dir, $start, $limit)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                //$result['totalcount'] = $backend->getCountByAddressbookId($addressbookId, $filter);
            }
        }
        
        return $result;
    }

    function getContainer($application, $nodeType, $owner=NULL)
    {    	
        switch($nodeType) {
            case 'Personal':
                if (!$owner) throw new Exception('No owner given');
                $container = Egwbase_Container::getInstance()->getPersonalContainer($application,$owner);
                break;
            case 'Shared':
                $container = Egwbase_Container::getInstance()->getSharedContainer($application);
                break;
            case 'OtherUsers':
                $container = Egwbase_Container::getInstance()->getOtherUsers($application);
                break;
            default:
                throw new Exception('no such NodeType');
        }
        echo Zend_Json::encode($container->toArray());

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }
    
    
    /**
     * change password of user 
     *
     * @param string $oldPw the old password
     * @param string $newPw the new password
     * @return array
     */
    function changePassword($oldPw, $newPw)
    {
        $auth = Zend_Auth::getInstance();        
              
        $oldIsValid = Egwbase_Controller::getInstance()->isValidPassword($auth->getIdentity(), $oldPw);              

        if ($oldIsValid === true) {
            $_account   = Egwbase_Account::getInstance();
            $result     = $_account->setPassword(Zend_Registry::get('currentAccount')->accountId, $newPw);
            
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
        
        return $res;
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
        $result = Egwbase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR']);
        
        $egwBaseNamespace = new Zend_Session_Namespace('egwbase');

        if ($result->isValid()) {
            $egwBaseNamespace->isAutenticated = TRUE;

            $response = array(
				'success'        => TRUE,
                'welcomeMessage' => "Some welcome message!"
			);
        } else {
            $egwBaseNamespace->isAutenticated = FALSE;

            $response = array(
				'success'      => FALSE,
				'errorMessage' => "Wrong username or passord!"
			);
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
        Egwbase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
        
        $result = array(
			'success'=> true,
        );

        return $result;
    }
}
