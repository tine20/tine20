<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_SambaSAM_Ldap
 * 
 * Samba Account Managing
 * 
 * todo: what about primaryGroupSID?
 *
 * @package Tinebase
 * @subpackage Samba
 */
class Tinebase_SambaSAM_Ldap extends Tinebase_SambaSAM_Abstract
{

    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
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
     * group properties mapping
     *
     * @var array
     */
    protected $_groupPropertyNameMapping = array(
        'sid'              => 'sambasid', 
        'groupType'        => 'sambagrouptype',
    );

    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredUserObjectClass = array(
        'sambaSamAccount'
    );
    
    /**
     * objectclasses required for groups
     *
     * @var array
     */
    protected $_requiredGroupObjectClass = array(
        'sambaGroupMapping'
    );
        
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options) 
    {
        $this->_options = $_options;
        if (empty($this->_options['sid'])) {
            throw new Exception('you need to configure the sid of the samba installation');
        }
        
        $this->_ldap = new Tinebase_Ldap($_options);
        $this->_ldap->bind();
    }
    
    /**
     * get user by id
     *
     * @param   int         $_userId
     * @return  Tinebase_Model_SAMUser user
     */
    public function getUserById($_userId) 
    {
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['userUUIDAttribute'], Zend_Ldap::filterEscape($userId)
        );
        
        $accounts = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array()
        );
        
        if(count($accounts) == 0) {
            throw new Exception('User not found');
        }
        
        $user = $this->_ldap2User($accounts->getFirst());

        return $user;
    }

    /**
     * adds sam properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_SAMUser  $_samUser
     * @return Tinebase_Model_SAMUser
     */
	public function addUser($_user, Tinebase_Model_SAMUser $_samUser)
	{
        $metaData = $this->_getUserMetaData($_user);
        $ldapData = $this->_user2ldap($_samUser);
        
        $ldapData['objectclass'] = array_unique(array_merge($metaData['objectclass'], $this->_requiredUserObjectClass));
        
        // defaults
        $ldapData['sambasid']           = $this->_options['sid'] . '-' . (2 * $_user->getId() + 1000);
        $ldapData['sambaacctflags']     = !empty($ldapData['sambaacctflags'])    ? $ldapData['sambaacctflags'] : '[U          ]';
        $ldapData['sambapwdcanchange']	= isset($ldapData['sambapwdcanchange'])  ? $ldapData['sambapwdcanchange']  : 0;
        $ldapData['sambapwdmustchange']	= isset($ldapData['sambapwdmustchange']) ? $ldapData['sambapwdmustchange'] : 2147483647;

        $ldapData['sambaprimarygroupsid'] = $this->getGroupById($_user->accountPrimaryGroup)->sid;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
        
        return $this->getUserById($_user->getId());
	}
	
	/**
     * updates sam properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_SAMUser  $_samUser
     * @return Tinebase_Model_SAMUser
     */
	public function updateUser($_user, Tinebase_Model_SAMUser $_samUser)
	{
        $metaData = $this->_getUserMetaData($_user);
        $ldapData = $this->_user2ldap($_samUser);
        
        // check if user has all required object classes.
        foreach ($this->_requiredUserObjectClass as $className) {
            if (!in_array($className, $metaData['objectclass'])) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn'] . ' had no samba account. Make sure to reset the users password!');

                return $this->addUser($_user, $_samUser);
            }
        }

        $ldapData['sambaprimarygroupsid'] = $this->getGroupById($_user->accountPrimaryGroup)->sid;

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
        
        return $this->getUserById($_user->getId());
	}

	
	/**
     * delete sam user
     *
     * @param int $_userId
     */
	public function deleteUser($_userId)
	{
        // nothing to do in ldap backend
	}
    
    /**
     * delete multiple users
     *
     * @param array $_accountIds
     */
    public function deleteUsers(array $_accountIds)
    {
        // nothing to do in ldap backend
    }

    /**
     * set the password for given user 
     * 
     * @param   Tinebase_Model_FullUser $_user
     * @param   string                  $_password
     * @param   bool                    $_encrypt encrypt password
     * @param   bool                    $_mustChange
     * @return  void
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setPassword($_user, $_password, $_encrypt = TRUE, $_mustChange = FALSE)
	{
        if (! $_encrypt) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' can not transform crypted password into nt/lm samba password. Make sure to reset the users password!');
        } else {
            $metaData = $this->_getUserMetaData($_user);
            $ldapData = array(
                'sambantpassword' => $this->_generateNTPassword($_password),
                'sambalmpassword' => $this->_generateLMPassword($_password),
                'sambapwdlastset' => ($_mustChange) ? '0' : Zend_Date::now()->getTimestamp()
            ); 
            
            // @deprecated
            /*
            if ($_mustChange) {
                $ldapData['sambapwdmustchange'] = '1';
                $ldapData['sambapwdcanchange'] = '1';
            }
            */
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
            
            $this->_ldap->update($metaData['dn'], $ldapData);
        }
	}

    /**
     * update user status
     *
     * @param   int         $_userId
     * @param   string      $_status
     */
    public function setStatus($_userId, $_status)
    {
        $metaData = $this->_getUserMetaData($_userId);
        
        $acctFlags = $this->getUserById($_userId)->acctFlags;
        if (empty($currentFlags)) {
            $acctFlags = '[U          ]';
        }
        $acctFlags[2] = $_status == 'disabled' ? 'D' : ' ';
        $ldapData = array('sambaacctflags' => $acctFlags);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
    }
	
    /**
     * get group by id
     *
     * @param   int         $_groupId
     * @return  Tinebase_Model_SAMGroup group
     */
    public function getGroupById($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);

        $filter = Zend_Ldap_Filter::equals(
            $this->_options['groupUUIDAttribute'], Zend_Ldap::filterEscape($groupId)
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array()
        );
        
        if(count($groups) == 0) {
            throw new Exception('Group not found');
        }
        
        $group = $this->_ldap2Group($groups->getFirst());
        
        return $group;
    }

	/**
     * adds sam properties to a new group
     *
	 * @param  Tinebase_Model_Group    $_group
     * @return Tinebase_Model_SAMGroup
     */
	public function addGroup($_group)
	{
        $metaData = $this->_getGroupMetaData($_group);

        $ldapData = array(
            'objectclass'    => array_unique(array_merge($metaData['objectclass'], $this->_requiredGroupObjectClass)),
            'sambasid'       => $this->_options['sid'] . '-' . (2 * $_group->getId() + 1001),
            'sambagrouptype' => 2,
            'displayname'    => $_group->name
        );
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
	}


	/**
	 * updates sam properties on an updated group
	 *
	 * @param  Tinebase_Model_Group    $_group
	 * @return Tinebase_Model_SAMGroup
	 */
	public function updateGroup($_group)
	{
        $metaData = $this->_getGroupMetaData($_group);

        // check if group has all required object classes.
        foreach ($this->_requiredGroupObjectClass as $className) {
            if (! in_array($className, $metaData['objectclass'])) {
                return $this->addGroup($_group);
            }
        }
        
        $ldapData = array('displayname' => $_group->name);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
	}


	/**
	 * deletes sam groups
	 * 
	 * @param  array $_groupIds
	 * @return void
	 */
	public function deleteGroups(array $_groupIds)
	{
        // nothing to do in ldap backend
	}
    
    /**
     * get metatada of existing account
     *
     * @param  int         $_userId
     * @return array 
     * 
     * @todo remove obsolete code
     */
    protected function _getUserMetaData($_userId)
    {
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['userUUIDAttribute'], Zend_Ldap::filterEscape($userId)
        );
        
        $result = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('objectclass')
        )->getFirst();
        
        return $result;
        
        /*
        } catch (Tinebase_Exception_NotFound $enf) {
            throw new Exception("account with id $userId not found");
        }
        */
    }
    
    /**
     * returns ldap metadata of given group
     *
     * @param  int         $_groupId
     * @return array 
     * 
     * @todo remove obsolete code
     */
    protected function _getGroupMetaData($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['groupUUIDAttribute'], Zend_Ldap::filterEscape($groupId)
        );
        
        $result = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('objectclass')
        )->getFirst();
        
        return $result;
        
        /*
        } catch (Tinebase_Exception_NotFound $e) {
            throw new Exception("group with id $groupId not found");
        }
        */
    }
    
    /**
     * Fetches all accounts from backend matching the given filter
     *
     * @param string $_filter
     * @param string $_accountClass
     * @return Tinebase_Record_RecordSet
     */
