<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * ActiveDirectory group backend
 * 
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_ActiveDirectory extends Tinebase_Group_Abstract
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
     * return all groups an account is member of
     * - this function caches its result (with cache tag 'ldap')
     *
     * @param mixed $_accountId the account as integer or Tinebase_Model_User
     * @return array
     */
    public function getGroupMemberships($_accountId)
    {
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = convertCacheId('getLdapGroupMemberships' . (($_accountId instanceof Tinebase_Model_FullUser) ? $_accountId->getId() : $_accountId));
        $result = $cache->load($cacheId);
        
        if (!$result) {
            if($_accountId instanceof Tinebase_Model_FullUser) {
                $memberuid = $_accountId->accountLoginName;
            } else {
                $account = Tinebase_User::getInstance()->getFullUserById($_accountId);
                $memberuid = $account->accountLoginName;
            }
            
            $filter = "(&(objectclass=user)(sAMAccountName=$memberuid))";
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
            
            $groupMemberships = $this->_ldap->fetchAll($this->_options['userDn'], $filter, array('memberOf'));
            
            $memberships = array();
            
            foreach($groupMemberships as $groupMembership) {
                $group = $this->_ldap->fetch($groupMembership['memberOf'][0], 'objectClass=group', array('objectGUID'));
                $memberships[] = $group['objectGUID'][0];
            }
    
            $result = $memberships;
            
            $cache->save($result, $cacheId, array('ldap'), 240);
        }
        
        return $result;
    }
    
    /**
     * get list of groupmembers 
     *
     * @param   int $_groupId
     * @return  array with account ids
     * @throws  Tinebase_Exception_Record_NotDefined
     */
    public function getGroupMembers($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);     
        
        try {
            $groupMembers = $this->_ldap->fetch($this->_options['groupsDn'], 'objectGUID=' . $groupId, array('member'));
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }
        
        $members = array();

        if(isset($groupMembers['member'])) {
            unset($groupMembers['member']['count']);
            foreach($groupMembers['member'] as $dn) {
                try {
                    $accountData = $this->_ldap->fetchDn($dn, 'objectclass=user', array('objectGUID'));
                    $members[] = $accountData['objectGUID'][0];
                } catch (Exception $e) {
                    // ignore ldap errors
                }
            }
        }
        
        return $members;        
    }

    /**
     * get group by name
     *
     * @param   string $_name
     * @return  Tinebase_Model_Group
     * @throws  Tinebase_Exception_Record_NotDefined
     */
    public function getGroupByName($_name)
    {        
        $groupName = Zend_Ldap::filterEscape($_name);
        
        try {
            $group = $this->_ldap->fetch($this->_options['groupsDn'], 'cn=' . $groupName, array('cn','description','objectGUID'));
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }

        $result = new Tinebase_Model_Group(array(
            'id'            => $group['objectGUID'][0],
            'name'          => $group['cn'][0],
            'description'   => isset($group['description'][0]) ? $group['description'][0] : '' 
        ));
        
        return $result;
    }
    
    /**
     * get group by id
     *
     * @param string $_name
     * @return Tinebase_Model_Group
     * @throws  Tinebase_Exception_Record_NotDefined
     */
    public function getGroupById($_groupId)
    {   
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);     
        
        try {
            $group = $this->_ldap->fetch($this->_options['groupsDn'], 'objectGUID=' . $groupId, array('cn','description','objectGUID'));
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }

        $result = new Tinebase_Model_Group(array(
            'id'            => $group['objectGUID'][0],
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
        if(!empty($_filter)) {
            $searchString = "*" . Tinebase_Ldap::filterEscape($_filter) . "*";
            $filter = "(&(objectclass=group)(|(cn=$searchString)))";
        } else {
            $filter = 'objectclass=group';
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
        
        $groups = $this->_ldap->fetchAll($this->_options['groupsDn'], $filter, array('cn','description','objectGUID'), 'cn');
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
        
        foreach($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => $group['objectGUID'][0],
                'name'          => $group['cn'][0],
                'description'   => isset($group['description'][0]) ? $group['description'][0] : '' 
            ));
            
            $result->addRecord($groupObject);
        }
        
        return $result;
    }

    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param int $_groupId
     * @param array $_groupMembers array of ids
     * @return unknown
     */
    public function setGroupMembers($_groupId, $_groupMembers) 
    {
        $metaData = $this->_getMetaData($_groupId);
        $membersMetaDatas = $this->_getAccountsMetaData((array)$_groupMembers);
        
        $memberDn = $memberUid = array();
        foreach ($membersMetaDatas as $memberMetadata) {
            $memberDn[]  = $memberMetadata['dn'];
            $memberUid[] = $memberMetadata['uid'];
        }
        
        if ($this->_options['useRfc2307bis']) {
            $this->_saveRfc2307GroupMembers($_groupId, $_groupMembers);
        } else {
            $data = array('memberuid' => $memberUid);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
            
            $this->_ldap->updateProperty($metaData['dn'], $data);
        }
    }
    
    /**
     * add a new groupmember to the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    public function addGroupMember($_groupId, $_accountId) 
    {
        $dn = $this->_getDn($_groupId);
        
        $memberUidNumbers = $this->getGroupMembers($_groupId);
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        
        if (in_array($accountMetaData['uidNumber'], $memberUidNumbers)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skipp adding group member, as {$accountMetaData['uid']} is already in group $dn");
             return;
        }
        
        $data = array('memberuid' => $accountMetaData['uid']);
        
        if ($this->_options['useRfc2307bis']) {
            if (count($memberUidNumbers) > 0) {
                $data['member'] = $accountMetaData['dn'];
            } else {
                // adding first member is handled by $this->_saveRfc2307GroupMembers
                $memberUidNumbers[] = $accountMetaData['uidNumber'];
                $this->_saveRfc2307GroupMembers($_groupId, $memberUidNumbers);
                return;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
        $this->_ldap->insertProperty($dn, $data);
    }

    /**
     * remove one groupmember from the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    public function removeGroupMember($_groupId, $_accountId) 
    {
        $dn = $this->_getDn($_groupId);
        
        $memberUidNumbers = $this->getGroupMembers($_groupId);
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        
        if (! in_array($accountMetaData['uidNumber'], $memberUidNumbers)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skipp removing group member, as {$accountMetaData['uid']} is not in group $dn");
             return;
        }
        
        $data = array('memberuid' => $accountMetaData['uid']);
        
        if ($this->_options['useRfc2307bis']) {
            
            if (count($memberUidNumbers) > 1) { 
                $data['member'] = $accountMetaData['dn'];
            } else {
                // deleting last member is handled by $this->_saveRfc2307GroupMembers
                unset($memberUidNumbers[$accountMetaData['uidNumber']]);
                $this->_saveRfc2307GroupMembers($_groupId, $memberUidNumbers);
                return;
            }
        }
            
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
        $this->_ldap->deleteProperty($dn, $data);
    }
    
    /**
     * saves group members when rfc2307 schema is in use
     * 
     * @param int $_groupId
     * @param array $_groupMembers array of ids
     */
    protected function _saveRfc2307GroupMembers($_groupId, $_groupMembers)
    {
        $group = $this->getGroupById($_groupId);
        $membersMetaDatas = $this->_getAccountsMetaData((array)$_groupMembers);
        
        $memberDn = $memberUid = array();
        foreach ($membersMetaDatas as $memberMetadata) {
            $memberDn[]  = $memberMetadata['dn'];
            $memberUid[] = $memberMetadata['uid'];
        }
        
        $metaData = $this->_getMetaData($_groupId);
        
        $data = array(
            'objectclass' => 'namedObject',
            'gidnumber'   => $group->getId(),
            'cn'          => $group->name,
            'description' => $group->description,
        );
        
        if (count($_groupMembers) > 0) {
            $data['objectclass'] = 'groupOfNames';
            $data['memberuid']   = $memberUid;
            $data['member']      = $memberDn;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
        if (array_search($data['objectclass'], $metaData['objectClass']) === false) {
            // NOTE: structual object classes can't be changed
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " replacing group {$data['cn']} for structual objectclass change");
            
            $data['objectclass'] = array(
                'top',
                'posixGroup',
                $data['objectclass']
            );
            
            $this->_ldap->delete($metaData['dn']);
            $this->_ldap->insert($metaData['dn'], $data);
        } else {
            unset($data['objectclass']);
            $this->_ldap->update($metaData['dn'], $data);
        }
    }
    
    /**
     * create a new group
     *
     * @param string $_groupName
     * @return unknown
     */
    public function addGroup(Tinebase_Model_Group $_group) 
    {
        $dn = $this->_generateDn($_group);
        $objectClass = array(
            'top',
            'posixGroup'
        );
        
        // NOTE: Usage of groupOfNames and namedObject is exclusive
        if ($this->_options['useRfc2307bis']) {
            //$objectClass[] = 'groupOfNames';
            $objectClass[] = 'namedObject';
        }
        
        $gidNumber = $this->_generateGidNumber();
        $data = array(
            'objectclass' => $objectClass,
            'gidnumber'   => $gidNumber,
            'cn'          => $_group->name,
            'description' => $_group->description,
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        $this->_ldap->insert($dn, $data);
        
        return $this->getGroupById($gidNumber);
    }
    
    /**
     * updates an existing group
     *
     * @param Tinebase_Model_Group $_account
     * @return Tinebase_Model_Group
     */
    public function updateGroup(Tinebase_Model_Group $_group) 
    {
        $dn = $this->_getDn($_group->getId());
        
        $data = array(
            'cn'          => $_group->name,
            'description' => $_group->description,
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        $this->_ldap->update($dn, $data);
        
        return $this->getGroupById($_group->getId());
    }

    /**
     * remove groups
     *
     * @param mixed $_groupId
     * 
     */
    public function deleteGroups($_groupId) 
    {
        $groupIds = array();
        
        if(is_array($_groupId) or $_groupId instanceof Tinebase_Record_RecordSet) {
            foreach($_groupId as $groupId) {
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
     * get an existing dn
     *
     * @param  int         $_groupId
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
     * @param  int         $_groupId
     */
    protected function _getMetaData($_groupId)
    {
        $metaData = array();
        
        try {
            $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
            $group = $this->_ldap->fetch($this->_options['groupsDn'], 'objectGUID=' . $groupId, array('objectclass'));
            $metaData['dn'] = $group['dn'];
            
            $metaData['objectClass'] = $group['objectclass'];
            unset($metaData['objectClass']['count']);
                
        } catch (Tinebase_Exception_NotFound $e) {
            throw new Exception("group with id $groupId not found");
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($metaData, true));
        return $metaData;
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
            $filterArray[] = "(objectGUID={$accountId})";
        }
        
        // fetch all dns at once
        $filter = '(|' . implode('', $filterArray) . ')';
        $accounts = $this->_ldap->fetchAll($this->_options['userDn'], $filter, array('sAMAccountName', 'objectGUID', 'objectclass'));
        if (count($accounts) != count($_accountIds)) {
            throw new Exception("Some dn's are missing");
        }
        
        $result = array();
        foreach ($accounts as $account) {
            unset($account['objectclass']['count']);
            
            $result[] = array(
                'dn'          => $account['dn'],
                'uid'         => $account['sAMAccountName'][0],
                'uidNumber'   => $account['objectGUID'][0],
                'objectClass' => $account['objectclass'],
            );
            
        }
        
        return $result;
    }
    
    /**
     * returns a single account dn
     *
     * @param int $_accountId
     * @return string
     */
    protected function _getAccountMetaData($_accountId)
    {
        return array_value(0, $this->_getAccountsMetaData(array($_accountId)));
    }
    
    /**
     * generates a new dn
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
        foreach ($this->_ldap->fetchAll($this->_options['groupsDn'], 'objectclass=posixgroup', array('gidnumber')) as $groupData) {
            $allGidNumbers[] = $groupData['gidnumber'][0];
        }
        sort($allGidNumbers);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  Existing gidnumbers " . print_r($allGidNumbers, true));
        
        $numGroups = count($allGidNumbers);
        if ($numGroups == 0 || $allGidNumbers[$numGroups-1] < $this->_options['minGroupId']) {
            $gidNumber =  $this->_options['minGroupId'];
        } elseif ($allGidNumbers[$numGroups-1] < $this->_options['maxGroupId']) {
            $gidNumber = ++$allGidNumbers[$numGroups-1];
        } else {
            throw new Tinebase_Exception_NotImplemented('Max Group Id is reached');
        }
        
        return $gidNumber;
    }
}
