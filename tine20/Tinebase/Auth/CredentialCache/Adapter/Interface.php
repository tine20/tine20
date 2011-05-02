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
     * @param  string $value
     */
    public function setCache($_id);
    
    /**
     * getCache() - get the credential cache
     *
     * @return Tinebase_Model_CredentialCache 
     */
    public function getCache();

    /**
     * resetCache() - resets the cache
     *
     * @return Tinebase_Auth_CredentialCache_Adapter_Interface Provides a fluent interface
     */
    public function resetCache();
}
