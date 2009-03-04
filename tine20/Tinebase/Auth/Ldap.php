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
     * the list of attributes to fetch from ldap
     *
     * @var array
     */
    protected $accountAttributes = array(
        'uid',
        'dn',
        'givenName',
        'sn',
        'mail',
        'uidNumber',
        'gidNumber',
        'shadowExpire'
    );

    /**
     * $_resultRow - Results of database authentication query
     *
     * @var array
     */
    protected $_resultRow = null;
    
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
        Tinebase_Core::getLogger()->debug('trying to authenticate '. $this->getUsername());
        
        $result = parent::authenticate();
        
        if($result->isValid()) {
            // username and password are correct, let's do some additional tests            
            Tinebase_Core::getLogger()->debug('authentication of '. $this->getUsername() . ' succeeded');
        } else {
            Tinebase_Core::getLogger()->debug('authentication of '. $this->getUsername() . ' failed');
        }
        
        return $result;
    }
    
    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function _authenticate()
    {
        if (empty($this->_identity)) {
            throw new Zend_Auth_Adapter_Exception('identity can not be empty');
        } 
        if (empty($this->_credential)) {
            throw new Zend_Auth_Adapter_Exception('credential can not be empty');
        }        
        
        Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') trying to authenticate '. $this->_identity . ' against ' . $this->_host);
        
        $ldapServer = new Tinebase_Ldap_LdapServer($this->_host);
        
        try {
            $ldapServer->bind($this->_adminDN, $this->_adminPassword);
        } catch (Exception $e) {
            throw new Zend_Auth_Adapter_Exception('could not bind to ldap server: ' . $this->_adminDN . ' as ' . $this->_adminPassword);
        }
        
        $account = $ldapServer->fetchAll($this->_searchDN, 'uid=' . $this->_identity, $this->accountAttributes);
        
        $result = NULL;
        
        if(count($account) < 1) {
            $result = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') account ' . $this->_identity . ' not found');
        } elseif(count($account) > 1) {
            $result = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') multiple accounts for ' . $this->_identity . ' found');
        } else {
            if(!$ldapServer->bind($account[0]['dn'], $this->_credential)) {
                $result = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') invalid password for ' . $account[0]['dn']);
            }
        }
        
        $ldapServer->disconnect();
        
        if($result !== NULL) {
            return $this->_getAuthResult($result);
        }

        $this->_resultRow = $account[0];
    
        Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') authentication of '. $this->_identity . ' succeeded');
        return $this->_getAuthResult(Zend_Auth_Result::SUCCESS);
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