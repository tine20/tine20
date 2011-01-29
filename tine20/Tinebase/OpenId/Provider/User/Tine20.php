<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  OpenID
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
    protected $_tine20Session;

    /**
     * Zend_Auth session namespace
     *
     * @var Zend_Session_Namespace
     */
    protected $_openIdSession;
    
    /**
     * constructor
     *
     * @param Zend_Session_Namespace $session
     */
    public function __construct(Zend_Session_Namespace $session = null)
    {
        $this->_tine20Session = new Zend_Session_Namespace("Zend_Auth");
        $this->_openIdSession = new Zend_Session_Namespace("openid");
    }

    /**
     * Stores information about logged in user in session data
     *
     * @param string $id user identity URL
     * @return bool
     */
    public function setLoggedInUser($id)
    {
        $this->_openIdSession->logged_in = $id;
        return true;
    }

    /**
     * Returns identity URL of logged in user or false
     *
     * @return mixed
     */
    public function getLoggedInUser()
    {
        if (isset($this->_tine20Session->storage)) {
            return dirname(Zend_OpenId::selfUrl()) . '/users/' . $this->_tine20Session->storage;
        }
        if (isset($this->_openIdSession->logged_in)) {
            return $this->_openIdSession->logged_in;
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
        unset($this->_openIdSession->logged_in);
        
        return true;
    }
}
