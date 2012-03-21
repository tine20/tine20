<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * LDAP authentication backend
 * 
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_Ldap extends Zend_Auth_Adapter_Ldap implements Tinebase_Auth_Interface
{
    /**
     * Constructor
     *
     * @param  array  $options  An array of arrays of Zend_Ldap options
     * @return void
     */
    public function __construct(array $options = array(),  $username = null, $password = null)
    {
        $this->setOptions($options);
        if ($username !== null) {
            $this->setIdentity($username);
        }
        if ($password !== null) {
            $this->setCredential($password);
        }
    }
    
    /**
     * Returns the LDAP Object
     *
     * @return Tinebase_Ldap The Tinebase_Ldap object used to authenticate the credentials
     */
    public function getLdap()
    {
        if ($this->_ldap === null) {
            /**
             * @see Tinebase_Ldap
             */
            $this->_ldap = new Tinebase_Ldap($this->getOptions());
        }
        return $this->_ldap;
    }
    
    /**
     * set loginname
     *
     * @param string $_identity
     * @return Tinebase_Auth_Ldap
     */
    public function setIdentity($_identity)
    {
        parent::setUsername($_identity);
        return $this;
    }
    
    /**
     * set password
     *
     * @param string $_credential
     * @return Tinebase_Auth_Ldap
     */
    public function setCredential($_credential)
    {
        parent::setPassword($_credential);
        return $this;
    }    
}
