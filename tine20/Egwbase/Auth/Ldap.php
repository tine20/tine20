<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html
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
class Tinebase_Auth_Ldap implements Zend_Auth_Adapter_Interface 
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
        $this->_host                = $_options->get('host');
        $this->_adminDN             = $_options->get('admindn');
        $this->_adminPassword       = $_options->get('adminpassword');
        $this->_searchDN            = $_options->get('searchdn');
        $this->_updateShadowFields  = strtolower($_options->get('updateshadowfields', 'no')) === 'yes' ? true : false;
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
        
        Zend_Registry::get('logger')->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') trying to authenticate '. $this->_identity . ' against ' . $this->_host);
        
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
            Zend_Registry::get('logger')->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') account ' . $this->_identity . ' not found');
        } elseif(count($account) > 1) {
            $result = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            Zend_Registry::get('logger')->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') multiple accounts for ' . $this->_identity . ' found');
        } else {
            if(!$ldapServer->bind($account[0]['dn'], $this->_credential)) {
                $result = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                Zend_Registry::get('logger')->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') invalid password for ' . $account[0]['dn']);
            }
        }
        
        $ldapServer->disconnect();
        
        if($result !== NULL) {
            return $this->_getAuthResult($result);
        }

        $this->_resultRow = $account[0];
    
        Zend_Registry::get('logger')->debug(__CLASS__ . '::' . __FUNCTION__ . '('. __LINE__ . ') authentication of '. $this->_identity . ' succeeded');
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
        $this->_identity = $_identity;
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