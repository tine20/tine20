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
     * setCache() - persists cache
     *
     * @param  string $value
     */
    public function setCache($_id)
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
        if (isset($_COOKIE['usercredentialcache']) && ! empty($_COOKIE['usercredentialcache'])) {
            $cacheId = Zend_Json::decode(base64_decode($_COOKIE['usercredentialcache']));
            if (is_array($cacheId)) {
                $result = new Tinebase_Model_CredentialCache($cacheId);
            }
        }
        
        return $result;
    }

    /**
     * resetCache() - resets the cache
     *
     * @return Tinebase_Auth_CredentialCache_Adapter_Interface Provides a fluent interface
     */
    public function resetCache()
    {
        
    }
}
