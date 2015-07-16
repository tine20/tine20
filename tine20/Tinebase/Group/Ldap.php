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
class Tinebase_Group_Ldap extends Tinebase_Group_Sql implements Tinebase_Group_Interface_SyncAble
{
    const PLUGIN_SAMBA = 'Tinebase_Group_LdapPlugin_Samba';
    
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
    protected $_groupBaseFilter      = 'objectclass=posixgroup';
    
    /**
     * the basic user ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_userBaseFilter      = 'objectclass=posixaccount';
    
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
    
    protected $_isDisabledBackend    = false;
    
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options) 
    {
        parent::__construct();
        
        if(empty($_options['userUUIDAttribute'])) {
            $_options['userUUIDAttribute'] = 'entryUUID';
        }
        if(empty($_options['groupUUIDAttribute'])) {
            $_options['groupUUIDAttribute'] = 'entryUUID';
        }
        if(empty($_options['baseDn'])) {
            $_options['baseDn'] = $_options['userDn'];
        }
        if(empty($_options['userFilter'])) {
            $_options['userFilter'] = 'objectclass=posixaccount';
        }
        if(empty($_options['userSearchScope'])) {
            $_options['userSearchScope'] = Zend_Ldap::SEARCH_SCOPE_SUB;
        }
        if(empty($_options['groupFilter'])) {
            $_options['groupFilter'] = 'objectclass=posixgroup';
        }
        
        $this->_options = $_options;
        
        if ((isset($_options['readonly']) || array_key_exists('readonly', $_options))) {
            $this->_isReadOnlyBackend = (bool)$_options['readonly'];
        }
        if ((isset($_options['ldap']) || array_key_exists('ldap', $_options))) {
            $this->_ldap = $_options['ldap'];
        }
        if (isset($this->_options['requiredObjectClass'])) {
            $this->_requiredObjectClass = (array)$this->_options['requiredObjectClass'];
        }
        if (! array_key_exists('groupsDn', $this->_options) || empty($this->_options['groupsDn'])) {
            $this->_isDisabledBackend = true;
        }
        
        $this->_userUUIDAttribute  = strtolower($this->_options['userUUIDAttribute']);
        $this->_groupUUIDAttribute = strtolower($this->_options['groupUUIDAttribute']);
        $this->_baseDn             = $this->_options['baseDn'];
        $this->_userBaseFilter     = $this->_options['userFilter'];
        $this->_userSearchScope    = $this->_options['userSearchScope'];
        $this->_groupBaseFilter    = $this->_options['groupFilter'];
        
        if (isset($_options['plugins']) && is_array($_options['plugins'])) {
            foreach ($_options['plugins'] as $className) {
                $this->_plugins[$className] = new $className($this->getLdap(), $this->_options);
            }
        }
    }
    
    /**
     * get syncable group by id from sync backend
     * 
     * @param  mixed  $_groupId  the groupid
     * 
     * @return Tinebase_Model_Group
     */
    public function getGroupByIdFromSyncBackend($_groupId)
    {
        if ($this->isDisabledBackend()) {
            throw new Tinebase_Exception_UnexpectedValue('backend is disabled');
        }
        
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, $this->_encodeGroupId($groupId))
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " ldap filter: " . $filter);
        
        $groups = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('cn', 'description', $this->_groupUUIDAttribute)
        );
        
        if (count($groups) == 0) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }

        $group = $groups->getFirst();
        
        $result = new Tinebase_Model_Group(array(
            'id'            => $this->_decodeGroupId($group[$this->_groupUUIDAttribute][0]),
            'name'          => $group['cn'][0],
            'description'   => isset($group['description'][0]) ? $group['description'][0] : '' 
        ), TRUE);
        
        return $result;
    }
    
    /**
     * get list of groups from syncbackend
     *
     * @todo make filtering working. Allways returns all groups
     *
     * @param  string  $_filter
     * @param  string  $_sort
     * @param  string  $_dir
     * @param  int     $_start
     * @param  int     $_limit
     * 
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Group
     */
    public function getGroupsFromSyncBackend($_filter = NULL, $_sort = 'name', $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        if ($this->isDisabledBackend()) {
            throw new Tinebase_Exception_UnexpectedValue('backend is disabled');
        }
        
        $filter = Zend_Ldap_Filter::string($this->_groupBaseFilter);
        
        $groups = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('cn', 'description', $this->_groupUUIDAttribute)
        );
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
        
        foreach ($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => $this->_decodeGroupId($group[$this->_groupUUIDAttribute][0]),
                'name'          => $group['cn'][0],
                'description'   => isset($group['description'][0]) ? $group['description'][0] : null
            ), TRUE);

            $result->addRecord($groupObject);
        }
        
        return $result;
    }
    
    /**
     * get ldap connection handling class
     * 
     * @throws Tinebase_Exception_Backend_Ldap
     * @return Tinebase_Ldap
     */
    public function getLdap()
    {
        if (! $this->_ldap instanceof Tinebase_Ldap) {
            $this->_ldap = new Tinebase_Ldap($this->_options);
            try {
                $this->getLdap()->bind();
            } catch (Zend_Ldap_Exception $zle) {
                // @todo move this to Tinebase_Ldap?
                throw new Tinebase_Exception_Backend_Ldap('Could not bind to LDAP: ' . $zle->getMessage());
            }
        }
        
        return $this->_ldap;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Group_Interface_SyncAble::isReadOnlyBackend()
     */
    public function isReadOnlyBackend()
    {
        return $this->_isReadOnlyBackend;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Group_Interface_SyncAble::isDisabledBackend()
     */
    public function isDisabledBackend()
    {
        return $this->_isDisabledBackend;
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
        if ($this->isDisabledBackend() || $this->isReadOnlyBackend()) {
            return $_groupMembers;
        }
        
        $metaData = $this->_getMetaData($_groupId);
        
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
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $group data: ' . print_r($metaData, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $memebers: ' . print_r($membersMetaDatas, true));
        
        $groupDn = $this->_getDn($_groupId);
        
        $memberDn = array();
        $memberUid = array();
        
        foreach ($membersMetaDatas as $memberMetadata) {
            $memberDn[]  = $memberMetadata['dn'];
            $memberUid[] = $memberMetadata['uid'];
        }
        
        $ldapData = array(
            'memberuid' => $memberUid
        );
        
        if ($this->_options['useRfc2307bis']) {
            if (!empty($memberDn)) {
                $ldapData['member'] = $memberDn; // array of dns
            } else {
                $ldapData['member'] = $groupDn; // single dn
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->getLdap()->update($metaData['dn'], $ldapData);
        
        return $_groupMembers;
    }
    
    /**
     * replace all current groupmemberships of user in sync backend
     *
     * @param  mixed  $_userId
     * @param  mixed  $_groupIds
     * 
     * @return array
     */
    public function setGroupMembershipsInSyncBackend($_userId, $_groupIds)
    {
        if ($this->isDisabledBackend() || $this->isReadOnlyBackend()) {
            return $_groupIds;
        }
        
        if ($_groupIds instanceof Tinebase_Record_RecordSet) {
            $_groupIds = $_groupIds->getArrayOfIds();
        }
        
        if(count($_groupIds) === 0) {
            throw new Tinebase_Exception_InvalidArgument('user must belong to at least one group');
        }
        
        $userId = Tinebase_Model_user::convertUserIdToInt($_userId);
        
        $groupMemberships = $this->getGroupMembershipsFromSyncBackend($userId);
        
        $removeGroupMemberships = array_diff($groupMemberships, $_groupIds);
        $addGroupMemberships    = array_diff($_groupIds, $groupMemberships);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' current groupmemberships: ' . print_r($groupMemberships, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' new groupmemberships: ' . print_r($_groupIds, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' added groupmemberships: ' . print_r($addGroupMemberships, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' removed groupmemberships: ' . print_r($removeGroupMemberships, true));
        
        foreach ($addGroupMemberships as $groupId) {
            $this->addGroupMemberInSyncBackend($groupId, $userId);
        }
        
        foreach ($removeGroupMemberships as $groupId) {
            $this->removeGroupMemberInSyncBackend($groupId, $userId);
        }
        
        return $this->getGroupMembershipsFromSyncBackend($userId);
    }
    
    /**
     * add a new groupmember to group in sync backend
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId string or user object
     */
    public function addGroupMemberInSyncBackend($_groupId, $_accountId) 
    {
        if ($this->isDisabledBackend() || $this->isReadOnlyBackend()) {
            return;
        }
        
        $userId  = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $memberships = $this->getGroupMembershipsFromSyncBackend($_accountId);
        if (in_array($groupId, $memberships)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skip adding group member, as $userId is already in group $groupId");
             return;
        }
        
        $groupDn = $this->_getDn($_groupId);
        $ldapData = array();
        
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, $this->_encodeGroupId($groupId)),
            Zend_Ldap_Filter::equals('memberuid', Zend_Ldap::filterEscape($accountMetaData['uid']))
        );
        
        $groups = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('dn')
        );

        if (count($groups) == 0) {
            // need to add memberuid
            $ldapData['memberuid'] = $accountMetaData['uid'];
        }
        
        
        if ($this->_options['useRfc2307bis']) {
            $filter = Zend_Ldap_Filter::andFilter(
                Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, $this->_encodeGroupId($groupId)),
                Zend_Ldap_Filter::equals('member', Zend_Ldap::filterEscape($accountMetaData['dn']))
            );
            
            $groups = $this->getLdap()->search(
                $filter, 
                $this->_options['groupsDn'], 
                $this->_groupSearchScope, 
                array('dn')
            );
            
            if (count($groups) == 0) {
                // need to add member
                $ldapData['member'] = $accountMetaData['dn'];
            }
        }
                
        if (!empty($ldapData)) {
            $this->getLdap()->addProperty($groupDn, $ldapData);
        }
        
        if ($this->_options['useRfc2307bis']) {
            // remove groupdn if no longer needed
            $filter = Zend_Ldap_Filter::andFilter(
                Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, $this->_encodeGroupId($groupId)),
                Zend_Ldap_Filter::equals('member', Zend_Ldap::filterEscape($groupDn))
            );
            
            $groups = $this->getLdap()->search(
                $filter, 
                $this->_options['groupsDn'], 
                $this->_groupSearchScope, 
                array('dn')
            );
            
            if (count($groups) > 0) {
                $ldapData = array (
                    'member' => $groupDn
                );
                $this->getLdap()->deleteProperty($groupDn, $ldapData);
            }
        }
    }

    /**
     * remove one member from the group in sync backend
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId
     */
    public function removeGroupMemberInSyncBackend($_groupId, $_accountId) 
    {
        if ($this->isDisabledBackend() || $this->isReadOnlyBackend()) {
            return;
        }
        
        $userId  = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $memberships = $this->getGroupMemberships($_accountId);
        if (!in_array($groupId, $memberships)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skip removing group member, as $userId is not in group $groupId " . print_r($memberships, true));
             return;
        }
        
        try {
            $groupDn = $this->_getDn($_groupId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . 
                " Failed to remove groupmember $_accountId from group $_groupId: " . $tenf->getMessage()
            );
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getTraceAsString());
            return;
        }
        
        try {
            $accountMetaData = $this->_getAccountMetaData($_accountId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' user not found in sync backend: ' . $_accountId);
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
        $memberUidNumbers = $this->getGroupMembers($_groupId);
        
        $ldapData = array(
            'memberuid' => $accountMetaData['uid']
        );
        
        if (isset($this->_options['useRfc2307bis']) && $this->_options['useRfc2307bis']) {
            
            if (count($memberUidNumbers) === 1) {
                // we need to add the group dn, as the member attribute is not allowed to be empty
                $dataAdd = array(
                    'member' => $groupDn
                );
                $this->getLdap()->addProperty($groupDn, $dataAdd);
            } else {
                $ldapData['member'] = $accountMetaData['dn'];
            }
        }
            
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $groupDn);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        try {
            $this->getLdap()->deleteProperty($groupDn, $ldapData);
        } catch (Zend_Ldap_Exception $zle) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . 
                " Failed to remove groupmember {$accountMetaData['dn']} from group $groupDn: " . $zle->getMessage()
            );
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zle->getTraceAsString());
        }
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
        if ($this->isDisabledBackend() || $this->isReadOnlyBackend()) {
            return $_group;
        }
        
        $dn = $this->_generateDn($_group);
        $objectClass = array(
            'top',
            'posixGroup'
        );
        
        $gidNumber = $this->_generateGidNumber();
        $ldapData = array(
            'objectclass' => $objectClass,
            'gidnumber'   => $gidNumber,
            'cn'          => $_group->name,
            'description' => $_group->description,
        );
        
        if (isset($this->_options['useRfc2307bis']) && $this->_options['useRfc2307bis'] == true) {
            $ldapData['objectclass'][] = 'groupOfNames';
            // the member attribute can not be emtpy, seems to be common praxis 
            // to set the member attribute to the group dn itself for empty groups
            $ldapData['member']        = $dn;
        }
        
        foreach ($this->_plugins as $plugin) {
            $plugin->inspectAddGroup($_group, $ldapData);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        $this->getLdap()->add($dn, $ldapData);
        
        $groupId = $this->getLdap()->getEntry($dn, array($this->_groupUUIDAttribute));
        
        $groupId = $this->_decodeGroupId($groupId[$this->_groupUUIDAttribute][0]);
        
        $group = $this->getGroupByIdFromSyncBackend($groupId);
                
        return $group;
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
            $newDn = "cn={$ldapData['cn']},{$this->_options['groupsDn']}";
            $this->_ldap->rename($dn, $newDn);
        }
        
        $group = $this->getGroupByIdFromSyncBackend($_group);

        return $group;
    }
    
    /**
     * delete one or more groups in sync backend
     *
     * @param  mixed   $_groupId
     */
    public function deleteGroupsInSyncBackend($_groupId) 
    {
        if ($this->isDisabledBackend() || $this->isReadOnlyBackend()) {
            return;
        }
        
        $groupIds = array();
        
        if (is_array($_groupId) or $_groupId instanceof Tinebase_Record_RecordSet) {
            foreach ($_groupId as $groupId) {
                $groupIds[] = Tinebase_Model_Group::convertGroupIdToInt($groupId);
            }
        } else {
            $groupIds[] = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        }
        
        foreach ($groupIds as $groupId) {
            try {
                $dn = $this->_getDn($groupId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // group does not exist in LDAP backend any more
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Did not found group with id ' . $groupId . ' in LDAP. Delete skipped!');
                continue;
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Deleting group ' . $dn . ' from LDAP');
            $this->getLdap()->delete($dn);
        }
    }
    
    /**
     * get dn of an existing group
     *
     * @param  string $_groupId
     * @return string 
     */
    protected function _getDn($_groupId)
    {
        $metaData = $this->_getMetaData($_groupId);
        
        return $metaData['dn'];
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
            array('objectclass')
        );
        
        if (count($result) !== 1) {
            throw new Tinebase_Exception_NotFound("Group with id $_groupId not found.");
        }
        
        return $result->getFirst();
    }
    
    /**
     * get metatada of existing user
     *
     * @param  string  $_userId
     * @return array
     */
    protected function _getUserMetaData($_userId)
    {
        $userId = $this->_encodeAccountId(Tinebase_Model_User::convertUserIdToInt($_userId));

        $filter = Zend_Ldap_Filter::equals(
            $this->_userUUIDAttribute, $userId
        );

        $result = $this->getLdap()->search(
            $filter,
            $this->_baseDn,
            $this->_userSearchScope
        );

        if (count($result) !== 1) {
            throw new Tinebase_Exception_NotFound("user with userid $_userId not found");
        }

        return $result->getFirst();
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
            $filterArray[] = Zend_Ldap_Filter::equals($this->_userUUIDAttribute, Zend_Ldap::filterEscape($accountId));
        }
        $filter = new Zend_Ldap_Filter_Or($filterArray);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $filter: ' . $filter . ' count: ' . count($filterArray));
        
        // fetch all dns at once
        $accounts = $this->getLdap()->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array('uid', $this->_userUUIDAttribute, 'objectclass')
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
                'uid'                       => $account['uid'][0],
                $this->_userUUIDAttribute   => $account[$this->_userUUIDAttribute][0]
            );
        }

        return $result;
    }
    
    /**
     * convert binary id to plain text id
     * 
     * @param  string  $groupId
     * @return string
     */
    protected function _decodeGroupId($groupId)
    {
        return $groupId;
    }
    
    /**
     * helper function to be overwriten in subclasses
     * 
     * @param  string  $accountId
     * @return string
     */
    protected function _encodeAccountId($accountId)
    {
        return $accountId;
    }
    
    /**
     * convert binary id to plain text id
     * 
     * @param  string  $groupId
     * @return string
     */
    protected function _encodeGroupId($groupId)
    {
        return $groupId;
    }
    
    /**
     * returns a single account dn
     *
     * @param string $_accountId
     * @return string
     */
    protected function _getAccountMetaData($_accountId)
    {
        return Tinebase_Helper::array_value(0, $this->_getAccountsMetaData(array($_accountId)));
    }
    
    /**
     * generates a new dn for a group
     *
     * @param  Tinebase_Model_Group $_group
     * @return string
     */
    protected function _generateDn(Tinebase_Model_Group $_group)
    {
        $newDn = "cn={$_group->name},{$this->_options['groupsDn']}";
        
        return $newDn;
    }
    
    /**
     * generates a gidnumber
     *
     * @todo add a persistent registry which id has been generated lastly to
     *       reduce amount of groupid to be transfered
     * 
     * @return int
     */
    protected function _generateGidNumber()
    {
        $allGidNumbers = array();
        $gidNumber = null;
        
        $filter = Zend_Ldap_Filter::orFilter(
            Zend_Ldap_Filter::equals('objectclass', 'posixgroup'),
            Zend_Ldap_Filter::equals('objectclass', 'group')
        );
        
        $groups = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('gidnumber')
        );
        
        foreach ($groups as $groupData) {
            $allGidNumbers[] = $groupData['gidnumber'][0];
        }
        sort($allGidNumbers);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  Existing gidnumbers " . print_r($allGidNumbers, true));
        
        $numGroups = count($allGidNumbers);
        if ($numGroups == 0 || $allGidNumbers[$numGroups-1] < $this->_options['minGroupId']) {
            $gidNumber =  $this->_options['minGroupId'];
        } elseif ($allGidNumbers[$numGroups-1] < $this->_options['maxGroupId']) {
            $gidNumber = ++$allGidNumbers[$numGroups-1];
        } elseif (count($allGidNumbers) < ($this->_options['maxGroupId'] - $this->_options['minGroupId'])) {
            // maybe there is a gap
            for($i = $this->_options['minGroupId']; $i <= $this->_options['maxGroupId']; $i++) {
                if (!in_array($i, $allGidNumbers)) {
                    $gidNumber = $i;
                    break;
                }
            }
        }
        
        if ($gidNumber === NULL) {
            throw new Tinebase_Exception_NotImplemented('Max Group Id is reached');
        }
        
        return $gidNumber;
    }
    
    /**
     * resolve groupid(for example ldap gidnumber) to uuid(for example ldap entryuuid)
     *
     * @param   string  $_groupId
     * @return  string  the uuid for groupid
     */
    public function resolveSyncAbleGidToUUid($_groupId)
    {
        if ($this->isDisabledBackend()) {
            throw new Tinebase_Exception_UnexpectedValue('backend is disabled');
        }
        
        return $this->resolveGIdNumberToUUId($_groupId);
    }
    
    /**
     * resolve gidnumber to UUID(for example entryUUID) attribute
     * 
     * @param int $_gidNumber the gidnumber
     * @return string 
     */
    public function resolveGIdNumberToUUId($_gidNumber)
    {
        if ($this->_groupUUIDAttribute == 'gidnumber') {
            return $_gidNumber;
        }
        
        if ($this->isDisabledBackend()) {
            throw new Tinebase_Exception_UnexpectedValue('backend is disabled');
        }
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals('gidnumber', $_gidNumber)
        );
        
        $groupId = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array($this->_groupUUIDAttribute)
        )->getFirst();
        
        if ($groupId == null) {
            throw new Tinebase_Exception_NotFound('LDAP group with (gidnumber=' . $_gidNumber . ') not found');
        }
        
        return $groupId[$this->_groupUUIDAttribute][0];
    }
    
    /**
     * resolve UUID(for example entryUUID) to gidnumber
     * 
     * @param string $_uuid
     * @return string
     */
    public function resolveUUIdToGIdNumber($_uuid)
    {
        if ($this->_groupUUIDAttribute == 'gidnumber') {
            return $_uuid;
        }
        
        if ($this->isDisabledBackend()) {
            throw new Tinebase_Exception_UnexpectedValue('backend is disabled');
        }
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, $this->_encodeGroupId($_uuid))
        );
        
        $groupId = $this->getLdap()->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('gidnumber')
        )->getFirst();
        
        return $groupId['gidnumber'][0];
    }
    
    /**
     * get groupmemberships of user from sync backend
     * 
     * @param   Tinebase_Model_User|string  $_userId
     * @return  array  list of group ids
     */
    public function getGroupMembershipsFromSyncBackend($_userId)
    {
        if (!$this->isDisabledBackend()) {
            $metaData = $this->_getUserMetaData($_userId);
            
            $filter = Zend_Ldap_Filter::andFilter(
                Zend_Ldap_Filter::string($this->_groupBaseFilter),
                Zend_Ldap_Filter::orFilter(
                    Zend_Ldap_Filter::equals('memberuid', Zend_Ldap::filterEscape($metaData['uid'][0])),
                    Zend_Ldap_Filter::equals('member',    Zend_Ldap::filterEscape($metaData['dn']))
                )
            );
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' ldap search filter: ' . $filter);
            
            $groups = $this->getLdap()->search(
                $filter, 
                $this->_options['groupsDn'], 
                $this->_groupSearchScope, 
                array('cn', 'description', $this->_groupUUIDAttribute)
            );
            
            $memberships = array();
            
            foreach ($groups as $group) {
                $memberships[] = $group[$this->_groupUUIDAttribute][0];
            }
        } else {
            $memberships = $this->getGroupMemberships($_userId);
            
            if (empty($memberships)) {
                $memberships[] = Tinebase_Group::getInstance()->getDefaultGroup()->getId();
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' group memberships: ' . print_r($memberships, TRUE));
        
        return $memberships;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Group/Interface/Syncable::mergeMissingProperties
     */
    public static function mergeMissingProperties($syncGroup, $sqlGroup)
    {
        // @TODO see ldap schema, email might be an attribute
        foreach (array('list_id', 'email', 'visibility') as $property) {
            $syncGroup->{$property} = $sqlGroup->{$property};
        }
    }
}
