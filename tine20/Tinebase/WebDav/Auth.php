<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav authentication
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Auth extends Sabre_DAV_Auth_Backend_Abstract {

    /**
     * Returns a users' information 
     * 
     * @param  string  $_realm 
     * @param  string  $_username 
     * @return string 
     */
    public function getUserInfo($_realm, $_username) 
    {
        if ($_username == Tinebase_Core::getUser()->accountLoginName) {
            $userInfo = array(
                'uri'                                   => 'principals/' . Tinebase_Core::getUser()->accountLoginName,
                '{http://sabredav.org/ns}email-address' => Tinebase_Core::getUser()->accountEmailAddress,
                '{DAV:}displayname'                     => Tinebase_Core::getUser()->accountDisplayName
            ); 
        } else {
            array(
                'uri'               => 'principals/' . $_username,
            	'{DAV:}displayname' => 'unknown user'
            );
        }
        
        return $userInfo;
    }

    /**
     * Returns information about the currently logged in user.
     *
     * If nobody is currently logged in, this method should return null.
     * 
     * @return array|null
     */
    public function getCurrentUser()
    {
        return $this->getUserInfo(null, Tinebase_Core::getUser()->accountLoginName);
    }
    
    public function getUsers() 
    {
        // lis of all users
        $result = array(
            Tinebase_Core::getUser()
        );
        
        $rv = array();
        
        foreach($result as $user) {
            $rv[] = array(
                'uri'                                   => 'principals/' . $user->accountLoginName,
                '{http://sabredav.org/ns}email-address' => $user->accountEmailAddress,
                '{DAV:}displayname'                     => $user->accountDisplayName
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
    public function authenticate(Sabre_DAV_Server $_server, $_realm) 
    {
        $userData = $this->getUserInfo($_realm, $_username);
        
        return $userData;
    }
}
