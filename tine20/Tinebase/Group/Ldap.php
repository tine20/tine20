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
class Tinebase_Group_Ldap extends Tinebase_Group_Abstract implements Tinebase_Group_Interface_SyncAble
{
    const PLUGIN_SAMBA = 'Tinebase_Group_LdapPlugin_Samba';
    
    /**
     * the ldap backend
     *
     * @var Tinebase_Ldap
     */
    protected $_ldap;
    
    /**
     * the sql group backend
     * 
     * @var Tinebase_Group_Sql
     */
    protected $_sql;
    
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
     * return all groups an account is member of
     *
     * @param mixed $_accountId the account as integer or Tinebase_Model_User
     * @return array
     */
    public function getGroupMemberships($_accountId)
    {
        return $this->_sql->getGroupMemberships($_accountId);        
    }
    
    /**
     * get list of groupmembers 
     *
     * @param   string $_groupId
     * @return  array with account ids
     */
    public function getGroupMembers($_groupId)
    {
        return $this->_sql->getGroupMembers($_groupId);
    }

    /**
     * get group by name
     *
     * @param   string $_name
     * @return  Tinebase_Model_Group
     */
    public function getGroupByName($_name)
    {        
        return $this->_sql->getGroupByName($_name);
    }
    
    /**
     * get group by id
     *
     * @param string $_groupId the group id
     * @return Tinebase_Model_Group
     */
    public function getGroupById($_groupId)
    {   
        return $this->_sql->getGroupById($_groupId);
    }

    /**
     * get group by id directly from ldap
     * 
     * @param $_groupId
     * @return Tinebase_Model_Group
     */
    public function getLdapGroupById($_groupId)
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
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Group
     */
    public function getGroups($_filter = NULL, $_sort = 'name', $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        return $this->_sql->getGroups($_filter, $_sort, $_dir, $_start, $_limit);        
    }
    
    /**
     * Get multiple groups
     *
     * @param  string|array $_ids Ids
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids) 
    {
        return $this->_sql->getMultiple($_ids);
    }
    
    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param string $_groupId
     * @param array $_groupMembers array of ids
     * @return unknown
     */
    public function setGroupMembers($_groupId, $_groupMembers) 
    {
        $this->setLdapGroupMembers($_groupId, $_groupMembers);
        
        $this->_sql->setGroupMembers($_groupId, $_groupMembers);
    }
    
    /**
     * replace all current groupmembers with the new groupmembers list in ldap only
     *
     * @param string $_groupId
     * @param array $_groupMembers array of ids
     * @return unknown
     */
    public function setLdapGroupMembers($_groupId, $_groupMembers) 
    {
        $metaData = $this->_getMetaData($_groupId);
        $membersMetaDatas = $this->_getAccountsMetaData((array)$_groupMembers);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $group data: ' . print_r($metaData, true));
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $memebers: ' . print_r($membersMetaDatas, true));
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
    }
    
    /**
     * add a new groupmember to the group
     *
     * @param string $_groupId
     * @param mixed $_accountId string or user object
     * @return void
     */
    public function addGroupMember($_groupId, $_accountId) 
    {
        $userId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $memberships = $this->getGroupMemberships($_accountId);
        if (in_array($userId, $memberships)) {
             Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skip adding group member, as $userId is already in group $groupId");
             return;
        }
        
        $groupDn = $this->_getDn($_groupId);
        $ldapData = array();
        
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
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
        
        $this->_sql->addGroupMember($_groupId, $_accountId);
    }

    /**
     * remove one member from the group
     *
     * @param string $_groupId
     * @param mixed $_accountId
     * @return void
     */
    public function removeGroupMember($_groupId, $_accountId) 
    {
        $userId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $memberships = $this->getGroupMemberships($_accountId);
        if (!in_array($groupId, $memberships)) {
             Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skipp removing group member, as $userId is not in group $groupId " . print_r($memberships, true));
             return;
        }
        
        $groupDn = $this->_getDn($_groupId);
        
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
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
            
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $groupDn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->deleteProperty($groupDn, $ldapData);
        
        $this->_sql->removeGroupMember($_groupId, $_accountId);
    }
        
    /**
     * create a new group
     *
     * @param Tinebase_Model_Group $_group
     * @return Tinebase_Model_Group
     */
    public function addGroup(Tinebase_Model_Group $_group) 
    {
        $ldapGroup = $this->addLdapGroup($_group);
        
        $_group->id = $ldapGroup->getId();
        
        // add group to sql backend too
        $group = $this->_sql->addGroup($_group);
        
        return $group;
    }
    
