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
class Tinebase_Group_Ldap extends Tinebase_Group_Abstract
{
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
     * name of the ldap attribute which identifies a group uniquely
     * for example gidNumber, entryUUID, objectGUID
     * @var string
     */
    protected $_uuidAttribute = 'entryUUID';
            
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options) {
        $this->_options = $_options;
        
        $this->_ldap = new Tinebase_Ldap($_options);
        $this->_ldap->bind();
        
        $this->_sql = new Tinebase_Group_Sql();
    }
        
    /**
     * return all groups an account is member of
     * - this function caches its result (with cache tag 'ldap')
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
     * @param   int $_groupId
     * @return  array with account ids
     * @throws  Tinebase_Exception_Record_NotDefined
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
     * @throws  Tinebase_Exception_Record_NotDefined
     */
    public function getGroupByName($_name)
    {        
        return $this->_sql->getGroupByName($_name);
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
        return $this->_sql->getGroupById($_groupId);
    }
    
    protected function _getGroupById($_groupId)
    {   
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);     
        
        try {
            $group = $this->_ldap->fetch($this->_options['groupsDn'], $this->_uuidAttribute . '=' . $groupId, array('cn', 'description', $this->_uuidAttribute));
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }

        $result = new Tinebase_Model_Group(array(
            'id'            => $group[strtolower($this->_uuidAttribute)][0],
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
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
            
            $this->_ldap->updateProperty($metaData['dn'], $data);
        }
        
        $this->_sql->setGroupMembers($_groupId, $_groupMembers);
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
             Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skipp adding group member, as {$accountMetaData['uid']} is already in group $dn");
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
        $this->_ldap->insertProperty($dn, $data);
        
        $this->_sql->addGroupMember($_groupId, $_accountId);
    }

    /**
     * remove one groupmember from the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return void
     */
    public function removeGroupMember($_groupId, $_accountId) 
    {
        $dn = $this->_getDn($_groupId);
        
        $memberUidNumbers = $this->getGroupMembers($_groupId);
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        
        if (! in_array($accountMetaData['uidNumber'], $memberUidNumbers)) {
             Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skipp removing group member, as {$accountMetaData['uid']} is not in group $dn");
             return;
        }
        
        $data = array('memberuid' => $accountMetaData['uid']);
        
        if (isset($this->_options['useRfc2307bis']) && $this->_options['useRfc2307bis']) {
            
            if (count($memberUidNumbers) > 1) { 
                $data['member'] = $accountMetaData['dn'];
            } else {
                // deleting last member is handled by $this->_saveRfc2307GroupMembers
                unset($memberUidNumbers[$accountMetaData['uidNumber']]);
                $this->_saveRfc2307GroupMembers($_groupId, $memberUidNumbers);
                return;
            }
        }
            
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
        $this->_ldap->deleteProperty($dn, $data);
        
        $this->_sql->removeGroupMember($_groupId, $_accountId);
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
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
        if (isset($this->_options['useRfc2307bis']) && $this->_options['useRfc2307bis'] == true) {
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        $this->_ldap->insert($dn, $data);
        
        $groupId = $this->_ldap->fetch($dn, 'objectclass=*', array($this->_uuidAttribute));
        
        $groupId = $groupId[strtolower($this->_uuidAttribute)][0];
        
        $group = $this->_getGroupById($groupId);
        
        // add group to sql backend too
        $group = $this->_sql->addGroup($group);
        
        return $group;
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        $this->_ldap->update($dn, $data);
        
        $group = $this->_getGroupById($_group);
        
        // add group to sql backend too
        $group = $this->_sql->updateGroup($group);
        
        return $group;
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
        
        $this->_sql->deleteGroups($_groupId);
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
            $group = $this->_ldap->fetch($this->_options['groupsDn'], $this->_uuidAttribute . '=' . $groupId, array('objectclass'));
            $metaData['dn'] = $group['dn'];
            
            $metaData['objectClass'] = $group['objectclass'];
            unset($metaData['objectClass']['count']);
                
        } catch (Tinebase_Exception_NotFound $e) {
            throw new Exception("group with id $groupId not found");
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($metaData, true));
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
            $filterArray[] = "(uidnumber={$accountId})";
        }
        
        // fetch all dns at once
        $filter = '(|' . implode('', $filterArray) . ')';
        $accounts = $this->_ldap->fetchAll($this->_options['userDn'], $filter, array('uid', 'uidnumber', 'objectclass'));
        if (count($accounts) != count($_accountIds)) {
            throw new Exception("Some dn's are missing");
        }
        
        $result = array();
        foreach ($accounts as $account) {
            unset($account['objectclass']['count']);
            
            $result[] = array(
                'dn'          => $account['dn'],
                'uid'         => $account['uid'][0],
                'uidNumber'   => $account['uidnumber'][0],
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
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  Existing gidnumbers " . print_r($allGidNumbers, true));
        
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
    
    public function importGroups()
    {
        #if(!empty($_filter)) {
        #    $searchString = "*" . Tinebase_Ldap::filterEscape($_filter) . "*";
        #    $filter = "(&(objectclass=posixgroup)(|(cn=$searchString)))";
        #} else {
            $filter = 'objectclass=posixgroup';
        #}
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
        
        $groups = $this->_ldap->fetchAll($this->_options['groupsDn'], $filter, array('cn', 'description', $this->_uuidAttribute), 'cn');
        
        foreach($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => $group[strtolower($this->_uuidAttribute)][0],
                'name'          => $group['cn'][0],
                'description'   => isset($group['description'][0]) ? $group['description'][0] : null
            )); 

            try {
                $group = $this->_sql->getGroupById($groupObject->getId());
                $this->_sql->updateGroup($groupObject);
            } catch (Tinebase_Exception_Record_NotDefined $e) {
                $this->_sql->addGroup($groupObject);
            }
        }
    }
    
    public function importGroupMembers()
    {
        $groups = $this->getGroups();
        
        foreach($groups as $group) {
            $groupId = Tinebase_Model_Group::convertGroupIdToInt($group);     

            try {
                $groupMembers = $this->_ldap->fetch($this->_options['groupsDn'], $this->_uuidAttribute . '=' . $groupId, array('member', 'memberuid'));
            } catch (Exception $e) {
                // group not found => nothing to import
                continue;
            }
            
            if(isset($groupMembers['member'])) {
                unset($groupMembers['member']['count']);
                foreach($groupMembers['member'] as $dn) {
                    try {
                        $accountData = $this->_ldap->fetchDn($dn, 'objectclass=*', array('uidnumber'));
                        $memberId = Tinebase_User::getInstance()->resolveLdapUIdNumber($accountData['uidnumber'][0]);
                    } catch (Exception $e) {
                        // ignore ldap errors
                    }
                    $this->_sql->addGroupMember($groupId, $memberId);
                }
            } else {
                unset($groupMembers['memberuid']['count']);
                foreach((array)$groupMembers['memberuid'] as $loginName) {
                    $account = Tinebase_User::getInstance()->getUserByLoginName($loginName);
                    $memberId = $account->getId();
                    
                    $this->_sql->addGroupMember($groupId, $memberId);
                }
            }
        }        
    }
    
    public function resolveGIdNumberToUUId($_gidNumber)
    {
        if(strtolower($this->_uuidAttribute) == 'gidnumber') {
            return $_gidNumber;
        }
        
        $groupId = $this->_ldap->fetch($this->_options['groupsDn'], 'gidnumber=' . $_gidNumber, array($this->_uuidAttribute));
        
        return $groupId[strtolower($this->_uuidAttribute)][0];
    }
    
    public function resolveUUIdToGIdNumber($_uuid)
    {
        if(strtolower($this->_uuidAttribute) == 'gidnumber') {
            return $_uuid;
        }
        
        $groupId = $this->_ldap->fetch($this->_options['groupsDn'], $this->_uuidAttribute . '=' . $_uuid, array('gidnumber'));
        
        return $groupId['gidnumber'][0];
    }
}