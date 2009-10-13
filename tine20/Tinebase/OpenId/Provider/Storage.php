<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  OpenId
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * @see Zend_OpenId_Provider_Storage
 */
require_once "Zend/OpenId/Provider/Storage.php";

/**
 * External storage implemmentation using sql table
 *
 * @package     Tinebase
 * @subpackage  OpenId
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */
class Tinebase_OpenId_Provider_Storage extends Zend_OpenId_Provider_Storage
{
    /**
     * the constructor
     */
    public function __construct() {
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * copy of Tinebase_Core::get('dbAdapter')
     *
     * @var Zend_Db_Adapter_Abstract
     */
    private $_db;
    
    /**
     * Stores information about session identified by $handle
     *
     * @param string $handle assiciation handle
     * @param string $macFunc HMAC function (sha1 or sha256)
     * @param string $secret shared secret
     * @param string $expires expiration UNIX time
     * @return bool
     */
    public function addAssociation($handle, $macFunc, $secret, $expires)
    {
        $backend = new Tinebase_OpenId_Backend_Association();
        
        $association = new Tinebase_Model_OpenId_Association(array(
            'id'        => $handle,
            'macfunc'   => $macFunc,
            'secret'    => $secret,
            'expires'   => $expires
        ));

        $backend->create($association);
    }
    
    /**
     * Gets information about association identified by $handle
     * Returns true if given association found and not expired and false
     * otherwise
     *
     * @param string $handle assiciation handle
     * @param string &$macFunc HMAC function (sha1 or sha256)
     * @param string &$secret shared secret
     * @param string &$expires expiration UNIX time
     * @return bool
     */
    public function getAssociation($handle, &$macFunc, &$secret, &$expires)
    {
        $backend = new Tinebase_OpenId_Backend_Association();

        $result = false;
        
        try {
            $association = $backend->get($handle);
            
            $macFunc    = $association->macfunc;
            $secret     = $association->secret;
            $expires    = $association->expires;
            
            if($expires > time()) {
                $result = true;
            }
        } catch (Tinebase_Exception_NotFound $e) {
            $result = false;
        }
        
        return $result;
    }
    
    /**
     * Removes information about association identified by $handle
     *
     * @param string $handle assiciation handle
     * @return bool
     */
    public function delAssociation($handle)
    {
        $backend = new Tinebase_OpenId_Backend_Association();
        
        $backend->delete($handle);
        
        return true;
    }

    /**
     * Register new user with given $id and $password
     * Returns true in case of success and false if user with given $id already
     * exists
     *
     * @param string $id user identity URL
     * @param string $password encoded user password
     * @return bool
     */
    public function addUser($id, $password)
    {
        // we don't allow to register over OpenId currently
        return false;
    }
    
    /**
     * Returns true if user with given $id exists and false otherwise
     *
     * @param string $id user identity URL
     * @return bool
     */
    public function hasUser($id)
    {
        try {
            $userBackend = Tinebase_User::getInstance()->getUserByLoginName($id);
        } catch(Tinebase_Exception_NotFound $e) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify if user with given $id exists and has specified $password
     *
     * @param string $id user identity URL
     * @param string $password user password
     * @return bool
     */
    public function checkUser($id, $password)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($id, $password);
        
        if ($authResult->isValid()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Removes information about specified user
     *
     * @param string $id user identity URL
     * @return bool
     */
    public function delUser($id)
    {
        // you can't delete user over OpenId currently
        return false;
    }
    
    /**
     * Returns array of all trusted/untrusted sites for given user identified
     * by $id
     *
     * @param string $id user identity URL
     * @return array
     */
    public function getTrustedSites($id)
    {
        $backend = new Tinebase_OpenId_Backend_TrustedSite();
        
        $trustedSites = $backend->getMultipleByProperty($id, 'user_identity');

        if(count($sites) == 0) {
            return false;
        }
        
        $result = array();
        
        foreach($trustedSites as $trustedSite) {
            $result[$trustedSite->site] = unserialize($trustedSite->trusted);
        }
        
        return $result;
        
    }

    /**
     * Stores information about trusted/untrusted site for given user
     *
     * @param string $id user identity URL
     * @param string $site site URL
     * @param mixed $trusted trust data from extension or just a boolean value
     * @return bool
     */
    public function addSite($id, $site, $trusted)
    {
        $backend = new Tinebase_OpenId_Backend_TrustedSite();
        
        if($trusted !== NULL) {
            // add site
            $newSite = new Tinebase_Model_OpenId_TrustedSite(array(
                'id'            => Tinebase_Model_OpenId_TrustedSite::generateUID(),
                'user_identity' => $id,
                'site'          => $site,
                'trusted'       => serialize($trusted)
            ));
            
            $backend->create($newSite);
        } else {
            // remove site
            $filter = new Tinebase_Model_OpenId_TrustedSitesFilter(array(
                array('field' => 'user_identity', 'operator' => 'equals', 'value' => $id),
                array('field' => 'site',          'operator' => 'equals', 'value' => $site)
            ));
            $sitesToRemove = $backend->search($filter, null, true);
            
            foreach($sitesToRemove as $siteToRemove) {
                $backend->delete($siteToRemove->getId());
            }
        }
        
        return true;        
    }
    
}