/*    protected function _getUsersFromBackend($_filter, $_accountClass = 'Tinebase_Model_SAMUser')
    {
        $result = new Tinebase_Record_RecordSet($_accountClass);
        $accounts = $this->_ldap->fetchAll($this->_options['userDn'], $_filter, array_values($this->_userPropertyNameMapping));
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['userUUIDAttribute'], Zend_Ldap::filterEscape($userId)
        );
        
        $result = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array_values($this->_userPropertyNameMapping)
        )->getFirst();
        
        foreach ($accounts as $account) {
            $accountObject = $this->_ldap2User($account, $_accountClass);
            
            $result->addRecord($accountObject);
        }
        
        return $result;
    } */
    
    /**
     * Returns a user obj with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Record_Abstract
     */
    protected function _ldap2User($_userData, $_accountClass='Tinebase_Model_SAMUser')
    {
        $accountArray = array();
        
        foreach ($_userData as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_userPropertyNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'pwdLastSet':
                    case 'logonTime':
                    case 'logoffTime':
                    case 'kickoffTime':
                    case 'pwdCanChange':
                    case 'pwdMustChange':
                        $accountArray[$keyMapping] = new Zend_Date($value[0], Zend_Date::TIMESTAMP);
                        break;
                    default: 
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }
        
        $accountObject = new $_accountClass($accountArray);
        
        return $accountObject;
    }
    
    /**
     * returns array of ldap data
     *
     * @param  Tinebase_Model_SAMUser $_user
     * @return array
     */
    protected function _user2ldap(Tinebase_Model_SAMUser $_user)
    {
        $ldapData = array();
        foreach ($_user as $key => $value) {
            $ldapProperty = array_key_exists($key, $this->_userPropertyNameMapping) ? $this->_userPropertyNameMapping[$key] : false;
            if ($ldapProperty) {
                switch ($key) {
                    case 'pwdLastSet':
                    case 'logonTime':
                    case 'logoffTime':
                    case 'kickoffTime':
                    case 'pwdCanChange':
                    case 'pwdMustChange':
                        $ldapData[$ldapProperty] = $value instanceof Zend_Date ? $value->getTimestamp() : '';
                        break;
                    default:
                        $ldapData[$ldapProperty] = $value;
                        break;
                }
            }
        }
        
        return $ldapData;
    }
    
    /**
     * Returns a group obj with raw data from ldap
     *
     * @param array $_ldapData
     * @return Tinebase_Model_SAMGroup
     */
    protected function _ldap2Group($_ldapData)
    {
        $groupArray = array();
        
        foreach ($_ldapData as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_groupPropertyNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                   default: 
                        $groupArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }

        $group = new Tinebase_Model_SAMGroup($groupArray);
        
        return $group;
    }
}  
