<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class to handle webdav authentication
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Auth extends Sabre_DAV_Auth_Backend_AbstractDigest {

    /**
     * Returns a users' information 
     * 
     * @param string $realm 
     * @param string $username 
     * @return string 
     */
    public function getUserInfo($realm,$username) {

        return array(
            'userId' => $username,
            'digestHash' => $username,
        );

    }

    public function getUsers() {

        $result = array(
            array('username' => 'lars')
        );
        
        $rv = array();
        foreach($result as $user) {

            $rv[] = array(
                'userId' => $user['username'],
            );

        }

        return $rv;

    }
    
    /**
     * Authenticates the user based on the current request.
     *
     * If authentication succeeds, a struct with user-information must be returned
     * If authentication fails, this method must throw an exception. 
     *
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return void
     */
    public function authenticate(Sabre_DAV_Server $server,$realm) {
        $userData = $this->getUserInfo($realm, $username);
        
        return $userData;
    }
}
