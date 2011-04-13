<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  OpenID
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * user handling via tine20 session data
 *
 * @package     Tinebase
 * @subpackage  OpenID
 */
class Tinebase_OpenId_Provider_User_Tine20 extends Zend_OpenId_Provider_User
{
    /**
     * Zend_Auth session namespace
     *
     * @var Zend_Session_Namespace
     */
    protected $_sessionNameSpace;
    
    /**
     * constructor
     *
     * @param Zend_Session_Namespace $session
     */
    public function __construct(Zend_Session_Namespace $session = null)
    {
        $this->_sessionNameSpace = new Zend_Session_Namespace("openid");
    }

    /**
     * Stores information about logged in user in session data
     *
     * @param string $id user identity URL
     * @return bool
     */
    public function setLoggedInUser($id)
    {
        $this->_sessionNameSpace->logged_in = $id;
        
        return true;
    }

    /**
     * Returns identity URL of logged in user or false
     *
     * @return mixed
     */
    public function getLoggedInUser()
    {
        // user is logged in via Tine 2.0 already
        if (($user = Tinebase_Core::getUser()) instanceof Tinebase_Model_FullUser) {
            return dirname(Zend_OpenId::selfUrl()) . '/users/' . (isset($user->openid) ? $user->openid : $user->accountLoginName);
        }
        
        // user has authenticated via OpenId before
        if (isset($this->_sessionNameSpace->logged_in)) {
            return $this->_sessionNameSpace->logged_in;
        }
        
        return false;
    }

    /**
     * Performs logout. resets data in OpenId session namespace
     *
     * @return bool
     */
    public function delLoggedInUser()
    {
        unset($this->_sessionNameSpace->logged_in);
        
        return true;
    }
}
