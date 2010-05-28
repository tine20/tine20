<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
     * authenticate() - defined by Zend_Auth_Adapter_Interface.
     *
     * @throws Zend_Auth_Adapter_Exception if answering the authentication query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to authenticate '. $this->getUsername());
        
        $result = parent::authenticate();
        
        if($result->isValid()) {
            // username and password are correct, let's do some additional tests            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Authentication of '. $this->getUsername() . ' succeeded');
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Authentication of '. $this->getUsername() . ' failed');
        }
        
        return $result;
    }
    
    protected function _getAuthResult($_code)
    {
        switch($_code) {
            case Zend_Auth_Result::SUCCESS:
                return new Zend_Auth_Result(
                    $_code, 
                    $this->_identity, 
                    array('Ldap.php Authentication successful.'));
                break;
            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                return new Zend_Auth_Result(
                    $_code, 
                    $this->_identity, 
                    array('The supplied password is invalid.'));
                break;
            case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
                return new Zend_Auth_Result(
                    $_code, 
                    $this->_identity, 
                    array('More than one account found.'));
                break;
            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                return new Zend_Auth_Result(
                    $_code, 
                    $this->_identity, 
                    array('No account' . $this->_identity . ' found.'));
                break;
        }
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