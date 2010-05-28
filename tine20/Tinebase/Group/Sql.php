<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * sql implementation of the groups interface
 * 
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_Sql extends Tinebase_Group_Abstract
{
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
     * the constructor
     */
    public function __construct() {
    	$this->_db = Tinebase_Core::getDb();
        $this->groupsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'groups'));
        $this->groupMembersTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'group_members'));
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
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = convertCacheId('getGroupMemberships' . $accountId);
        $memberships = $cache->load($cacheId);

        if (! $memberships) {
            $memberships = array();
            $colName = $this->groupsTable->getAdapter()->quoteIdentifier('account_id');
            $select = $this->groupMembersTable->select();
            $select->where($colName . ' = ?', $accountId);

            $rows = $this->groupMembersTable->fetchAll($select);

            foreach($rows as $membership) {
                $memberships[] = $membership->group_id;
            }

            $cache->save($memberships, $cacheId, array('group'));
        }

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
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = convertCacheId('getGroupMembers' . $groupId);
        $members = $cache->load($cacheId);

        if (! $members) {
            $members = array();

            $select = $this->groupMembersTable->select();
            $select->where('group_id = ?', $groupId);

            $rows = $this->groupMembersTable->fetchAll($select);

            foreach($rows as $member) {
                $members[] = $member->account_id;
            }

            $cache->save($members, $cacheId, array('group'));
        }

        return $members;
    }
    
    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param  mixed  $_groupId
     * @param  array  $_groupMembers
     */
    public function setGroupMembers($_groupId, $_groupMembers)
    {
        if($this instanceof Tinebase_Group_Interface_SyncAble) {
            $this->setGroupMembersInSyncBackend($_groupId, $_groupMembers);
        }
        
        $this->setGroupMembersInSqlBackend($_groupId, $_groupMembers);
    }
     
    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param  mixed  $_groupId
     * @param  array  $_groupMembers
     */
    public function setGroupMembersInSqlBackend($_groupId, $_groupMembers)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        // remove old members
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('group_id') . ' = ?', $groupId);
        $this->groupMembersTable->delete($where);
        
        if(count($_groupMembers) > 0) {
            $db = Tinebase_Core::getDb();
            $stmt = $db->prepare('INSERT INTO ' . SQL_TABLE_PREFIX . 'group_members (group_id, account_id) VALUES (?,?)');
            
            // add new members
            foreach ($_groupMembers as $accountId) {
                $accountId = Tinebase_Model_User::convertUserIdToInt($accountId);        
                $stmt->execute(array(
                    $groupId, 
                    $accountId
                ));
            }
        }
        
        // invalidate cache (no memcached support yet)
        Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('group'));
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
        if($this instanceof Tinebase_Group_Interface_SyncAble) {
            $this->setGroupMembershipsInSyncBackend($_userId, $_groupIds);
        }
        
        return $this->setGroupMembershipsInSqlBackend($_userId, $_groupIds);
    }
    
    /**
     * replace all current groupmemberships of user in sql backend
     *
     * @param  mixed  $_userId
     * @param  mixed  $_groupIds
     * 
     * @return array
     */
    public function setGroupMembershipsInSqlBackend($_userId, $_groupIds)
    {
        if(count($_groupIds) === 0) {
            throw new Tinebase_Exception_InvalidArgument('user must belong to at least one group');
        }
        
        $userId = Tinebase_Model_user::convertUserIdToInt($_userId);
        
        // remove old memberships
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . ' = ?', $userId);
        $this->groupMembersTable->delete($where);
        
        $db = Tinebase_Core::getDb();
        $stmt = $db->prepare('INSERT INTO ' . SQL_TABLE_PREFIX . 'group_members (group_id, account_id) VALUES (?,?)');
        
        // add new members
        foreach ($_groupIds as $groupId) {
            $groupId = Tinebase_Model_Group::convertGroupIdToInt($groupId); 
            $stmt->execute(array(
                $groupId, 
                $userId
            ));
        }
        
        // invalidate cache (no memcached support yet)
        Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('group'));
        
        return $this->getGroupMemberships($_userId);
    }
    
    /**
     * add a new groupmember to a group
     *
     * @param  string  $_groupId
     * @param  string  $_accountId
     */
    public function addGroupMember($_groupId, $_accountId)
    {
        if($this instanceof Tinebase_Group_Interface_SyncAble) {
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

        $data = array(
            'group_id'      => $groupId,
            'account_id'    => $accountId
        );
        
        try {
            $this->groupMembersTable->insert($data);
            
            // invalidate cache (no memcached support yet)
            Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('group'));     
                   
        } catch (Zend_Db_Statement_Exception $e) {
            // account is already member of this group
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
        if($this instanceof Tinebase_Group_Interface_SyncAble) {
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
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('group_id') . '= ?', $groupId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . '= ?', $accountId),
        );
         
        $this->groupMembersTable->delete($where);
        
        // invalidate cache (no memcached support yet)
        Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('group'));
    }
    
    /**
     * create a new group
     *
     * @param   Tinebase_Model_Group  $_group
     * 
     * @return  Tinebase_Model_Group
     */
    public function addGroup(Tinebase_Model_Group $_group)
    {
        if($this instanceof Tinebase_Group_Interface_SyncAble) {
            $groupFromSyncBackend = $this->addGroupInSyncBackend($_group);
            $_group->setId($groupFromSyncBackend->getId());
        }
        
        return $this->addGroupInSqlBackend($_group);
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Creating new group ' . $_group->name 
            //. print_r($_group->toArray(), true)
        );
        
        if(!isset($_group->id)) {
            $groupId = $_group->generateUID();
            $_group->setId($groupId);
        }
        
        $data = $_group->toArray();
        
        unset($data['members']);
        
        $this->groupsTable->insert($data);
                
        return $_group;
    }
    
    /**
     * create a new group
     *
     * @param  Tinebase_Model_Group  $_group
     * 
     * @return Tinebase_Model_Group
     */
    public function updateGroup(Tinebase_Model_Group $_group)
    {
        if($this instanceof Tinebase_Group_Interface_SyncAble) {
            $this->updateGroupInSyncBackend($_group);
        }
        
        return $this->updateGroupInSqlBackend($_group);
    }
    
    /**
     * create a new group in sync backend
     *
     * @param  Tinebase_Model_Group  $_group
     * @return Tinebase_Model_Group
     */
    public function updateGroupInSqlBackend(Tinebase_Model_Group $_group)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_group);
        
        $data = array(
            'name'          => $_group->name,
            'description'   => $_group->description
        );
        
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $groupId);
        
        $this->groupsTable->update($data, $where);
        
        return $this->getGroupById($groupId);
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
        
        if(is_array($_groupId) or $_groupId instanceof Tinebase_Record_RecordSet) {
            foreach($_groupId as $groupId) {
                $groupIds[] = Tinebase_Model_Group::convertGroupIdToInt($groupId);
            }
        } else {
            $groupIds[] = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        }        
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            
            $where = $this->_db->quoteInto($this->_db->quoteIdentifier('group_id') . ' IN (?)', $groupIds);
            $this->groupMembersTable->delete($where);
            $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $groupIds);
            $this->groupsTable->delete($where);
            
            if($this instanceof Tinebase_Group_Interface_SyncAble) {
                $this->deleteGroupsInSyncBackend($groupIds);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();            
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
    }
    
    /**
     * Delete all groups returned by {@see getGroups()} using {@see deleteGroups()}
     * @return void
     */
    public function deleteAllGroups()
    {
        $groups = $this->getGroups();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($groups) .' users');
        $this->deleteGroups($groups);
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
        $select = $this->groupsTable->select();
        
        if($_filter !== NULL) {
        
            $select->where($this->_db->quoteIdentifier('name') . ' LIKE ?', '%' . $_filter . '%');
        }
        if($_sort !== NULL) {
            $select->order("$_sort $_dir");
        }
        if($_start !== NULL) {
            $select->limit($_limit, $_start);
        }
        
        $rows = $this->groupsTable->fetchAll($select);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Group', $rows->toArray());
        
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
        $select = $this->groupsTable->select();
                
        $select->where($this->_db->quoteIdentifier('name') . ' = ?', $_name);
        
        $row = $this->groupsTable->fetchRow($select);

        if($row === NULL) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }
        
        $result = new Tinebase_Model_Group($row->toArray());
        
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
        $groupdId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);     
        
        $select = $this->groupsTable->select();
                
        $select->where($this->_db->quoteIdentifier('id') . ' = ?', $groupdId);
        
        $row = $this->groupsTable->fetchRow($select);
        
        if($row === NULL) {
            throw new Tinebase_Exception_Record_NotDefined('Group not found.');
        }

        $result = new Tinebase_Model_Group($row->toArray());
        
        return $result;
    }
    
    /**
     * Get multiple grups
     *
     * @param string|array $_ids Ids
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids)
    {
    	$result = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
    	
    	if (! empty($_ids)) {
	        $select = $this->groupsTable->select();
	        $select->where($this->_db->quoteIdentifier('id') . ' IN (?)', array_unique((array) $_ids));
	        
	        $rows = $this->groupsTable->fetchAll($select);
	        foreach ($rows as $row) {
	        	$result->addRecord(new Tinebase_Model_Group($row->toArray()));
	        }
    	}
    	
    	return $result;
    }
    
    /**
     * Method called by {@see Addressbook_Setup_Initialize::_initilaize()}
     * 
     * @param $_options
     * @return unknown_type
     */
    public function __importGroupMembers($_options = null)
    {
        //nothing to do
    }
}
