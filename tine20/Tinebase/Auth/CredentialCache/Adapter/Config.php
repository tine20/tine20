<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * credential cache adapter (config.inc.php)
 *  
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_CredentialCache_Adapter_Config implements Tinebase_Auth_CredentialCache_Adapter_Interface
{
    /**
     * config key const
     * 
     */
    const CONFIG_KEY = 'usercredentialcache';
    
    /**
     * setCache() - persists cache
     *
     * @param  Tinebase_Model_CredentialCache $_cache
     */
    public function setCache(Tinebase_Model_CredentialCache $_cache)
    {
    }
    
    /**
     * getCache() - get the credential cache
     *
     * @return NULL|Tinebase_Model_CredentialCache 
     */
    public function getCache()
    {
        $result = NULL;
        
        $config = Tinebase_Core::getConfig();
        if ($config->{self::CONFIG_KEY}) {
            $id = $this->getDefaultId();
            if ($id !== NULL) {
                $cacheId = array(
                    'key'   => $config->{self::CONFIG_KEY},
                    'id'    => $id,
                );
                $result = new Tinebase_Model_CredentialCache($cacheId);
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No credential cache key found in config.');
        }
        
        return $result;
    }

    /**
     * resetCache() - resets the cache
     */
    public function resetCache()
    {
    }
    
    /**
     * getDefaultKey() - get default cache key
     * 
     * @return string
     */
    public function getDefaultKey()
    {
        $result = NULL;
        
        $config = Tinebase_Core::getConfig();
        if ($config->{self::CONFIG_KEY}) {
            $result = $config->{self::CONFIG_KEY};
        } else {
            throw new Tinebase_Exception_NotFound('No credential cache key found in config!');
        }
        
        return $result;
    }
    
    /**
     * getDefaultId() - get default cache id
     * - use user id as default cache id
     * 
     * @return string
     */
    public function getDefaultId()
    {
        $result = NULL;
        
        if (Tinebase_Core::isRegistered(Tinebase_Core::USER)) {
            $result = Tinebase_Core::getUser()->getId();
        }
        
        return $result;
    }
}
