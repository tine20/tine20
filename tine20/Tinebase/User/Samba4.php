<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * User Samba4 ldap backend
 *
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_Samba4 extends Tinebase_User_Ldap
{
    /**
     * mapping of ldap attributes to class properties
     *
     * @var array
     */
    protected $_rowNameMapping = array(
        'accountDisplayName'        => 'displayname',
        'accountFullName'           => 'cn',
        'accountFirstName'          => 'givenname',
        'accountLastName'           => 'sn',
        'accountLoginName'          => 'samaccountname',
        #'accountLastPasswordChange' => 'pwdLastSet',
        #'accountExpires'            => 'accountExpires',
        'accountPrimaryGroup'       => 'primarygroupid',
        'accountEmailAddress'       => 'mail',
        'accountHomeDirectory'      => 'homedirectory',
        #'accountLoginShell'         => 'loginshell',
        #'accountStatus'             => 'shadowinactive'
    );

    /**
     * objectclasses required by this backend
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'top',
        'user',
        'person',
        'organizationalPerson'
    );

    /**
     * the basic group ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_groupBaseFilter = 'objectclass=group';

    /**
     * the basic user ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_userBaseFilter = 'objectclass=user';

    protected $_isReadOnlyBackend = true;

    /**
     * the constructor
     *
     * @param  array  $_options  Options used in connecting, binding, etc.
     * @throws Tinebase_Exception_Backend_Ldap
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        if(empty($_options['userUUIDAttribute'])) {
            $_options['userUUIDAttribute'] = 'objectGUID';
        }
        if(empty($_options['groupUUIDAttribute'])) {
            $_options['groupUUIDAttribute'] = 'objectGUID';
        }
        if(empty($_options['baseDn'])) {
            $_options['baseDn'] = $_options['userDn'];
        }
        if(empty($_options['userFilter'])) {
            $_options['userFilter'] = 'objectclass=user';
        }
        if(empty($_options['userSearchScope'])) {
            $_options['userSearchScope'] = Zend_Ldap::SEARCH_SCOPE_SUB;
        }
        if(empty($_options['groupFilter'])) {
            $_options['groupFilter'] = 'objectclass=group';
        }

        if (isset($_options['requiredObjectClass'])) {
            $this->_requiredObjectClass = (array)$_options['requiredObjectClass'];
        }
        if (array_key_exists('readonly', $_options)) {
            $this->_isReadOnlyBackend = (bool)$_options['readonly'];
        }
        
        $this->_options = $_options;

        $this->_userUUIDAttribute  = strtolower($this->_options['userUUIDAttribute']);
        $this->_groupUUIDAttribute = strtolower($this->_options['groupUUIDAttribute']);
        $this->_baseDn             = $this->_options['baseDn'];
        $this->_userBaseFilter     = $this->_options['userFilter'];
        $this->_userSearchScope    = $this->_options['userSearchScope'];
        $this->_groupBaseFilter    = $this->_options['groupFilter'];

        $this->_rowNameMapping['accountId'] = $this->_userUUIDAttribute;
        
        try {
            $this->_ldap = new Tinebase_Ldap($this->_options);
            $this->_ldap->bind();
        } catch (Zend_Ldap_Exception $zle) {
            // @todo move this to Tinebase_Ldap?
            throw new Tinebase_Exception_Backend_Ldap('Could not bind to LDAP: ' . $zle->getMessage());
        }
        
        // get domain sid
        $domainConfig = $this->_ldap->search(
            'objectClass=domain',
            $this->_ldap->getFirstNamingContext(),
            Zend_Ldap::SEARCH_SCOPE_BASE,
            array('objectsid')
        )->getFirst();
        
        $this->_domainSidBinary = $domainConfig['objectsid'][0];
        $this->_domainSidPlain  = $this->_decodeSid($domainConfig['objectsid'][0]);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Domainsid " . $this->_domainSidPlain);
    }
    
    /**
     * convert binary id to plain text id
     * 
     * @param  string  $accountId
     * @return string
     */
    protected function _decodeAccountId($accountId)
    {
        switch ($this->_userUUIDAttribute) {
            case 'objectguid':
                return Tinebase_Ldap::decodeGuid($accountId);
                break;
                
            case 'objectsid':
                return Tinebase_Ldap::decodeSid($accountId);
                break;
                
            default:
                return $accountId;
                break;
        }
        
    }
}
