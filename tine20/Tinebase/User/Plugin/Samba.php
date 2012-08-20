<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * plugin to handle sambaSAM ldap attributes
 * 
 * @package Tinebase
 * @subpackage User
 */
class Tinebase_User_Plugin_Samba  extends Tinebase_User_Plugin_LdapAbstract
{
    /**
     * mapping of ldap attributes to class properties
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'sid'              => 'sambasid', 
        'primaryGroupSID'  => 'sambaprimarygroupsid', 
        'acctFlags'        => 'sambaacctflags',
        'homeDrive'        => 'sambahomedrive',
        'homePath'         => 'sambahomepath',
        'profilePath'      => 'sambaprofilepath',
        'logonScript'      => 'sambalogonscript',    
        'logonTime'        => 'sambalogontime',
        'logoffTime'       => 'sambalogofftime',
        'kickoffTime'      => 'sambakickofftime',
        'pwdLastSet'       => 'sambapwdlastset',
        'pwdCanChange'     => 'sambapwdcanchange',
        'pwdMustChange'    => 'sambapwdmustchange',
    );
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'sambaSamAccount'
    );
    
    /**
     * the constructor
     *
     * @param  array          $options  options used in connecting, binding, etc.
     */
    public function __construct(array $_options = array()) 
    {
        parent::__construct($_options);
        
        if (empty($this->_options['sid'])) {
            throw new Exception('you need to configure the sid of the samba installation');
        }
    }
    
    /**
     * inspect set expiry date
     * 
     * @param Tinebase_DateTime  $_expiryDate  the expirydate
     * @param array      $_ldapData    the data to be written to ldap
     */
    public function inspectExpiryDate($_expiryDate, array &$_ldapData)
    {
        if ($_expiryDate instanceof Tinebase_DateTime) {
            // seconds since Jan 1, 1970
            $_ldapData['sambakickofftime'] = $_expiryDate->getTimestamp();
        } else {
            $_ldapData['sambakickofftime'] = array();
        }
    }
    
    /**
     * inspect setStatus
     * 
     * @param string  $_status    the status
     * @param array   $_ldapData  the data to be written to ldap
     */
    public function inspectStatus($_status, array &$_ldapData)
    {
        $acctFlags = (isset($_ldapData['sambaacctflags']) && !empty($_ldapData['sambaacctflags'])) ? $_ldapData['sambaacctflags'] : '[U          ]';
        $acctFlags[2] = ($_status === 'disabled') ? 'D' : ' ';
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Setting samba account flags to ' . $acctFlags);
        
        $_ldapData['sambaacctflags'] = $acctFlags;
    }
    
