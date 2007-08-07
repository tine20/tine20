<?php
/**
 * LDAP authentication backend
 * 
 * @author Lars Kneschke <l.kneschke@metaways.de>
 * @package Egwbase
 * @todo implement LDAP authentication logic
 *
 */
class Egwbase_Auth_Ldap implements Zend_Auth_Adapter_Interface 
{
    /**
     * Enter description here...
     *
     * @return void
     */
    public function __construct()
    {
        #$dbOptions = Zend_Registry::get('dbConfig');
    }
    
    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        // ...
    }
    
    /**
     * setIdentity() - set the value to be used as the identity
     *
     * @param  string $value
     * @return void
     */
    public function setIdentity($value)
    {
        $this->_identity = $value;
    }

    /**
     * setCredential() - set the credential value to be used
     *
     * @param  string $credential
     * @return void
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;
    }

}