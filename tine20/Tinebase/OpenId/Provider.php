<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  OpenID
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * OpenID provider (server) implementation for Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  OpenID
 */
class Tinebase_OpenId_Provider extends Zend_OpenId_Provider
{
    /**
     * Performs login of user with given $id, $password and $username
     * Returns true in case of success and false otherwise
     *
     * @param string $id user identity URL
     * @param string $password user password
     * @param string $username Tine 2.0 login name
     * @return bool
     */
    public function login($id, $password, $username = null)
    {
        if (!Zend_OpenId::normalize($id)) {
            return false;
        }
        
        if (!$this->_storage->checkUser($id, $password, $username)) {
            return false;
        }
        
        $this->_user->setLoggedInUser($id);
        
        return true;
    }        
}