    /**
     * inspect set password
     * 
     * @param string   $_userId
     * @param string   $_password
     * @param boolean  $_encrypt
     * @param boolean  $_mustChange
     * @param array    $_ldapData    the data to be written to ldap
     */
    public function inspectSetPassword($_userId, $_password, $_encrypt, $_mustChange, array &$_ldapData)
    {
        if ($_encrypt !== true) {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' can not transform crypted password into nt/lm samba password. Make sure to reset password for user ' . $_userId);
        } else {
            $_ldapData['sambantpassword'] = Tinebase_User_Abstract::encryptPassword($_password, Tinebase_User_Abstract::ENCRYPT_NTPASSWORD);
            $_ldapData['sambalmpassword'] = array();
            
            if ($_mustChange === true) {
                $_ldapData['sambapwdmustchange'] = '1';
                $_ldapData['sambapwdcanchange']  = '1';
                $_ldapData['sambapwdlastset']    = array();
                
            } else if ($_mustChange === false) {
                $_ldapData['sambapwdmustchange'] = '2147483647';
                $_ldapData['sambapwdcanchange']  = '1';
                $_ldapData['sambapwdlastset']    = Tinebase_DateTime::now()->getTimestamp();
                                
            } else if ($_mustChange === null &&
                $_userId instanceof Tinebase_Model_FullUser && 
                isset($_userId->sambaSAM) && 
                isset($_userId->sambaSAM->pwdMustChange) && 
                isset($_userId->sambaSAM->pwdCanChange)) {
                    
                $_ldapData['sambapwdmustchange'] = $_userId->sambaSAM->pwdMustChange->getTimestamp();
                $_ldapData['sambapwdcanchange']  = $_userId->sambaSAM->pwdCanChange->getTimestamp();
                $_ldapData['sambapwdlastset']    = array();
                
            }
        }
    }
    
    /**
     * converts raw ldap data to sambasam object
     *
     * @param  Tinebase_Model_User  $_user
     * @param  array                $_ldapEntry
     */
    protected function _ldap2User(Tinebase_Model_User $_user, array &$_ldapEntry)
    {
        $accountArray = array();
        
        foreach ($_ldapEntry as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_propertyMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'pwdLastSet':
                    case 'logonTime':
                    case 'logoffTime':
                    case 'kickoffTime':
                    case 'pwdCanChange':
                    case 'pwdMustChange':
                        $accountArray[$keyMapping] = new Tinebase_DateTime($value[0]);
                        break;
                    default: 
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }
        
        $_user->sambaSAM = new Tinebase_Model_SAMUser($accountArray);
    }
    
    /**
     * return sid of group
     * 
     * @param string  $_groupId
     * @return string the sid of the group 
     */
    protected function _getGroupSID($_groupId)
    {
        $ldapOptions = Tinebase_User::getBackendConfiguration();
        
        $filter = Zend_Ldap_Filter::equals(
            $ldapOptions['groupUUIDAttribute'], Zend_Ldap::filterEscape($_groupId)
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $ldapOptions['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('sambasid')
        );
        
        if (count($groups) == 0) {
            throw new Tinebase_Exception_NotFound('Group not found! Filter: ' . $filter->toString());
        }
        
        $group = $groups->getFirst();
        
        if (empty($group['sambasid'][0])) {
            throw new Tinebase_Exception_NotFound('Group has no sambaSID');
        }
        
        return $group['sambasid'][0];
    }
        
    /**
     * (non-PHPdoc)
     * @see Tinebase_User_Plugin_LdapAbstract::_user2ldap()
     */
    protected function _user2ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry = array())
    {
        $this->inspectExpiryDate(isset($_user->accountExpires) ? $_user->accountExpires : null, $_ldapData);
        
        if ($_user->sambaSAM instanceof Tinebase_Model_SAMUser) {
            foreach ($_user->sambaSAM as $key => $value) {
                if (array_key_exists($key, $this->_propertyMapping)) {
                    switch ($key) {
                        case 'pwdLastSet':
                        case 'logonTime':
                        case 'logoffTime':
                        case 'kickoffTime':
                        case 'sid':
                        case 'primaryGroupSID':
                        case 'acctFlags':
                            // do nothing
                            break;
                            
                        case 'pwdCanChange':
                        case 'pwdMustChange':
                            if ($value instanceof Tinebase_DateTime) {
                                $_ldapData[$this->_propertyMapping[$key]]     = $value->getTimestamp();
                            } else {
                                $_ldapData[$this->_propertyMapping[$key]]     = array();
                            }
                            break;
                            
                        default:
                            $_ldapData[$this->_propertyMapping[$key]]     = $value;
                            break;
                    }
                }
            }
        }
        
        if (empty($_ldapEntry['sambasid'])) {
            $uidNumer = isset($_ldapData['uidnumber']) ? $_ldapData['uidnumber'] : $_ldapEntry['uidnumber'][0];
            $_ldapData['sambasid'] = $this->_options['sid'] . '-' . (2 * $uidNumer + 1000);
        }
        
        $_ldapData['sambaacctflags'] = (isset($_ldapEntry['sambaacctflags']) && !empty($_ldapEntry['sambaacctflags'])) ? $_ldapEntry['sambaacctflags'][0] : NULL;
        $this->inspectStatus($_user->accountStatus, $_ldapData);
        if ($_user->sambaSAM instanceof Tinebase_Model_SAMUser) {
            $_ldapData['sambaacctflags'][1] = !empty($_user->sambaSAM->acctFlags) ? $_user->sambaSAM->acctFlags[1] : 'U';
        }
        
        try {
            $_ldapData['sambaprimarygroupsid'] = $this->_getGroupSID($_user->accountPrimaryGroup);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $_ldapData['sambaprimarygroupsid'] = array();
        }
        
        // check if user has all required object classes. This is needed
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $_ldapData['objectclass'])) {
                // merge all required classes at once
                $_ldapData['objectclass'] = array_unique(array_merge($_ldapData['objectclass'], $this->_requiredObjectClass));
                break;
            }
        }
    }
}  
