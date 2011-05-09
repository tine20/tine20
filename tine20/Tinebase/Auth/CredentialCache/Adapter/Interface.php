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
interface Tinebase_Auth_CredentialCache_Adapter_Interface
{
    /**
     * setCache() - persists cache
     *
     * @param Tinebase_Model_CredentialCache $_cache
     */
    public function setCache(Tinebase_Model_CredentialCache $_cache);
    
    /**
     * getCache() - get the credential cache
     *
     * @return NULL|Tinebase_Model_CredentialCache
     */
    public function getCache();

    /**
     * resetCache() - resets the cache
     */
    public function resetCache();

    /**
     * getDefaultKey() - get default cache key
     * 
     * @return string
     */
    public function getDefaultKey();
    
    /**
     * getDefaultId() - get default cache id
     * 
     * @return string
     */
    public function getDefaultId();
}
