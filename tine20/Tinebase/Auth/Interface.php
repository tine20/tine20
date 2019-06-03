<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * authentication backend interface
 *  
 * @package     Tinebase
 * @subpackage  Auth
 */
interface Tinebase_Auth_Interface extends Zend_Auth_Adapter_Interface
{
    /**
     * setIdentity() - set the value to be used as the identity
     *
     * @param  string $value
     * @return Zend_Auth_Adapter_Interface Provides a fluent interface
     */
    public function setIdentity($value);
    
    /**
     * setCredential() - set the credential value to be used
     *
     * @param  string $credential
     * @return Zend_Auth_Adapter_Interface Provides a fluent interface
     */
    public function setCredential($credential);

    /**
     * @return bool
     */
    public function supportsAuthByEmail();

    /**
     * @return self
     */
    public function getAuthByEmailBackend();
}
