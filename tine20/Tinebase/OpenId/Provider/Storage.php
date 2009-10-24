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
 * @see Zend_OpenId_Provider_Storage
 */
require_once "Zend/OpenId/Provider/Storage.php";

/**
 * External storage implemmentation using sql table
 *
 * @package     Tinebase
 * @subpackage  OpenID
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */
class Tinebase_OpenId_Provider_Storage extends Zend_OpenId_Provider_Storage
{
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
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " ");
        
        $backend = new Tinebase_OpenId_Backend_Association();
        
        $association = new Tinebase_Model_OpenId_Association(array(
            'id'        => $handle,
            'macfunc'   => $macFunc,
            'secret'    => base64_encode($secret),
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
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " ");
        
        $backend = new Tinebase_OpenId_Backend_Association();

        $result = false;
        
        try {
            $association = $backend->get($handle);
            
            $macFunc    = $association->macfunc;
            $secret     = base64_decode($association->secret);
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
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " ");
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
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " method not implemented");
        // we don't allow to register from OpenId currently
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
        // strip of everything before last /
        $localPart = substr(strrchr($id, '/'), 1);
        
        if(empty($localPart)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " invalid id: $id supplied");
            return $false;
        }
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " localPart: $localPart");
        
        try {
            Tinebase_User::getInstance()->getUserByProperty(Tinebase_User_Abstract::PROPERTY_OPENID, $localPart);
        } catch(Tinebase_Exception_NotFound $e) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " OpenID: $id not found");
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify if OpenID with given $id exists and has specified $password
     *
     * @param  string  $id        user identity URL
     * @param  string  $password  the Tine 2.0 password
     * @param  string  $username  the Tine 2.0 username
     * @return bool
     */
    public function checkUser($id, $password, $username = null)
    {
        // strip of everything before last /
        $localPart = substr(strrchr($id, '/'), 1);
        
        if(empty($localPart)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " invalid id: $id supplied");
            return $false;
        }
        
        if(empty($username)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " \$username can not be empty");
            return $false;
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " localPart: $localPart");
                
        $authResult = Tinebase_Auth::getInstance()->authenticate($username, $password);

        if ($authResult->isValid() !== true) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " authentication for $id({$account->accountLoginName}) failed");
            return false;
        }
        
        // we can't destroy the whole session, only the Zend_Auth stuff must get removed
        unset($_SESSION['Zend_Auth']);

        $account = Tinebase_User::getInstance()->getUserByLoginName($username, 'Tinebase_Model_FullUser');
        
        if($account->openid != $localPart) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " localPart: $localPart does not match for authenticated account");
            return false;
        }
        
        return true;
    }
    
    /**
     * Removes information about specified OpenID
     *
     * @param string $id user identity URL
     * @return bool
     */
    public function delUser($id)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " method not implemented");
        // we don't allow to delete accounts from OpenID
        return false;
    }
    
    /**
     * Returns array of all trusted/untrusted sites for given OpenID identified
     * by $id
     *
     * @param string $id user identity URL
     * @return array
     */
    public function getTrustedSites($id)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " ");
        
        try {
            $account = $this->_getAccountForId($id);
        } catch(Tinebase_Exception_InvalidArgument $e) {
            return false;
        } catch(Tinebase_Exception_NotFound $e) {
            return false;
        }
        
        $backend = new Tinebase_OpenId_Backend_TrustedSite();
        
        $trustedSites = $backend->getMultipleByProperty($account->accountId, 'account_id');

        #if(count($trustedSites) == 0) {
        #    return false;
        #}
        
        $result = array();
        
        foreach($trustedSites as $trustedSite) {
            $result[$trustedSite->site] = unserialize($trustedSite->trusted);
        }
        
        return $result;
        
    }

    /**
     * Stores information about trusted/untrusted site for given OpenID
     *
     * @param string $id user identity URL
     * @param string $site site URL
     * @param mixed $trusted trust data from extension or just a boolean value
     * @return bool
     */
    public function addSite($id, $site, $trusted)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " ");
        
        try {
            $account = $this->_getAccountForId($id);
        } catch(Tinebase_Exception_InvalidArgument $e) {
            return false;
        } catch(Tinebase_Exception_NotFound $e) {
            return false;
        }
                
        $backend = new Tinebase_OpenId_Backend_TrustedSite();
        
        if($trusted !== NULL) {
            // add site
            $newSite = new Tinebase_Model_OpenId_TrustedSite(array(
                'id'            => Tinebase_Model_OpenId_TrustedSite::generateUID(),
                'account_id'    => $account->accountId,
                'site'          => $site,
                'trusted'       => serialize($trusted)
            ));
            
            $backend->create($newSite);
        } else {
            // remove site
            $filter = new Tinebase_Model_OpenId_TrustedSitesFilter(array(
                array('field' => 'account_id', 'operator' => 'equals', 'value' => $account->accountId),
                array('field' => 'site',       'operator' => 'equals', 'value' => $site)
            ));
            $sitesToRemove = $backend->search($filter, null, true);
            
            foreach($sitesToRemove as $siteToRemove) {
                $backend->delete($siteToRemove->getId());
            }
        }
        
        return true;        
    }

    /**
     * retrieve account object for given OpenID
     * 
     * @param $_id
     * @return Tinebase_Model_FullUser
     */
    protected function _getAccountForId($_id)
    {
        $localPart = substr(strrchr($_id, '/'), 1);
        
        if(empty($localPart)) {
            throw new Tinebase_Exception_InvalidArgument("invalid id: $id supplied");
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " localPart: $localPart");
        
        try {
            $account = Tinebase_User::getInstance()->getUserByProperty(Tinebase_User_Abstract::PROPERTY_OPENID, $localPart, 'Tinebase_Model_FullUser');
        } catch(Tinebase_Exception_NotFound $e) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " OpenID: $_id not found");
            throw $e;
        }

        return $account;
    }
}
