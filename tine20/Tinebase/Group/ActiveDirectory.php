<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Group ldap backend
 * 
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_ActiveDirectory extends Tinebase_Group_Ldap
{
    /**
     * the ldap backend
     *
     * @var Tinebase_Ldap
     */
    protected $_ldap;
    
    /**
     * ldap config options
     *
     * @var array
     */
    protected $_options;
    
    /**
     * list of plugins 
     * 
     * @var array
     */
    protected $_plugins = array();
    
    /**
     * name of the ldap attribute which identifies a group uniquely
     * for example gidNumber, entryUUID, objectGUID
     * @var string
     */
    protected $_groupUUIDAttribute;
    
    /**
     * name of the ldap attribute which identifies a user uniquely
     * for example uidNumber, entryUUID, objectGUID
     * @var string
     */
    protected $_userUUIDAttribute;
    
    /**
     * the basic group ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_groupBaseFilter      = 'objectclass=group';
    
    /**
     * the basic user ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_userBaseFilter      = 'objectclass=user';
    
    /**
     * the basic user search scope
     *
     * @var integer
     */
    protected $_groupSearchScope     = Zend_Ldap::SEARCH_SCOPE_SUB;
    
    /**
     * the basic user search scope
     *
     * @var integer
     */
    protected $_userSearchScope      = Zend_Ldap::SEARCH_SCOPE_SUB;
    
    protected $_isReadOnlyBackend    = false;
    
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options) 
    {
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
        
        parent::__construct($_options);
        
        // get domain sid
        $this->_domainConfig = $this->getLdap()->search(
            'objectClass=domain',
            $this->getLdap()->getFirstNamingContext(),
            Zend_Ldap::SEARCH_SCOPE_BASE
        )->getFirst();
        
        $this->_domainSidBinary = $this->_domainConfig['objectsid'][0];
        $this->_domainSidPlain  = Tinebase_Ldap::decodeSid($this->_domainConfig['objectsid'][0]);
        
        $domanNameParts    = array();
        Zend_Ldap_Dn::explodeDn($this->_domainConfig['distinguishedname'][0], $fooBar, $domanNameParts);
        $this->_domainName = implode('.', $domanNameParts);
    }
    
    /**
     * create a new group in sync backend
     *
     * @param  Tinebase_Model_Group  $_group
     * 
     * @return Tinebase_Model_Group|NULL
     */
    public function addGroupInSyncBackend(Tinebase_Model_Group $_group) 
    {
        if ($this->_isReadOnlyBackend) {
            return NULL;
        }
        
        $dn = $this->_generateDn($_group);
        $objectClass = array(
            'top',
            'group'
        );
        
        $ldapData = array(
            'objectclass'    => $objectClass,
            'cn'             => $_group->name,
            'description'    => $_group->description,
            'samaccountname' => $_group->name,
        );
        
        if ($this->_options['useRfc2307']) {
            $ldapData['objectclass'][] = 'posixGroup';
            $ldapData['gidnumber']     = $this->_generateGidNumber();
            
            $ldapData['msSFU30NisDomain'] = array_value(0, explode('.', $this->_domainName));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) 
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' add group $dn: ' . $dn);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->getLdap()->add($dn, $ldapData);
        
        $groupId = $this->getLdap()->getEntry($dn, array($this->_groupUUIDAttribute));
        
        $groupId = $this->_decodeGroupId($groupId[$this->_groupUUIDAttribute][0]);
        
        $group = $this->getGroupByIdFromSyncBackend($groupId);
        
        return $group;
    }
    
    /**
     * add a new groupmember to group in sync backend
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId string or user object
     */
    public function addGroupMemberInSyncBackend($_groupId, $_accountId) 
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        $userId  = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $memberships = $this->getGroupMembershipsFromSyncBackend($userId);
        if (in_array($groupId, $memberships)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                 Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skip adding group member, as $userId is already in group $groupId");
             
             return;
        }
        
        $groupDn         = $this->_getDn($groupId);
        $accountMetaData = $this->_getAccountMetaData($userId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
        $ldapData = array(
            'member' => $accountMetaData['dn']
        );
        
        $this->getLdap()->addProperty($groupDn, $ldapData);
    }
    
    /**
     * get groupmemberships of user from sync backend
     * 
     * @param   Tinebase_Model_User|string  $_userId
     * @return  array  list of group ids
     */
    public function getGroupMembershipsFromSyncBackend($_userId)
    {
        $userId = $_userId instanceof Tinebase_Model_User ? $_userId->getId() : $_userId; 
        
        // find user in AD and retrieve memberOf attribute
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_userBaseFilter),
            Zend_Ldap_Filter::equals($this->_userUUIDAttribute, $this->_encodeAccountId($userId))
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' ldap search filter: ' . $filter);
        
        $memberOfs = $this->getLdap()->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array('memberof', 'primarygroupid')
        )->getFirst();
        
        if ($memberOfs === null) {
            return array();
        }
        
        // resolve primarygrouid to dn
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals('objectsid', Zend_Ldap::filterEscape($this->_domainSidPlain . '-' . $memberOfs['primarygroupid'][0]))
        );
        
        $group = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array($this->_groupUUIDAttribute)
        )->getFirst();
        
        $memberships = array(
            $this->_decodeGroupId($group[$this->_groupUUIDAttribute][0])
        );
        
        if (isset($memberOfs['memberof'])) {
            // resolve $this->_groupUUIDAttribute attribute
            $filter = new Zend_Ldap_Filter_Or(array());
            foreach ($memberOfs['memberof'] as $memberOf) {
                $filter = $filter->addFilter(Zend_Ldap_Filter::equals('distinguishedName', Zend_Ldap::filterEscape($memberOf)));
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' ldap search filter: ' . $filter);
            
            $groups = $this->getLdap()->search(
                $filter, 
                $this->_options['groupsDn'], 
                $this->_groupSearchScope, 
                array($this->_groupUUIDAttribute)
            );
            
            foreach ($groups as $group) {
                $memberships[] = $this->_decodeGroupId($group[$this->_groupUUIDAttribute][0]);
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' group memberships: ' . print_r($memberships, TRUE));
        
        return array_unique($memberships);
    }
    
    /**
     * updates an existing group in sync backend
     *
     * @param  Tinebase_Model_Group  $_group
     *
     * @return Tinebase_Model_Group
     */
    public function updateGroupInSyncBackend(Tinebase_Model_Group $_group)
    {
        if ($this->isDisabledBackend() || $this->isReadOnlyBackend()) {
            return $_group;
        }
        
        $metaData = $this->_getMetaData($_group->getId());
        $dn = $metaData['dn'];
        
        $ldapData = array(
                'cn'          => $_group->name,
                'description' => $_group->description,
                'objectclass' => $metaData['objectclass']
        );
        
        foreach ($this->_plugins as $plugin) {
            $plugin->inspectUpdateGroup($_group, $ldapData);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->getLdap()->update($dn, $ldapData);
        
        if ($metaData['cn'] != $ldapData['cn']) {
            $newDn = "cn={$ldapData['cn']},{$this->_options['baseDn']}";
            $this->_ldap->rename($dn, $newDn);
        }
        
        $group = $this->getGroupByIdFromSyncBackend($_group);
        
        return $group;
    }
    
    /**
     * remove one member from the group in sync backend
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId
     */
    public function removeGroupMemberInSyncBackend($_groupId, $_accountId) 
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        $userId  = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $memberships = $this->getGroupMemberships($_accountId);
        if (!in_array($groupId, $memberships)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) 
                 Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skip removing group member, as $userId is not in group $groupId " . print_r($memberships, true));
             return;
        }
        
        try {
            $groupDn = $this->_getDn($_groupId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) 
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " Failed to remove groupmember $_accountId from group $_groupId: " . $tenf->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getTraceAsString());
            return;
        }
        
        try {
            $accountMetaData = $this->_getAccountMetaData($_accountId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) 
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' user not found in sync backend: ' . $_accountId);
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
        $memberUidNumbers = $this->getGroupMembers($_groupId);
        
        $ldapData = array(
            'member' => $accountMetaData['dn']
        );
            
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $groupDn);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        try {
            $this->getLdap()->deleteProperty($groupDn, $ldapData);
        } catch (Zend_Ldap_Exception $zle) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) 
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " Failed to remove groupmember {$accountMetaData['dn']} from group $groupDn: " . $zle->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zle->getTraceAsString());
        }
    }
    
    /**
     * resolve gidnumber to UUID(for example entryUUID) attribute
     * 
     * @param int $_gidNumber the gidnumber
     * @return string 
     */
    public function resolveGIdNumberToUUId($rid)
    {
        $groupSid = $this->_domainSidPlain . '-' .  $rid;
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals('objectsid', $groupSid)
        );
        
        $groupId = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array($this->_groupUUIDAttribute)
        )->getFirst();
        
        if ($groupId == null) {
            throw new Tinebase_Exception_NotFound('LDAP group with (objectsid=' . $groupSid . ') not found');
        }
        
        return $this->_decodeGroupId($groupId[$this->_groupUUIDAttribute][0]);
    }
    
    /**
     * resolve UUID(for example entryUUID) to gidnumber
     * 
     * @param string $_uuid
     * @return string
     */
    public function resolveUUIdToGIdNumber($_uuid)
    {
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, $this->_encodeGroupId($_uuid))
        );
        
        $groupData = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('objectsid')
        )->getFirst();

        $sidParts = explode('-', Tinebase_Ldap::decodeSid($groupData['objectsid'][0]));
        
        return array_pop($sidParts);
    }
    
    /**
     * return gidnumber of group
     * 
     * @param string $_uuid
     * @return string
     */
    public function resolveGidNumber($_uuid)
    {
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, $this->_encodeGroupId($_uuid))
        );
        
        $groupData = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('gidnumber')
        )->getFirst();

        return $groupData['gidnumber'][0];
        
    }
    
    /**
     * replace all current groupmembers with the new groupmembers list in sync backend
     *
     * @param  string  $_groupId
     * @param  array   $_groupMembers array of ids
     * @return array with current group memberships (account ids)
     */
    public function setGroupMembersInSyncBackend($_groupId, $_groupMembers) 
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        $groupMetaData = $this->_getMetaData($_groupId);
        
        $membersMetaDatas = $this->_getAccountsMetaData((array)$_groupMembers, FALSE);
        if (count($_groupMembers) !== count($membersMetaDatas)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Removing ' . (count($_groupMembers) - count($membersMetaDatas)) . ' no longer existing group members from group ' . $_groupId);
            
            $_groupMembers = array();
            foreach ($membersMetaDatas as $account) {
                $_groupMembers[] = $account[$this->_userUUIDAttribute];
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $group data: ' . print_r($groupMetaData, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $memebers: ' . print_r($membersMetaDatas, true));
        
        $groupDn = $this->_getDn($_groupId);
        
        $memberDn = array();
        foreach ($membersMetaDatas as $memberMetadata) {
            if ($this->_domainSidPlain . '-' . $memberMetadata['primarygroupid'] == $groupMetaData['objectsid']) {
                // skip this user => is already meber because of his primary group
                continue;
            }
            $memberDn[]  = $memberMetadata['dn'];
        }
        
        $ldapData = array(
            'member' => $memberDn
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $groupMetaData['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->getLdap()->update($groupMetaData['dn'], $ldapData);
        
        return $_groupMembers;
    }
    
    /**
     * convert binary id to plain text id
     * 
     * @param  string  $groupId
     * @return string
     */
    protected function _decodeGroupId($groupId)
    {
        switch ($this->_groupUUIDAttribute) {
            case 'objectguid':
                return Tinebase_Ldap::decodeGuid($groupId);
                break;
                
            case 'objectsid':
                return Tinebase_Ldap::decodeSid($groupId);
                break;
                
            default:
                return $groupId;
                break;
        }
    }
    
    /**
     * convert plain text id to binary id
     * 
     * @param  string  $accountId
     * @return string
     */
    protected function _encodeAccountId($accountId)
    {
        switch ($this->_userUUIDAttribute) {
            case 'objectguid':
                return Tinebase_Ldap::encodeGuid($accountId);
                break;
                
            default:
                return $accountId;
                break;
        }
        
    }
    
    /**
     * convert plain text id to binary id
     * 
     * @param  string  $groupId
     * @return string
     */
    protected function _encodeGroupId($groupId)
    {
        switch ($this->_groupUUIDAttribute) {
            case 'objectguid':
                return Tinebase_Ldap::encodeGuid($groupId);
                break;
                
            default:
                return $groupId;
                break;
        }
    }
    
    /**
     * returns arrays of metainfo from given accountIds
     *
     * @param array $_accountIds
     * @param boolean $throwExceptionOnMissingAccounts
     * @return array of strings
     */
    protected function _getAccountsMetaData(array $_accountIds, $throwExceptionOnMissingAccounts = TRUE)
    {
        $filterArray = array();
        foreach ($_accountIds as $accountId) {
            $accountId = Tinebase_Model_User::convertUserIdToInt($accountId);
            $filterArray[] = Zend_Ldap_Filter::equals($this->_userUUIDAttribute, $this->_encodeAccountId($accountId));
        }
        $filter = new Zend_Ldap_Filter_Or($filterArray);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $filter: ' . $filter . ' count: ' . count($filterArray));
        
        // fetch all dns at once
        $accounts = $this->getLdap()->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array($this->_userUUIDAttribute, 'objectclass', 'primarygroupid')
        );
        
        if (count($_accountIds) != count($accounts)) {
            $wantedAccountIds    = array();
            $retrievedAccountIds = array();
            
            foreach ($_accountIds as $accountId) {
                $wantedAccountIds[] = Tinebase_Model_User::convertUserIdToInt($accountId);
            }
            foreach ($accounts as $account) {
                $retrievedAccountIds[] = $account[$this->_userUUIDAttribute][0];
            }
            
            $message = "Some dn's are missing. "  . print_r(array_diff($wantedAccountIds, $retrievedAccountIds), true);
            if ($throwExceptionOnMissingAccounts) {
                throw new Tinebase_Exception_NotFound($message);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $message);
            }
        }
        
        $result = array();
        foreach ($accounts as $account) {
            $result[] = array(
                'dn'                        => $account['dn'],
                'objectclass'               => $account['objectclass'],
                $this->_userUUIDAttribute   => $this->_decodeGroupId($account[$this->_userUUIDAttribute][0]),
                'primarygroupid'            => $account['primarygroupid'][0]
            );
        }

        return $result;
    }
    
    /**
     * returns ldap metadata of given group
     *
     * @param  string $_groupId
     * @return array
     * @throws Tinebase_Exception_NotFound
     * 
     * @todo remove obsolete code
     */
    protected function _getMetaData($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_groupUUIDAttribute, $this->_encodeGroupId($groupId)
        );
        
        $result = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('objectclass', 'objectsid')
        );
        
        if (count($result) !== 1) {
            throw new Tinebase_Exception_NotFound("Group with id $_groupId not found.");
        }
        
        $group = $result->getFirst();
        
        return array(
            'dn'          => $group['dn'],
            'objectclass' => $group['objectclass'],
            'objectsid'   => Tinebase_Ldap::decodeSid($group['objectsid'][0])
        );
    }
}
