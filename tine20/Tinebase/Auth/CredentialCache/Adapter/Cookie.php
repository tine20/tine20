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
 * credential cache adapter interface
 *  
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_CredentialCache_Adapter_Cookie implements Tinebase_Auth_CredentialCache_Adapter_Interface
{
    /**
     * cookie key const
     * 
     * @var string
     */
    const COOKIE_KEY = 'usercredentialcache';
    
    /**
     * setCache() - persists cache
     *
     * @param  string $value
     */
    public function setCache(Tinebase_Model_CredentialCache $_cache)
    {
        $cacheId = $_cache->getCacheId();
        setcookie(self::COOKIE_KEY, base64_encode(Zend_Json::encode($cacheId)));
    }
    
    /**
     * getCache() - get the credential cache
     *
     * @return NULL|Tinebase_Model_CredentialCache 
     */
    public function getCache()
    {
        $result = NULL;
        if (isset($_COOKIE[self::COOKIE_KEY]) && ! empty($_COOKIE[self::COOKIE_KEY])) {
            $cacheId = Zend_Json::decode(base64_decode($_COOKIE[self::COOKIE_KEY]));
            if (is_array($cacheId)) {
                $result = new Tinebase_Model_CredentialCache($cacheId);
            }
        }
        
        return $result;
    }

    /**
     * resetCache() - resets the cache
     */
    public function resetCache()
    {
        setcookie(self::COOKIE_KEY, '', time() - 3600);
    }
}
