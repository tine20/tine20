<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
         
        if (empty($_options['sid'])) {
            throw new Exception('you need to configure the sid of the samba installation');
        }
    }
    
    public function inspectSetBlocked($_accountId, $_blockedUntilDate)
    {
    	// does nothing
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
        $acctFlags = '[U          ]';
        $acctFlags[2] = $_status == 'disabled' ? 'D' : ' ';
        
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
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' can not transform crypted password into nt/lm samba password. Make sure to reset password for user ' . $_loginName);
        } else {
            $_ldapData['sambantpassword'] = Tinebase_User_Abstract::encryptPassword($_password, Tinebase_User_Abstract::ENCRYPT_NTPASSWORD);
            $_ldapData['sambalmpassword'] = Tinebase_User_Abstract::encryptPassword($_password, Tinebase_User_Abstract::ENCRYPT_LMPASSWORD);
            
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
                        $accountArray[$keyMapping] = new Tinebase_DateTime($value[0], Tinebase_DateTime::TIMESTAMP);
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
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['groupUUIDAttribute'], Zend_Ldap::filterEscape($_groupId)
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
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
     * convert objects with user data to ldap data array
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    protected function _user2ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry = array())
    {
        $this->inspectExpiryDate(isset($_user->accountExpires) ? $_user->accountExpires : null, $_ldapData);
        
        foreach ($_user->sambaSAM as $key => $value) {
            if (array_key_exists($key, $this->_propertyMapping)) {
                switch ($key) {
                    case 'pwdLastSet':
                    case 'logonTime':
                    case 'logoffTime':
                    case 'kickoffTime':
                        // do nothing
                        break;
                        
                    case 'sid':
                        if (empty($_ldapEntry['sambasid']) && isset($_ldapData['uidnumber'])) {
                            $_ldapData['sambasid'] = $this->_options[Tinebase_User_Ldap::PLUGIN_SAMBA]['sid'] . '-' . (2 * $_ldapData['uidnumber'] + 1000);
                        }
                        break;
                        
                    case 'pwdCanChange':
                    case 'pwdMustChange':
                        if ($value instanceof Tinebase_DateTime) {
                            $_ldapData[$this->_propertyMapping[$key]]     = $value->getTimestamp();
                        } else {
                            $_ldapData[$this->_propertyMapping[$key]]     = array();
                        }
                        break;
                        
                    case 'acctFlags':
                        $_ldapData[$this->_propertyMapping[$key]]     = !empty($_ldapEntry['sambaacctflags']) ? $_ldapEntry['sambaacctflags'][0] : '[U          ]';
                        $_ldapData[$this->_propertyMapping[$key]][2]  = ($_user->accountStatus != 'enabled') ? 'D' : ' ';
                        break;
                            
                    case 'primaryGroupSID':
#                        $_ldapData[$this->_propertyMapping[$key]]     = $this->_getGroupSID($_user->accountPrimaryGroup);
                        break;
                        
                    default:
                        $_ldapData[$this->_propertyMapping[$key]]     = $value;
                        break;
                }
            }
        }
        
        // check if user has all required object classes. This is needed
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $_ldapEntry['objectclass'])) {
                // merge all required classes at once
                $_ldapData['objectclass'] = array_unique(array_merge($_ldapEntry['objectclass'], $this->_requiredObjectClass));
                break;
            }
        }
    }
}  