    /**
     * create a new group
     *
     * @param Tinebase_Model_Group $_group
     * @return Tinebase_Model_Group
     */
    public function addLdapGroup(Tinebase_Model_Group $_group) 
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        $this->_ldap->add($dn, $ldapData);
        
        $groupId = $this->_ldap->getEntry($dn, array($this->_groupUUIDAttribute));
        
        $groupId = $groupId[strtolower($this->_groupUUIDAttribute)][0];
        
        $group = $this->getLdapGroupById($groupId);
                
        return $group;
    }
    
    /**
     * updates an existing group
     *
     * @param Tinebase_Model_Group $_group
     * @return Tinebase_Model_Group
     */
    public function updateGroup(Tinebase_Model_Group $_group) 
    {
        // update group in ldap backend
        $group = $this->updateLdapGroup($_group);
        
        // update group in sql backend too
        $group = $this->_sql->updateGroup($group);
        
        return $group;
    }
    
    /**
     * updates an existing group in ldap only
     *
     * @param Tinebase_Model_Group $_group
     * @return Tinebase_Model_Group
     */
    public function updateLdapGroup(Tinebase_Model_Group $_group) 
    {
        $dn = $this->_getDn($_group->getId());
        
        $ldapData = array(
            'cn'          => $_group->name,
            'description' => $_group->description,
        );
        
        foreach ($this->_plugins as $plugin) {
            $plugin->inspectUpdateGroup($_group, $ldapData);
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        $this->_ldap->update($dn, $ldapData);
        
        $group = $this->getLdapGroupById($_group);

        return $group;
    }
    
    /**
     * delete one or more groups
     *
     * @param mixed $_groupId
     * @return void
     */
    public function deleteGroups($_groupId) 
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
            // delete group in sql first(foreign keys)
            $this->_sql->deleteGroups($groupId);
            
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
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $filter: ' . $filter);
        
        
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
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  Existing gidnumbers " . print_r($allGidNumbers, true));
        
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
     * import groups from ldap to sql 
     * 
     * @return void
     */
    public function importGroups()
    {
        $filter = Zend_Ldap_Filter::equals(
            'objectclass', 'posixgroup'
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('cn', 'description', $this->_groupUUIDAttribute)
        );
        
        foreach ($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => $group[strtolower($this->_groupUUIDAttribute)][0],
                'name'          => $group['cn'][0],
                'description'   => isset($group['description'][0]) ? $group['description'][0] : null
            )); 

            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' add group: ' . print_r($groupObject->toArray(), TRUE));
            try {
                $this->_sql->addGroup($groupObject, $group[strtolower($this->_groupUUIDAttribute)][0]);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .' Could not add group: ' . $groupObject->name . ' Error message: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * import groupmembers from ldap to sql
     * 
     * @return void
     */
    public function importGroupMembers()
    {
        $groups = $this->getGroups();
        
        foreach ($groups as $group) {
            $groupId = Tinebase_Model_Group::convertGroupIdToInt($group);     

            $filter = Zend_Ldap_Filter::equals(
                $this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)
            );
            
            $groupMembers = $this->_ldap->search(
                $filter, 
                $this->_options['groupsDn'], 
                $this->_groupSearchScope, 
                array('member', 'memberuid')
            )->getFirst();

            if (count($groupMembers) == 0) {
                // group not found => nothing to import
                continue;
            }

            if (isset($groupMembers['member'])) {
                unset($groupMembers['member']['count']);
                foreach ($groupMembers['member'] as $dn) {
                    try {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' read ldap data for dn: ' . $dn);
                        $accountData = $this->_ldap->getEntry($dn, array('uidnumber'));
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' ldap data returned: ' . print_r($accountData, true));
                        $memberId = Tinebase_User::getInstance()->resolveUIdNumberToUUId($accountData['uidnumber'][0]);
                        
                        // add account to sql backend
                        $this->_sql->addGroupMember($groupId, $memberId);
                    } catch (Exception $e) {
                        // ignore ldap errors
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .' user not found: ' . $e->getMessage());
                    }
                }
            } elseif (isset($groupMembers['memberuid'])) {
                unset($groupMembers['memberuid']['count']);
                foreach ((array)$groupMembers['memberuid'] as $loginName) {
                    $account = Tinebase_User::getInstance()->getUserByLoginName($loginName);
                    $memberId = $account->getId();
                    
                    $this->_sql->addGroupMember($groupId, $memberId);
                }
            }
        }        
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
}