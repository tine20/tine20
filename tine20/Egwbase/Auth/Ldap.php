<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * LDAP authentication backend
 * 
 * @package     Egwbase
 * @subpackage  Auth
 */
class Egwbase_Auth_Ldap implements Zend_Auth_Adapter_Interface 
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
     * $_identity - Identity value
     *
     * @var string
     */
    protected $_identity = null;

    /**
     * $_credential - Credential values
     *
     * @var string
     */
    protected $_credential = null;

    /**
     * $_resultRow - Results of database authentication query
     *
     * @var array
     */
    protected $_resultRow = null;
    
    /**
     * the ldap host to connect to
     *
     * @var string
     */
    protected $_host;
    
    /**
     * the adminDN to search the ldap tree
     *
     * @var string
     */
    protected $_adminDN;
    
    /**
     * the password for the adminDN
     *
     * @var string
     */
    protected $_adminPassword;
    
    /**
     * where to start searching for accounts
     *
     * @var string
     */
    protected $_searchDN;

    /**
     * the contructor
     *
     * @param Zend_Config $_options
     */
    public function __construct(Zend_Config $_options)
    {
        $this->_host            = $_options->get('host', 'localhost');
        $this->_adminDN         = $_options->get('admindn');
        $this->_adminPassword   = $_options->get('adminpassword');
        $this->_searchDN        = $_options->get('searchdn');
    }
    
    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (empty($this->_identity)) {
            throw new Zend_Auth_Adapter_Exception('identity can not be empty');
        } 
        if (empty($this->_credential)) {
            throw new Zend_Auth_Adapter_Exception('credential can not be empty');
        }        
        
        Zend_Registry::get('logger')->debug('Ldap.php trying to authenticate '. $this->_identity . ' against ' . $this->_host);
        
        $ldapServer = new Egwbase_Ldap_LdapServer();
        
        $ldapServer->connect($this->_host);
        
        try {
            $ldapServer->bind($this->_adminDN, $this->_adminPassword);
        } catch (Exception $e) {
            throw new Zend_Auth_Adapter_Exception('could not bind to ldap server: ' . $this->_adminDN . ' as ' . $this->_adminPassword);
        }
        
        $account = $ldapServer->fetchAll($this->_searchDN, 'uid=' . $this->_identity, $this->accountAttributes);
        
        $ldapServer->disconnect();
        
        if(count($account) < 1) {
            Zend_Registry::get('logger')->debug('Ldap.php account ' . $this->_identity . ' not found');
            $code = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $messages[] = 'No account" . $this->_identity . " found.';
            return new Zend_Auth_Result($code, $this->_identity, $messages);
        }

        if(count($account) > 1) {
            Zend_Registry::get('logger')->debug('Ldap.php multiple accounts for ' . $this->_identity . ' found');
            $code = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            $messages[] = 'More than one account found.';
            return new Zend_Auth_Result($code, $this->_identity, $messages);
        }
        
        try {
            $ldapServer->bind($account[0]['dn'], $this->_credential);
        } catch (Exception $e) {
            Zend_Registry::get('logger')->debug('Ldap.php invalid password for ' . $account[0]['dn']);
            $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            $messages[] = 'The supplied password is invalid.';
            return new Zend_Auth_Result($code, $this->_identity, $messages);
        }

        $this->_resultRow = $account[0];
        
        Zend_Registry::get('logger')->debug('authentication of '. $this->_identity . ' succeeded');
        
        $code = Zend_Auth_Result::SUCCESS;
        $messages[] = 'Ldap.php Authentication successful.';
        return new Zend_Auth_Result($code, $this->_identity, $messages);
    }
    
    /**
     * set loginname
     *
     * @param string $_identity
     * @return Egwbase_Auth_Ldap
     */
    public function setIdentity($_identity)
    {
        $this->_identity = $_identity;
        return $this;
    }
    
    /**
     * set password
     *
     * @param string $_credential
     * @return Egwbase_Auth_Ldap
     */
    public function setCredential($_credential)
    {
        $this->_credential = $_credential;
        return $this;
    }    

    /**
     * set the password for given account
     *
     * @param int $_accountId
     * @param string $_password
     * @return void
     */
    public function setPassword($_loginName, $_password)
    {
        if(empty($_loginName)) {
            throw new InvalidArgumentException('$_loginName can not be empty');
        }
    }
}