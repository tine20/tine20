<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * sql implementation of the groups interface
 * 
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_Sql extends Tinebase_Group_Abstract
{
    use Tinebase_Controller_Record_ModlogTrait;

    protected static $_doJoinXProps = true;

    /**
     * Model name
     *
     * @var string
     *
     * @todo perhaps we can remove that and build model name from name of the class (replace 'Controller' with 'Model')
     */
    protected $_modelName = 'Tinebase_Model_Group';

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * the groups table
     *
     * @var Tinebase_Db_Table
     */
    protected $groupsTable;
    
    /**
     * the groupmembers table
     *
     * @var Tinebase_Db_Table
     */
    protected $groupMembersTable;
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'groups';
    
    /**
     * set to true is addressbook table is found
     * 
     * @var boolean
     */
    protected $_addressBookInstalled = false;
    
    /**
     * in class cache 
     * 
     * @var array
     */
    protected $_classCache = array (
        'getGroupMemberships' => array()
    );
    
    /**
     * the constructor
     */
    public function __construct() 
    {
        $this->_db = Tinebase_Core::getDb();
        
        $this->groupsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . $this->_tableName));
        $this->groupMembersTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'group_members'));
        
        try {
            // MySQL throws an exception         if the table does not exist
            // PostgreSQL returns an empty array if the table does not exist
            $adbSchema = Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . 'addressbook');
            $adbListsSchema = Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . 'addressbook_lists');
            if (! empty($adbSchema) && ! empty($adbListsSchema) ) {
                $this->_addressBookInstalled = TRUE;
            }
        } catch (Zend_Db_Statement_Exception $zdse) {
            // nothing to do
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
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        $classCacheId = $accountId;
        
        if (isset($this->_classCache[__FUNCTION__][$classCacheId])) {
            return $this->_classCache[__FUNCTION__][$classCacheId];
        }
        
        $cacheId     = Tinebase_Helper::convertCacheId(__FUNCTION__ . $classCacheId);
        $memberships = Tinebase_Core::getCache()->load($cacheId);
        
        if (! $memberships) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ .' fetch group memberships from db');

            $select = $this->_db->select()
                ->distinct()
                ->from(array('group_members' => SQL_TABLE_PREFIX . 'group_members'), array('group_id'))
                ->where($this->_db->quoteIdentifier('account_id') . ' = ?', $accountId);
            
            $stmt = $this->_db->query($select);
            
            $memberships = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
            
            Tinebase_Core::getCache()->save($memberships, $cacheId, [__CLASS__], 300);
        }
        
        $this->_classCache[__FUNCTION__][$classCacheId] = $memberships;
        
        return $memberships;
    }
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array with account ids
     */
    public function getGroupMembers($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $cacheId = Tinebase_Helper::convertCacheId(__FUNCTION__ . $groupId);
        $members = Tinebase_Core::getCache()->load($cacheId);

        if (false === $members) {
            $members = array();

            $select = $this->groupMembersTable->select();
            $select->where($this->_db->quoteIdentifier('group_id') . ' = ?', $groupId);

            $rows = $this->groupMembersTable->fetchAll($select);
            
            foreach($rows as $member) {
                $members[] = $member->account_id;
            }

            Tinebase_Core::getCache()->save($members, $cacheId, [__CLASS__], 300);
        }

        return $members;
    }

    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param  mixed $_groupId
     * @param  array $_groupMembers
     * @return void
     */
    public function setGroupMembers($_groupId, $_groupMembers)
    {
        if ($_groupMembers === null) {
            // do nothing
            return;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Setting ' . count($_groupMembers) . ' new groupmembers for group ' . $_groupId);
        
        if ($this instanceof Tinebase_Group_Interface_SyncAble) {
            $_groupMembers = $this->setGroupMembersInSyncBackend($_groupId, $_groupMembers);
        }
        
        $this->setGroupMembersInSqlBackend($_groupId, $_groupMembers);
    }
     
    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param  mixed  $_groupId
     * @param  array  $_groupMembers
     * @throws Zend_Db_Statement_Exception
     * @return void
     */
    public function setGroupMembersInSqlBackend($_groupId, $_groupMembers)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);

        $oldGroupMembers = $this->getGroupMembers($groupId);

        // remove old members
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('group_id') . ' = ?', $groupId);
        $this->groupMembersTable->delete($where);
        
        // check if users have accounts
        $userIdsWithExistingAccounts = Tinebase_User::getInstance()->getMultiple($_groupMembers)->getArrayOfIds();
        
        if (count($_groupMembers) > 0) {
            // add new members
            foreach ($_groupMembers as $accountId) {
                $accountId = Tinebase_Model_User::convertUserIdToInt($accountId);
                if (in_array($accountId, $userIdsWithExistingAccounts)) {
                    try {
                        $this->_db->insert(SQL_TABLE_PREFIX . 'group_members', array(
                            'group_id' => $groupId,
                            'account_id' => $accountId
                        ));
                    } catch (Zend_Db_Statement_Exception $zdse) {
                        // ignore duplicate exceptions
                        if (! preg_match('/duplicate/i', $zdse->getMessage())) {
                            throw $zdse;
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                . ' ' . $zdse->getMessage());
                        }
                    }

                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' User with ID ' . $accountId . ' does not have an account!');
                }
                
                $this->_clearCache(array('getGroupMemberships' => $accountId));
            }
        }
        
        $this->_clearCache(array('getGroupMembers' => $groupId));

        $newGroupMembers = $this->getGroupMembers($groupId);

        if (!empty(array_diff($oldGroupMembers, $newGroupMembers)) || !empty(array_diff($newGroupMembers, $oldGroupMembers)))
        {
            $oldGroup = new Tinebase_Model_Group(array('id' => $groupId, 'members' => $oldGroupMembers), true);
            $newGroup = new Tinebase_Model_Group(array('id' => $groupId, 'members' => $newGroupMembers), true);
            $this->_writeModLog($newGroup, $oldGroup);
        }
    }
    
    /**
     * invalidate cache by type/id
     * 
     * @param array $cacheIds
     */
    protected function _clearCache($cacheIds = array())
    {
        $cache = Tinebase_Core::getCache();

        if (empty($cacheIds)) {
            $this->resetClassCache();
        } else {
            foreach ($cacheIds as $type => $id) {
                $cacheId = Tinebase_Helper::convertCacheId($type . $id);
                $cache->remove($cacheId);
                $this->resetClassCache($type);
            }
        }
    }

    public function resetClassCache($key = null)
    {
        if (null === $key) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, [__CLASS__]);
        }
        return parent::resetClassCache($key);
    }
    
    /**
     * set all groups an account is member of
     *
     * @param  mixed  $_userId    the userid as string or Tinebase_Model_User
     * @param  mixed  $_groupIds
     * 
     * @return array
     */
    public function setGroupMemberships($_userId, $_groupIds)
    {
        if(count($_groupIds) === 0) {
            throw new Tinebase_Exception_InvalidArgument('user must belong to at least one group');
        }
        
        if($this instanceof Tinebase_Group_Interface_SyncAble) {
            $this->setGroupMembershipsInSyncBackend($_userId, $_groupIds);
        }
        
        return $this->setGroupMembershipsInSqlBackend($_userId, $_groupIds);
    }
    
    /**
     * set all groups an user is member of
     *
     * @param  mixed  $_usertId   the account as integer or Tinebase_Model_User
     * @param  mixed  $_groupIds
     * @return array
     */
    public function setGroupMembershipsInSqlBackend($_userId, $_groupIds)
    {
        if ($_groupIds instanceof Tinebase_Record_RecordSet) {
            $_groupIds = $_groupIds->getArrayOfIds();
        }
        
        if (count($_groupIds) === 0) {
            throw new Tinebase_Exception_InvalidArgument('user must belong to at least one group');
        }
        
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
        
        $groupMemberships = $this->getGroupMemberships($userId);
        
        $removeGroupMemberships = array_diff($groupMemberships, $_groupIds);
        $addGroupMemberships    = array_diff($_groupIds, $groupMemberships);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' current groupmemberships: ' . print_r($groupMemberships, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' new groupmemberships: ' . print_r($_groupIds, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' added groupmemberships: ' . print_r($addGroupMemberships, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' removed groupmemberships: ' . print_r($removeGroupMemberships, true));
        
        foreach ($addGroupMemberships as $groupId) {
            $this->addGroupMemberInSqlBackend($groupId, $userId);
        }
        
        foreach ($removeGroupMemberships as $groupId) {
            $this->removeGroupMemberFromSqlBackend($groupId, $userId);
        }

        // useless event, its not used anywhere!
        $event = new Tinebase_Group_Event_SetGroupMemberships(array(
            'user'               => $_userId,
            'addedMemberships'   => $addGroupMemberships,
            'removedMemberships' => $removeGroupMemberships
        ));
        Tinebase_Event::fireEvent($event);
        
        return $this->getGroupMemberships($userId);
    }
    
    /**
     * add a new groupmember to a group
     *
     * @param  string  $_groupId
     * @param  string  $_accountId
     */
    public function addGroupMember($_groupId, $_accountId)
    {
        if ($this instanceof Tinebase_Group_Interface_SyncAble) {
            $this->addGroupMemberInSyncBackend($_groupId, $_accountId);
        }
        
        $this->addGroupMemberInSqlBackend($_groupId, $_accountId);
    }
    
    /**
     * add a new groupmember to a group
     *
     * @param  string  $_groupId
     * @param  string  $_accountId
     */
    public function addGroupMemberInSqlBackend($_groupId, $_accountId)
    {
        $groupId   = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);

        $memberShips = $this->getGroupMemberships($accountId);
        
        if (!in_array($groupId, $memberShips)) {

            $oldGroupMembers = $this->getGroupMembers($groupId);

            $data = array(
                'group_id'      => $groupId,
                'account_id'    => $accountId
            );
        
            $this->groupMembersTable->insert($data);
            
            $this->_clearCache(array(
                'getGroupMembers'     => $groupId,
                'getGroupMemberships' => $accountId,
            ));

            $newGroupMembers = $this->getGroupMembers($groupId);

            if (!empty(array_diff($oldGroupMembers, $newGroupMembers)) || !empty(array_diff($newGroupMembers, $oldGroupMembers)))
            {
                $oldGroup = new Tinebase_Model_Group(array('id' => $groupId, 'members' => $oldGroupMembers), true);
                $newGroup = new Tinebase_Model_Group(array('id' => $groupId, 'members' => $newGroupMembers), true);
                $this->_writeModLog($newGroup, $oldGroup);
            }
        }
        
    }
    
    /**
     * remove one groupmember from the group
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId
     */
    public function removeGroupMember($_groupId, $_accountId)
    {
        if ($this instanceof Tinebase_Group_Interface_SyncAble) {
            $this->removeGroupMemberInSyncBackend($_groupId, $_accountId);
        }
        
        return $this->removeGroupMemberFromSqlBackend($_groupId, $_accountId);
    }
    
    /**
     * remove one groupmember from the group
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId
     */
    public function removeGroupMemberFromSqlBackend($_groupId, $_accountId)
    {
        $groupId   = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);

        $oldGroupMembers = $this->getGroupMembers($groupId);
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('group_id') . '= ?', $groupId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . '= ?', $accountId),
        );
         
        $this->groupMembersTable->delete($where);
        
        $this->_clearCache(array(
            'getGroupMembers'     => $groupId,
            'getGroupMemberships' => $accountId,
        ));

        $newGroupMembers = $this->getGroupMembers($groupId);

        if (!empty(array_diff($oldGroupMembers, $newGroupMembers)) || !empty(array_diff($newGroupMembers, $oldGroupMembers)))
        {
            $oldGroup = new Tinebase_Model_Group(array('id' => $groupId, 'members' => $oldGroupMembers), true);
            $newGroup = new Tinebase_Model_Group(array('id' => $groupId, 'members' => $newGroupMembers), true);
            $this->_writeModLog($newGroup, $oldGroup);
        }
    }
    
    /**
     * create a new group
     *
     * @param   Tinebase_Model_Group  $_group
     * 
     * @return  Tinebase_Model_Group
     * 
     * @todo do not create group in sql if sync backend is readonly?
     */
    public function addGroup(Tinebase_Model_Group $_group)
    {
        if ($this instanceof Tinebase_Group_Interface_SyncAble) {
            $groupFromSyncBackend = $this->addGroupInSyncBackend($_group);
            
            if (isset($groupFromSyncBackend->id)) {
                $_group->setId($groupFromSyncBackend->getId());
            }
        }
        
        return $this->addGroupInSqlBackend($_group);
    }
    
    /**
     * alias for addGroup
     * 
     * @param Tinebase_Model_Group $group
     * @return Tinebase_Model_Group
     */
    public function create(Tinebase_Model_Group $group)
    {
        return $this->addGroup($group);
    }
    
    /**
     * create a new group in sql backend
     *
     * @param   Tinebase_Model_Group  $_group
     * 
     * @return  Tinebase_Model_Group
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function addGroupInSqlBackend(Tinebase_Model_Group $_group)
    {
        if(!$_group->isValid()) {
            throw new Tinebase_Exception_Record_Validation('invalid group object');
        }

        // prevent changing of email if it does not match configured domains
        Tinebase_EmailUser::checkDomain($_group->email, true);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Creating new group ' . $_group->name 
            //. print_r($_group->toArray(), true)
        );
        
        if(!$_group->getId()) {
            $groupId = $_group->generateUID();
            $_group->setId($groupId);
        }
        
        if (!$_group->list_id) {
            $_group->visibility = 'hidden';
            $_group->list_id    = null;
        }
        
        $data = $_group->toArray();
        
        unset($data['members']);
        unset($data['container_id']);
        unset($data['xprops']);

        // TODO should be done in the model
        $data['account_only'] = ! isset($data['account_only']) || empty($data['account_only']) ? 0 : (int) $data['account_only'];

        $this->groupsTable->insert($data);

        $newGroup = clone $_group;
        $newGroup->members = null;
        $newGroup->container_id = null;
        $this->_writeModLog($newGroup, null);
        
        return $_group;
    }
    
    /**
     * update a group
     *
     * @param  Tinebase_Model_Group  $_group
     * 
     * @return Tinebase_Model_Group
     */
    public function updateGroup(Tinebase_Model_Group $_group)
    {
        if ($this instanceof Tinebase_Group_Interface_SyncAble) {
            $this->updateGroupInSyncBackend($_group);
        }
        
        return $this->updateGroupInSqlBackend($_group);
    }
    
    /**
     * create a new group in sync backend
     * 
     * NOTE: sets visibility to HIDDEN if list_id is empty
     *
     * @param  Tinebase_Model_Group  $_group
     * @return Tinebase_Model_Group
     */
    public function updateGroupInSqlBackend(Tinebase_Model_Group $_group)
    {
        // prevent changing of email if it does not match configured domains
        Tinebase_EmailUser::checkDomain($_group->email, true);

        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_group);

        $oldGroup = $this->getGroupById($groupId);

        if (empty($_group->list_id)) {
            $_group->visibility = Tinebase_Model_Group::VISIBILITY_HIDDEN;
            $_group->list_id    = null;
        }

        $data = array(
            'name'          => $_group->name,
            'description'   => $_group->description,
            'visibility'    => $_group->visibility,
            'email'         => $_group->email,
            'list_id'       => $_group->list_id,
            'account_only'  => empty($_group->account_only) ? 0 : (int) $_group->account_only,
            'created_by'            => $_group->created_by,
            'creation_time'         => $_group->creation_time,
            'last_modified_by'      => $_group->last_modified_by,
            'last_modified_time'    => $_group->last_modified_time,
            'is_deleted'            => $_group->is_deleted,
            'deleted_time'          => $_group->deleted_time,
            'deleted_by'            => $_group->deleted_by,
            'seq'                   => $_group->seq,
        );
        
        if (empty($data['seq'])) {
            unset($data['seq']);
        }
        
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $groupId);
        
        $this->groupsTable->update($data, $where);
        
        $updatedGroup = $this->getGroupById($groupId);

        $this->_writeModLog($updatedGroup, $oldGroup);

        return $updatedGroup;
    }
    
    /**
     * delete groups
     *
     * @param   mixed $_groupId

     * @throws  Tinebase_Exception_Backend
     */
    public function deleteGroups($_groupId)
    {
        $groupIds = array();
        
        if (is_array($_groupId) or $_groupId instanceof Tinebase_Record_RecordSet) {
            foreach ($_groupId as $groupId) {
                $groupIds[] = Tinebase_Model_Group::convertGroupIdToInt($groupId);
            }
            if (count($groupIds) === 0) {
                return;
            }
        } else {
            $groupIds[] = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        }
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            $this->deleteGroupsInSqlBackend($groupIds);
            if ($this instanceof Tinebase_Group_Interface_SyncAble) {
                $this->deleteGroupsInSyncBackend($groupIds);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Exception::log($e);
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
    }
    
    /**
     * set primary group for accounts with given primary group id
     * 
     * @param array $groupIds
     * @param string $newPrimaryGroupId
     * @throws Tinebase_Exception_Record_NotDefined
     */
    protected function _updatePrimaryGroupsOfUsers($groupIds, $newPrimaryGroupId = null)
    {
        if ($newPrimaryGroupId === null) {
            $newPrimaryGroupId = $this->getDefaultGroup()->getId();
        }
        foreach ($groupIds as $groupId) {
            $users = Tinebase_User::getInstance()->getUsersByPrimaryGroup($groupId);
            $users->accountPrimaryGroup = $newPrimaryGroupId;
            foreach ($users as $user) {
                Tinebase_User::getInstance()->updateUser($user);
            }
        }
    }
    
    /**
     * delete groups in sql backend
     * 
     * @param array $groupIds
     */
    public function deleteGroupsInSqlBackend($groupIds)
    {
        $this->_updatePrimaryGroupsOfUsers($groupIds);

        $groups = array();
        foreach($groupIds as $groupId) {
            $group = $this->getGroupById($groupId);
            $group->members = $this->getGroupMembers($groupId);
            $groups[] = $group;
        }

        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('group_id') . ' IN (?)', (array) $groupIds);
        $this->groupMembersTable->delete($where);
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', (array) $groupIds);
        $this->groupsTable->delete($where);

        foreach($groups as $group) {
            $this->_writeModLog(null, $group);
        }
    }
    
    /**
     * Delete all groups returned by {@see getGroups()} using {@see deleteGroups()}
     * @return void
     */
    public function deleteAllGroups()
    {
        $groups = $this->getGroups();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($groups) .' groups');

        if(count($groups) > 0) {
            $this->deleteGroups($groups);
        }
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
        $select = $this->_getSelect();
        
        if($_filter !== NULL) {
            $select->where($this->_db->quoteIdentifier($this->_tableName. '.name') . ' LIKE ?', '%' . $_filter . '%');
        }
        if($_sort !== NULL) {
            $select->order($this->_tableName . '.' . $_sort . ' ' . $_dir);
        }
        if($_start !== NULL) {
            $select->limit($_limit, $_start);
        }
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        $stmt->closeCursor();
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Group', $queryResult, TRUE);
        
        return $result;
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
        $result = $this->getGroupByPropertyFromSqlBackend('name', $_name);
        
        return $result;
    }
    
    /**
     * get group by property
     *
     * @param   string  $_property      the key to filter
     * @param   string  $_value         the value to search for
     *
     * @return  Tinebase_Model_Group
     * @throws  Tinebase_Exception_Record_NotDefined
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function getGroupByPropertyFromSqlBackend($_property, $_value)
    {
        if (! in_array($_property, array('id', 'name', 'description', 'list_id', 'email'))) {
            throw new Tinebase_Exception_InvalidArgument('property not allowed');
        }
        
        $select = $this->_getSelect();
        
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . $_property) . ' = ?', $_value);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!$queryResult) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }
        
        $result = new Tinebase_Model_Group($queryResult, TRUE);
        $result->runConvertToRecord();
        
        return $result;
    }
    
    
    /**
     * get group by id
     *
     * @param   string $_name
     * @return  Tinebase_Model_Group
     * @throws  Tinebase_Exception_Record_NotDefined
     */
    public function getGroupById($_groupId)
    {
        if (! $_groupId) {
            throw new Tinebase_Exception_InvalidArgument('$_groupId required');
        }

        $groupdId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $result = $this->getGroupByPropertyFromSqlBackend('id', $groupdId);
        
        return $result;
    }
    
    /**
     * Get multiple groups
     *
     * @param string|array $_ids Ids
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
        
        if (! empty($_ids)) {
            $rows = $this->_getSelect()->where($this->_db->quoteIdentifier($this->_tableName . '.id') . ' IN (?)',
                array_unique((array) $_ids))->query()->fetchAll();

            foreach ($rows as $row) {
                $group = new Tinebase_Model_Group($row, true);
                $group->runConvertToRecord();
                $result->addRecord($group);
            }
        }
        
        return $result;
    }

    /**
     * required for update path to Adb 12.7 ... can be removed once we drop updatability from < 12.7 to 12.7+
     */
    public static function doJoinXProps($join = true)
    {
        static::$_doJoinXProps = $join;
    }

    /**
     * get the basic select object to fetch records from the database
     * 
     * NOTE: container_id, xprops is joined from addressbook lists table
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = $this->_db->select();
        
        $select->from(array($this->_tableName => SQL_TABLE_PREFIX . $this->_tableName), $_cols);
        
        if ($this->_addressBookInstalled === true) {
            $joinCols = ['container_id', 'xprops'];
            if (!static::$_doJoinXProps) {
                unset($joinCols[1]);
            }
            $select->joinLeft(
                array('addressbook_lists' => SQL_TABLE_PREFIX . 'addressbook_lists'),
                $this->_db->quoteIdentifier($this->_tableName . '.list_id') . ' = ' . $this->_db->quoteIdentifier('addressbook_lists.id'),
                $joinCols
            );
        }
        
        return $select;
    }
    
    /**
     * Method called by {@see Addressbook_Setup_Initialize::_initilaize()}
     * 
     * @param $_options
     * @return mixed
     */
    public function __importGroupMembers($_options = null)
    {
        //nothing to do
        return null;
    }

    /**
     * @param Tinebase_Model_ModificationLog $modification
     */
    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $modification)
    {
        switch ($modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
                $record = new Tinebase_Model_Group($diff->diff);
                Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($record);
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($record, 'create');
                $this->addGroup($record);
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:
                $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
                if (isset($diff->diff['members']) && is_array($diff->diff['members'])) {
                    $this->setGroupMembers($modification->record_id, $diff->diff['members']);
                    $record = $this->getGroupById($modification->record_id);
                    $record->members = $this->getGroupMembers($record->getId());
                    Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($record);
                } else {
                    $record = $this->getGroupById($modification->record_id);
                    $currentRecord = clone $record;
                    $record->applyDiff($diff);
                    $record->members = $this->getGroupMembers($record->getId());
                    Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($record);
                    Tinebase_Timemachine_ModificationLog::setRecordMetaData($record, 'update', $currentRecord);
                    $this->updateGroup($record);
                }
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                $record = $this->getGroupById($modification->record_id);
                if (!empty($record->list_id)) {
                    Addressbook_Controller_List::getInstance()->delete($record->list_id);
                }
                $this->deleteGroups($modification->record_id);
                break;

            default:
                throw new Tinebase_Exception('unknown Tinebase_Model_ModificationLog->old_value: ' . $modification->old_value);
        }
    }

    /**
     * @param Tinebase_Model_ModificationLog $modification
     * @param bool $dryRun
     */
    public function undoReplicationModificationLog(Tinebase_Model_ModificationLog $modification, $dryRun)
    {
        if (Tinebase_Timemachine_ModificationLog::CREATED === $modification->change_type) {
            if (!$dryRun) {
                $record = $this->getGroupById($modification->record_id);
                if (!empty($record->list_id)) {
                    Addressbook_Controller_List::getInstance()->delete($record->list_id);
                }
                $this->deleteGroups($modification->record_id);
            }
        } elseif (Tinebase_Timemachine_ModificationLog::DELETED === $modification->change_type) {
            $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
            $model = $modification->record_type;
            /** @var Tinebase_Model_Group $record */
            $record = new $model($diff->oldData, true);
            if (!$dryRun) {
                Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($record);
                $createdGroup = $this->create($record);
                if (is_array($record->members) && !empty($record->members)) {
                    $this->setGroupMembers($createdGroup->getId(), $record->members);
                }
            }
        } else {
            $record = $this->getGroupById($modification->record_id);
            $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' Undoing diff ' . print_r($diff->toArray(), true));

            // this undo will (re)load and populate members property if required
            $record->undo($diff);

            if (! $dryRun) {
                if (isset($diff->diff['members']) && is_array($diff->diff['members']) && is_array($record->members)) {
                    $this->setGroupMembers($record->getId(), $record->members);
                } else {
                    $record->members = $this->getGroupMembers($record->getId());
                }
                Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($record);
                $this->updateGroup($record);
            }
        }
    }

    public function sanitizeGroupListSync($dryRun = true)
    {
        // find duplicate list references
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        try {
            if ($this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
                $listIds = $this->_db->select()->from([$this->_tableName => SQL_TABLE_PREFIX . $this->_tableName],
                    [
                        'list_id'
                    ])
                    ->where($this->_db->quoteIdentifier($this->_tableName . '.list_id') . ' IS NOT NULL')
                    ->having(new Zend_Db_Expr('count(' . $this->_db->quoteIdentifier('list_id') . ') > 1'))
                    ->group('list_id')->query()->fetchAll(Zend_Db::FETCH_COLUMN, 0);
            } else {
                $listIds = $this->_db->select()->from([$this->_tableName => SQL_TABLE_PREFIX . $this->_tableName],
                    [
                        'list_id',
                        new Zend_Db_Expr('count(' . $this->_db->quoteIdentifier('list_id') . ') AS '
                            . $this->_db->quoteIdentifier('c'))
                    ])
                    ->where($this->_db->quoteIdentifier($this->_tableName . '.list_id') . ' IS NOT NULL')
                    ->having($this->_db->quoteIdentifier('c') . ' > 1')
                    ->group('list_id')->query()->fetchAll(Zend_Db::FETCH_COLUMN, 0);
            }

            if (count($listIds) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ . ' found duplicate list references ' . join(', ', $listIds));

                if (!$dryRun) {
                    $this->_db->update(SQL_TABLE_PREFIX . $this->_tableName, ['list_id' => null],
                        $this->_db->quoteInto($this->_db->quoteIdentifier('list_id') . ' = (?)', $listIds));
                }

                echo PHP_EOL . 'found ' . count($listIds) . ' duplicate list references (fixed)' . PHP_EOL;
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        // find groups which are deleted but lists are not
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        try {
            $groupIds = $this->_db->select()->from([$this->_tableName => SQL_TABLE_PREFIX . $this->_tableName], ['id'])
                ->join(
                    ['addressbook_lists' => SQL_TABLE_PREFIX . 'addressbook_lists'],
                    $this->_db->quoteIdentifier('addressbook_lists.id') . ' = '
                    . $this->_db->quoteIdentifier($this->_tableName . '.list_id') . ' AND '
                    . $this->_db->quoteIdentifier('addressbook_lists.is_deleted') . ' = 0',
                    []
                )->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 1')
                ->query()->fetchAll(Zend_Db::FETCH_COLUMN, 0);

            if (count($groupIds) > 0) {
                $msg = 'found ' . count($groupIds) . ' groups which are deleted and linked to undeleted lists: '
                    . join(', ', $groupIds);
                echo PHP_EOL . $msg . PHP_EOL . '(not fixed!)' . PHP_EOL;
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ . $msg);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        // find groups with deleted lists
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        try {
            $groupIds = $this->_db->select()->from([$this->_tableName => SQL_TABLE_PREFIX . $this->_tableName], ['id'])
                ->join(
                    ['addressbook_lists' => SQL_TABLE_PREFIX . 'addressbook_lists'],
                    $this->_db->quoteIdentifier('addressbook_lists.id') . ' = '
                    . $this->_db->quoteIdentifier($this->_tableName . '.list_id') . ' AND '
                    . $this->_db->quoteIdentifier('addressbook_lists.is_deleted') . ' = 1',
                    []
                )->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0')
                ->query()->fetchAll(Zend_Db::FETCH_COLUMN, 0);

            if (count($groupIds) > 0) {
                $msg = 'found ' . count($groupIds) . ' groups which are linked to deleted lists: '
                    . join(', ', $groupIds);
                echo PHP_EOL . $msg . PHP_EOL . '(not fixed!)' . PHP_EOL;
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ . $msg);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }


        // find groups with lists of wrong type
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        try {
            $listIds = $this->_db->select()->from([$this->_tableName => SQL_TABLE_PREFIX . $this->_tableName], [])
                ->join(
                    ['addressbook_lists' => SQL_TABLE_PREFIX . 'addressbook_lists'],
                    $this->_db->quoteIdentifier('addressbook_lists.id') . ' = '
                    . $this->_db->quoteIdentifier($this->_tableName . '.list_id'),
                    ['id']
                )->where($this->_db->quoteIdentifier('addressbook_lists.type') . ' <> \'' .
                    Addressbook_Model_List::LISTTYPE_GROUP . '\'')
                ->query()->fetchAll(Zend_Db::FETCH_COLUMN, 0);

            if (count($listIds) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ . ' found lists linked to groups of the wrong type ' . join(', ', $listIds));

                if (!$dryRun) {
                    Addressbook_Controller_List::getInstance()->getBackend()->updateMultiple($listIds,
                        ['type' => Addressbook_Model_List::LISTTYPE_GROUP]);
                }

                echo PHP_EOL . 'found ' . count($listIds) . ' lists linked to groups of the wrong type (fixed)'
                    . PHP_EOL;
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        // find groups without lists
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        try {
            $groupIds = $this->_db->select()->from([$this->_tableName => SQL_TABLE_PREFIX . $this->_tableName], ['id'])
                ->joinLeft(
                    ['addressbook_lists' => SQL_TABLE_PREFIX . 'addressbook_lists'],
                    $this->_db->quoteIdentifier('addressbook_lists.id') . ' = '
                    . $this->_db->quoteIdentifier($this->_tableName . '.list_id'),
                    []
                )->where($this->_db->quoteIdentifier('addressbook_lists.id') . ' IS NULL')
                ->query()->fetchAll(Zend_Db::FETCH_COLUMN, 0);

            if (count($groupIds) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ . ' found groups not having a list ' . join(', ', $groupIds));

                /** @var Tinebase_Model_Group $group */
                foreach ($this->getMultiple($groupIds) as $group) {
                    if (!empty($group->list_id)) {
                        $group->list_id = null;
                    }
                    $group->members = $this->getGroupMembers($group);
                    if (!$dryRun) {
                        Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($group);
                        $this->updateGroupInSqlBackend($group);
                    }
                }

                echo PHP_EOL . 'found ' . count($groupIds) . ' groups not having a list (fixed)' . PHP_EOL;
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        // addressbook lists of type group without a group
        // make them type list and report them
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        try {
            $ids = [];
            $names = [];
            foreach ($this->_db->select()->from(['addressbook_lists' => SQL_TABLE_PREFIX . 'addressbook_lists'],
                ['id', 'name'])
                         ->joinLeft(
                             [$this->_tableName => SQL_TABLE_PREFIX . $this->_tableName],
                             $this->_db->quoteIdentifier('addressbook_lists.id') . ' = '
                             . $this->_db->quoteIdentifier($this->_tableName . '.list_id'),
                             [])
                         ->where($this->_db->quoteIdentifier('addressbook_lists.type') . ' = \''
                             . Addressbook_Model_List::LISTTYPE_GROUP . '\' AND '
                             . $this->_db->quoteIdentifier($this->_tableName . '.id') . ' IS NULL')
                         ->query()->fetchAll() as $row) {
                $ids[] = $row['id'];
                $names[] = $row['name'];
            }

            if (count($ids) > 0) {
                $msg = 'changed the following lists from type group to type list:' . PHP_EOL
                    . join(', ', $names);
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ . ' ' . $msg . ' ' . join(', ', $ids));

                if (!$dryRun) {
                    Addressbook_Controller_List::getInstance()->getBackend()->updateMultiple($ids,
                        ['type' => Addressbook_Model_List::LISTTYPE_LIST]);
                }
                
                echo $msg . PHP_EOL;
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        // check members
        foreach ($dryRun ? [] : Tinebase_Group::getInstance()->getGroups() as $group) {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            try {
                try {
                    $group = Tinebase_Group::getInstance()->getGroupById($group);
                } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                        // race condition, just continue
                        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                        $transactionId = null;
                        continue;
                }
                if (!empty($group->list_id)) {
                    $oldListId = $group->list_id;
                    $group->members = Tinebase_Group::getInstance()->getGroupMembers($group);
                    Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($group);
                    if ($group->list_id !== $oldListId) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__
                            . '::' . __LINE__ . ' groups list_id changed in createOrUpdateByGroup unexpectedly: '
                            . $group->getId());
                        Tinebase_Group::getInstance()->updateGroup($group);
                    }
                }

                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                $transactionId = null;
            } finally {
                if (null !== $transactionId) {
                    Tinebase_TransactionManager::getInstance()->rollBack();
                }
            }
        }
    }
}
