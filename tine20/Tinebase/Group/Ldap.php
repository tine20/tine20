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
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options) {
        $this->_options = $_options;
        
        $this->_userUUIDAttribute  = isset($_options['userUUIDAttribute'])  ? strtolower($_options['userUUIDAttribute'])  : 'entryuuid';
        $this->_groupUUIDAttribute = isset($_options['groupUUIDAttribute']) ? strtolower($_options['groupUUIDAttribute']) : 'entryuuid';
        $this->_userBaseFilter     = isset($_options['userFilter'])         ? $_options['userFilter']         : 'objectclass=posixaccount';
        $this->_groupBaseFilter    = isset($_options['groupFilter'])        ? $_options['groupFilter']        : 'objectclass=posixgroup';
        
        $this->_ldap = new Tinebase_Ldap($_options);
        $this->_ldap->bind();
        
        $this->_sql = new Tinebase_Group_Sql();
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
    protected function _getGroupById($_groupId)
    {   
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);     

        throw new RuntimeException('still untested');
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('cn', 'description', $this->_groupUUIDAttribute)
        );
        
        if(count($groups) == 0) {
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
        $metaData = $this->_getMetaData($_groupId);
        $membersMetaDatas = $this->_getAccountsMetaData((array)$_groupMembers);
        
        $groupDn = $this->_getDn($_groupId);
        
        $memberDn = array(); 
        $memberUid = array();
        
        foreach ($membersMetaDatas as $memberMetadata) {
            $memberDn[]  = $memberMetadata['dn'];
            $memberUid[] = $memberMetadata['uid'];
        }
        
        $data = array(
            'memberuid' => $memberUid
        );
        
        if ($this->_options['useRfc2307bis']) {
            if(!empty($memberDn)) {
                $data['member'] = $memberDn; // array of dn's
            } else {
                $data['member'] = $groupDn; // singöe dn
            }
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
        $this->_ldap->updateProperty($metaData['dn'], $data);
        
        $this->_sql->setGroupMembers($_groupId, $_groupMembers);
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
        $data = array();
        
        $accountMetaData = $this->_getAccountMetaData($_accountId);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " account meta data: " . print_r($accountMetaData, true));
        
        $accounts = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('uid', $this->_userUUIDAttribute, 'objectclass')
        );
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)),
            Zend_Ldap_Filter::equals('memberuid', Zend_Ldap::filterEscape($accountMetaData['uid']))
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('dn')
        );

        if(count($groups) == 0) {
            // need to add memberuid
            $data['memberuid'] = $accountMetaData['uid'];
        }
        
        
        if ($this->_options['useRfc2307bis']) {
            $filter = Zend_Ldap_Filter::andFilter(
                Zend_Ldap_Filter::equals($this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)),
                Zend_Ldap_Filter::equals('member', Zend_Ldap::filterEscape($accountMetaData['dn']))
            );
            
            $groups = $this->_ldap->search(
                $filter, 
                $this->_options['groupsDn'], 
                Zend_Ldap::SEARCH_SCOPE_SUB, 
                array('dn')
            );
            
            if(count($groups) == 0) {
                // need to add member
                $data['member'] = $accountMetaData['dn'];
            }
        }
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $groupDn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
        if(!empty($data)) {
            $this->_ldap->addProperty($groupDn, $data);
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
                Zend_Ldap::SEARCH_SCOPE_SUB, 
                array('dn')
            );
            
            if(count($groups) > 0) {
                $data = array (
                    'member' => $groupDn
                );
                $this->_ldap->deleteProperty($groupDn, $data);
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
        
        $data = array(
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
                $data['member'] = $accountMetaData['dn'];
            }
        }
            
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $groupDn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        
        $this->_ldap->deleteProperty($groupDn, $data);
        
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
        throw new RuntimeException('still untested');
        
        $dn = $this->_generateDn($_group);
        $objectClass = array(
            'top',
            'posixGroup'
        );
                
        $gidNumber = $this->_generateGidNumber();
        $data = array(
            'objectclass' => $objectClass,
            'gidnumber'   => $gidNumber,
            'cn'          => $_group->name,
            'description' => $_group->description,
        );
        
        if (isset($this->_options['useRfc2307bis']) && $this->_options['useRfc2307bis'] == true) {
            $data['objectclass'][] = 'groupOfNames';
            // the member attribute can not be emtpy, seems to be common praxis 
            // to set the member attribute to the group dn itself for empty groups
            $data['member']        = $dn;
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($data, true));
        $this->_ldap->add($dn, $data);
        
        $groupId = $this->_ldap->getEntry($dn, array($this->_groupUUIDAttribute));
        
        $groupId = $groupId[strtolower($this->_groupUUIDAttribute)][0];
        
        $group = $this->_getGroupById($groupId);
        
        // add group to sql backend too
        $group = $this->_sql->addGroup($group);
        
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
     * delete one or more groups
     *
     * @param mixed $_groupId
     * @return void
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
            Zend_Ldap::SEARCH_SCOPE_SUB, 
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
        
        throw new RuntimeException('still untested');
        
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
        } elseif(count($allGidNumbers) < ($this->_options['maxGroupId'] - $this->_options['minGroupId'])) {
            // maybe there is a gap
            for($i = $this->_options['minGroupId']; $i <= $this->_options['maxGroupId']; $i++) {
                if(!in_array($i, $allGidNumbers)) {
                    $gidNumber = $i;
                    break;
                }
            }
        }
        
        if($gidNumber === NULL) {
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
        throw new RuntimeException('still untested');
        
        $filter = Zend_Ldap_Filter::equals(
            'objectclass', 'posixgroup'
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('cn', 'description', $this->_groupUUIDAttribute)
        );
        
        foreach($groups as $group) {
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
        throw new RuntimeException('still untested');
        
        $groups = $this->getGroups();
        
        foreach($groups as $group) {
            $groupId = Tinebase_Model_Group::convertGroupIdToInt($group);     

            $filter = Zend_Ldap_Filter::equals(
                $this->_groupUUIDAttribute, Zend_Ldap::filterEscape($groupId)
            );
            
            $groupMembers = $this->_ldap->search(
                $filter, 
                $this->_options['groupsDn'], 
                Zend_Ldap::SEARCH_SCOPE_SUB, 
                array('member', 'memberuid')
            )->getFirst();

            if(count($groupMembers) == 0) {
                // group not found => nothing to import
                continue;
            }

            if(isset($groupMembers['member'])) {
                unset($groupMembers['member']['count']);
                foreach($groupMembers['member'] as $dn) {
                    try {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' read ldap data for dn: ' . $dn);
                        $accountData = $this->_ldap->getEntry($dn, array('uidnumber'));
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' ldap data returned: ' . print_r($accountData, true));
                        $memberId = Tinebase_User::getInstance()->resolveLdapUIdNumber($accountData['uidnumber'][0]);
                        
                        // add account to sql backend
                        $this->_sql->addGroupMember($groupId, $memberId);
                    } catch (Exception $e) {
                        // ignore ldap errors
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .' user not found: ' . $e->getMessage());
                    }
                }
            } elseif(isset($groupMembers['memberuid'])) {
                unset($groupMembers['memberuid']['count']);
                foreach((array)$groupMembers['memberuid'] as $loginName) {
                    $account = Tinebase_User::getInstance()->getUserByLoginName($loginName);
                    $memberId = $account->getId();
                    
                    $this->_sql->addGroupMember($groupId, $memberId);
                }
            }
        }        
    }
    
    /**
     * resolve gidnumber to UUID(for example entryUUID) attribute
     * 
     * @param int $_gidNumber the gidnumber
     * @return string 
     */
    public function resolveGIdNumberToUUId($_gidNumber)
    {
        if(strtolower($this->_groupUUIDAttribute) == 'gidnumber') {
            return $_gidNumber;
        }
        
        return $groupId[strtolower($this->_groupUUIDAttribute)][0];
        
        throw new RuntimeException('still untested');
        
        $filter = Zend_Ldap_Filter::equals(
            'gidnumber', Zend_Ldap::filterEscape($_gidNumber)
        );
        
        $groupId = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array($this->_groupUUIDAttribute)
        )->getFirst();
        
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
        if(strtolower($this->_groupUUIDAttribute) == 'gidnumber') {
            return $_uuid;
        }
        
        throw new RuntimeException('still untested');
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_groupUUIDAttribute, Zend_Ldap::filterEscape($_uuid)
        );
        
        $groupId = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('gidnumber')
        )->getFirst();
        
        return $groupId['gidnumber'][0];
    }
}