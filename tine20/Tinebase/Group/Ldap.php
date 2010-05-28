<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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

        if (isset($_options['requiredObjectClass'])) {
            $this->_requiredObjectClass = (array)$_options['requiredObjectClass'];
        }

        $this->_options = $_options;

        $this->_userUUIDAttribute  = $this->_options['userUUIDAttribute'];
        $this->_groupUUIDAttribute = $this->_options['groupUUIDAttribute'];
        $this->_baseDn             = $this->_options['baseDn'];
        $this->_userBaseFilter     = $this->_options['userFilter'];
        $this->_userSearchScope    = $this->_options['userSearchScope'];
        $this->_groupBaseFilter    = $this->_options['groupFilter'];
                
        $this->_ldap = new Tinebase_Ldap($_options);
        $this->_ldap->bind();
        
        $this->_sql = new Tinebase_Group_Sql();
        
        foreach ($_options['plugins'] as $className) {
            $this->_plugins[$className] = new $className($this->_ldap, $this->_options);
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
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);     

        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId))
        );
        
        $groups = $this->_ldap->search(
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
            'id'            => $group[strtolower($this->_groupUUIDAttribute)][0],
            'name'          => $group['cn'][0],
            'description'   => isset($group['description'][0]) ? $group['description'][0] : '' 
        ));
        
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
        $filter = Zend_Ldap_Filter::string($this->_groupBaseFilter);
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('cn', 'description', $this->_groupUUIDAttribute)
        );
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
        
        foreach ($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => $group[strtolower($this->_groupUUIDAttribute)][0],
                'name'          => $group['cn'][0],
                'description'   => isset($group['description'][0]) ? $group['description'][0] : null
            )); 

            $result->addRecord($groupObject);
        }
        
        return $result;
    }
    
    /**
     * replace all current groupmembers with the new groupmembers list in sync backend
     *
     * @param  string  $_groupId
     * @param  array   $_groupMembers array of ids
     */
    public function setGroupMembersInSyncBackend($_groupId, $_groupMembers) 
    {
        $metaData = $this->_getMetaData($_groupId);
        $membersMetaDatas = $this->_getAccountsMetaData((array)$_groupMembers);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $group data: ' . print_r($metaData, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $memebers: ' . print_r($membersMetaDatas, true));
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
                $ldapData['member'] = $memberDn; // array of dn's
            } else {
                $ldapData['member'] = $groupDn; // singöe dn
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
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
        throw new Tinebase_Exception_NotImplemented('Not yet implemented');
        
        return $this->getGroupMembershipsFromSyncBackend($_userId);
    }
    
    /**
     * add a new groupmember to group in sync backend
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId string or user object
     */
    public function addGroupMemberInSyncBackend($_groupId, $_accountId) 
    {
        $userId  = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $memberships = $this->getGroupMemberships($_accountId);
        if (in_array($userId, $memberships)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skip adding group member, as $userId is already in group $groupId");
             return;
        }
        
        $groupDn = $this->_getDn($_groupId);
        $ldapData = array();
        
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)),
            Zend_Ldap_Filter::equals('memberuid', Zend_Ldap::filterEscape($accountMetaData['uid']))
        );
        
        $groups = $this->_ldap->search(
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
                Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)),
                Zend_Ldap_Filter::equals('member', Zend_Ldap::filterEscape($accountMetaData['dn']))
            );
            
            $groups = $this->_ldap->search(
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
            $this->_ldap->addProperty($groupDn, $ldapData);
        }
        
        if ($this->_options['useRfc2307bis']) {
            // remove groupdn if no longer needed
            $filter = Zend_Ldap_Filter::andFilter(
                Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)),
                Zend_Ldap_Filter::equals('member', Zend_Ldap::filterEscape($groupDn))
            );
            
            $groups = $this->_ldap->search(
                $filter, 
                $this->_options['groupsDn'], 
                $this->_groupSearchScope, 
                array('dn')
            );
            
            if (count($groups) > 0) {
                $ldapData = array (
                    'member' => $groupDn
                );
                $this->_ldap->deleteProperty($groupDn, $ldapData);
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
        $userId  = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $memberships = $this->getGroupMemberships($_accountId);
        if (!in_array($groupId, $memberships)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skipp removing group member, as $userId is not in group $groupId " . print_r($memberships, true));
             return;
        }
        
        $groupDn = $this->_getDn($_groupId);
        
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
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
                $this->_ldap->insertProperty($groupDn, $dataAdd);
            } else {
                $ldapData['member'] = $accountMetaData['dn'];
            }
        }
            
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $groupDn);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->deleteProperty($groupDn, $ldapData);
    }
        
    
    /**
     * create a new group in sync backend
     *
     * @param  Tinebase_Model_Group  $_group
     * 
     * @return Tinebase_Model_Group
     */
    public function addGroupInSyncBackend(Tinebase_Model_Group $_group) 
    {
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        $this->_ldap->add($dn, $ldapData);
        
        $groupId = $this->_ldap->getEntry($dn, array($this->_groupUUIDAttribute));
        
        $groupId = $groupId[strtolower($this->_groupUUIDAttribute)][0];
        
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
        $dn = $this->_getDn($_group->getId());
        
        $ldapData = array(
            'cn'          => $_group->name,
            'description' => $_group->description,
        );
        
        foreach ($this->_plugins as $plugin) {
            $plugin->inspectUpdateGroup($_group, $ldapData);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        $this->_ldap->update($dn, $ldapData);
        
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
        $groupIds = array();
        
        if (is_array($_groupId) or $_groupId instanceof Tinebase_Record_RecordSet) {
            foreach ($_groupId as $groupId) {
                $groupIds[] = Tinebase_Model_Group::convertGroupIdToInt($groupId);
            }
        } else {
            $groupIds[] = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        }
        
        foreach ($groupIds as $groupId) {
            $dn = $this->_getDn($groupId);
            $this->_ldap->delete($dn);
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
     * 
     * @todo remove obsolete code
     */
    protected function _getMetaData($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)
        );
        
        $result = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('objectclass')
        );
        
        if (count($result) !== 1) {
            throw new Exception("group with id $_groupId not found");
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
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);

        $filter = Zend_Ldap_Filter::equals(
            $this->_userUUIDAttribute, Zend_Ldap::filterEscape($userId)
        );

        $result = $this->_ldap->search(
            $filter,
            $this->_baseDn,
            $this->_userSearchScope
        );

        if (count($result) !== 1) {
            throw new Exception("user with userid $_userId not found");
        }

        return $result->getFirst();
    }
    
    /**
     * returns arrays of metainfo from given accountIds
     *
     * @param array $_accountIds
     * @return array of strings
     */
    protected function _getAccountsMetaData(array $_accountIds)
    {
        $filterArray = array();
        foreach ($_accountIds as $accountId) {
            $accountId = Tinebase_Model_User::convertUserIdToInt($accountId);
            $filterArray[] = Zend_Ldap_Filter::equals($this->_userUUIDAttribute, Zend_Ldap::filterEscape($accountId));
        }
        $filter = new Zend_Ldap_Filter_Or($filterArray);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $filter: ' . $filter);
        
        // fetch all dns at once
        $accounts = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array('uid', $this->_userUUIDAttribute, 'objectclass')
        );
        
        if (count($accounts) != count($_accountIds)) {
            throw new Exception("Some dn's are missing");
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
     * returns a single account dn
     *
     * @param string $_accountId
     * @return string
     */
    protected function _getAccountMetaData($_accountId)
    {
        return array_value(0, $this->_getAccountsMetaData(array($_accountId)));
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
        
        $filter = Zend_Ldap_Filter::equals(
            'objectclass', 'posixgroup'
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('gidnumber')
        );
        
        foreach ($groups as $groupData) {
            $allGidNumbers[] = $groupData['gidnumber'][0];
        }
        sort($allGidNumbers);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  Existing gidnumbers " . print_r($allGidNumbers, true));
        
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
        if (strtolower($this->_groupUUIDAttribute) == 'gidnumber') {
            return $_gidNumber;
        }
        
        $filter = Zend_Ldap_Filter::equals(
            'gidnumber', Zend_Ldap::filterEscape($_gidNumber)
        );
        
        $groupId = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array($this->_groupUUIDAttribute)
        )->getFirst();
        
        if ($groupId == null) {
            throw new Tinebase_Exception_NotFound('LDAP group with (gidnumber=' . $_gidNumber . ') not found');
        }
        return $groupId[strtolower($this->_groupUUIDAttribute)][0];
    }
    
    /**
     * resolve UUID(for example entryUUID) to gidnumber
     * 
     * @param string $_uuid
     * @return string
     */
    public function resolveUUIdToGIdNumber($_uuid)
    {
        if (strtolower($this->_groupUUIDAttribute) == 'gidnumber') {
            return $_uuid;
        }
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_groupUUIDAttribute, Zend_Ldap::filterEscape($_uuid)
        );
        
        $groupId = $this->_ldap->search(
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
     * @param   Tinebase_Model_User  $_user
     * @return  array  list of group ids
     */
    public function getGroupMembershipsFromSyncBackend(Tinebase_Model_User $_user)
    {
        $metaData = $this->_getUserMetaData($_user);
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::orFilter(
                Zend_Ldap_Filter::equals('memberuid', Zend_Ldap::filterEscape($metaData['uid'][0])),
                Zend_Ldap_Filter::equals('member',    Zend_Ldap::filterEscape($metaData['dn']))
            )
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('cn', 'description', $this->_groupUUIDAttribute)
        );
        
        /*
        $memberships = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
        
        foreach ($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => $group[strtolower($this->_groupUUIDAttribute)][0],
                'name'          => $group['cn'][0],
                'description'   => isset($group['description'][0]) ? $group['description'][0] : null
            )); 
            
            $memberships->addRecord($groupObject);
        }
        */
        
        $memberships = array();
        
        foreach ($groups as $group) {
            $memberships[] = $group[strtolower($this->_groupUUIDAttribute)][0];
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' group memberships: ' . print_r($memberships, TRUE));
        
        return $memberships;
    }
    
}