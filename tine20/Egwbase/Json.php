<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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

    /**
     * gets container / container folder
     * 
     * Backend function for containerTree widget
     * 
     * @param string $application
     * @param string $containerType
     * @param string $owner
     * @return string JSON
     */
    public function getContainer($application, $containerType, $owner =  NULL)
    {    	
        switch($containerType) {
            case Egwbase_Container::TYPE_PERSONAL:
                $container = Egwbase_Container::getInstance()->getPersonalContainer($application,$owner);
                break;
            case Egwbase_Container::TYPE_SHARED:
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
     * adds a container
     * 
     * @param string $application
     * @param string $containerName
     * $param string $containerType
     * @return array new container
     */
    public function addContainer($application, $containerName, $containerType)
    {
        switch($containerType) {
            case Egwbase_Container::TYPE_SHARED:
                $container = Egwbase_Container::getInstance()->addSharedContainer($application, $containerName);
                break;
            case Egwbase_Container::TYPE_PERSONAL:
                $container = Egwbase_Container::getInstance()->addPersonalContainer($application, $containerName);
                break;
            default:
                throw new Exception('no such containerType');
        }
        return $container->toArray();
    }
    
    /**
     * deletes a container
     * 
     * @param int $containerId
     * @return string success
     * @throws Exception
     */
    public function deleteContainer($containerId)
    {
        Egwbase_Container::getInstance()->deleteContainer($containerId);
        return 'success';
    }
    
    /**
     * renames a container
     * 
     * @param int $containerId
     * $param string $newName
     * @return array updated container
     */
    public function renameContainer($containerId, $newName)
    {
        $container = Egwbase_Container::getInstance()->renameContainer($containerId, $newName);
        return $container->toArray();
    }
    
    /**
     * returns container grants
     * 
     * @param int $containerId
     * @return array
     * @throws Exception
     */
    public function getContainerGrants($containerId) {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $result['results'] = Egwbase_Container::getInstance()->getAllGrants($containerId)->toArray();
        $result['totalcount'] = count($result['results']);
        
        foreach($result['results'] as &$value) {
            if($value['accountId'] === NULL) {
                $value['accountName'] = array('accountDisplayName' => 'Anyone');
            } else {
                $value["accountName"] = Egwbase_Account::getInstance()->getAccountById($value['accountId'])->toArray();
            }
        }
        
        return $result;
    }
    
    /**
     * sets new grants for given container
     * 
     * @param int $containerId
     * @param array $grants
     * @return array, see getContainerGrants
     */
    public function setContainerGrants($containerId, $grants)
    {
        $newGrants = new Egwbase_Record_RecordSet(Zend_Json::decode($grants), 'Egwbase_Record_Grants');
        
        Egwbase_Container::getInstance()->setAllGrants($containerId, $newGrants);
               
        return $this->getContainerGrants($containerId);
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
            Egwbase_Controller::getInstance()->changePassword($oldPassword, $newPassword, $newPassword);
        } catch (Exception $e) {
            $response = array(
                'success'      => FALSE,
                'errorMessage' => "new password could not be set!"
            );   
        }
        
        return $response;
        
/*        
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
    public function logout()
    {
        Egwbase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
        
        $result = array(
			'success'=> true,
        );

        return $result;
    }
}
