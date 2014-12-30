<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        extend/use sql abstract backend
 */

/**
 * this class handles the roles
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Tinebase_Acl_Roles
{
    /**
     * @var Zend_Db_Adapter
     */
    protected $_db;
    
    /**
     * @var Tinebase_Backend_Sql_Command_Interface
     */
    protected $_dbCommand;
    
    /**
     * the Zend_Dd_Table object
     *
     * @var Tinebase_Db_Table
     */
    protected $_rolesTable;
    
    /**
     * the Zend_Dd_Table object for role members
     *
     * @var Tinebase_Db_Table
     */
    protected $_roleMembersTable;

    /**
     * the Zend_Dd_Table object for role rights
     *
     * @var Tinebase_Db_Table
     */
    protected $_roleRightsTable;
        
    protected $_classCache = array(
        'getRoleMemberships' => array(),
        'hasRight'           => array(),
    );
    
    /**
     * holdes the _instance of the singleton
     *
     * @var Tinebase_Acl_Roles
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() 
    {
    }
    
    /**
     * the constructor
     *
     * disabled. use the singleton
     * temporarly the constructor also creates the needed tables on demand and fills them with some initial values
     */
    private function __construct() {

        $this->_rolesTable       = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'roles'));
        $this->_roleMembersTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'role_accounts'));
        $this->_roleRightsTable  = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'role_rights'));
        
        $this->_db        = Tinebase_Core::getDb();
        $this->_dbCommand = Tinebase_Backend_Sql_Command::factory($this->_db);
    }    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Acl_Roles
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Acl_Roles;
        }
        
        return self::$_instance;
    }        

    /**
     * check if one of the roles the user is in has a given right for a given application
     * 
     * we read all right for the given user at once and cache them in the internal class cache
     *
     * @param   string|Tinebase_Model_Application $_application the application (one of: app name, id or record)
     * @param   int $_accountId the numeric id of a user account
     * @param   int $_right the right to check for
     * @return  bool
     */
    public function hasRight($_application, $_accountId, $_right)
    {
        try {
            $application = Tinebase_Application::getInstance()->getApplicationById($_application);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
        
        if ($application->status !== Tinebase_Application::ENABLED) {
            return false;
        }
        
        try {
            $roleMemberships = $this->getRoleMemberships($_accountId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $roleMemberships = array();
        }
        
        if (empty($roleMemberships)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $_accountId . ' has no role/group memberships.');
            if (is_object(Tinebase_Core::getUser()) && Tinebase_Core::getUser()->getId() === $_accountId) {
                // @todo throw exception in this case?
                Tinebase_Session::destroyAndRemoveCookie();
            }
            
            return false;
        }
        
        $classCacheId = convertCacheId(implode('', $roleMemberships));
        
        if (!isset($this->_classCache[__FUNCTION__][$classCacheId])) {
            $select = $this->_db->select()
                ->distinct()
                ->from(array('role_rights' => SQL_TABLE_PREFIX . 'role_rights'), array('application_id', 'right'))
                ->where($this->_db->quoteIdentifier('role_id') . ' IN (?)', $roleMemberships);
                
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
            
            $stmt = $this->_db->query($select);
            $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            
            $rights = array();
            
            foreach ($rows as $row) {
                $rights[$row['application_id']][$row['right']] = true;
            }
            
            $this->_classCache[__FUNCTION__][$classCacheId] = $rights;
        } else {
            $rights = $this->_classCache[__FUNCTION__][$classCacheId];
        }
        
        $applicationId = $application->getId();
        
        return isset($rights[$applicationId]) && (isset($rights[$applicationId][$_right]) || isset($rights[$applicationId][Tinebase_Acl_Rights::ADMIN]));
    }
    
    /**
     * returns list of applications the user is able to use
     *
     * this function takes group memberships into account. Applications the accounts is able to use
     * must have any (was: the 'run') right set and the application must be enabled
     * 
     * @param   int $_accountId the numeric account id
     * @param   boolean $_anyRight is any right enough to geht app?
     * @return  array list of enabled applications for this account
     * @throws  Tinebase_Exception_AccessDenied if user has no role memberships
     */
    public function getApplications($_accountId, $_anyRight = FALSE)
    {
        $roleMemberships = $this->getRoleMemberships($_accountId);
        
        if (empty($roleMemberships)) {
            return new Tinebase_Record_RecordSet('Tinebase_Model_Application');
        }

        $select = $this->_db->select()
            ->distinct()
            ->from(array('role_rights' => SQL_TABLE_PREFIX . 'role_rights'), array())
            ->join(
                /* table  */ array('applications' => SQL_TABLE_PREFIX . 'applications'), 
                /* on     */ $this->_db->quoteIdentifier('role_rights.application_id') . ' = ' . $this->_db->quoteIdentifier('applications.id')
            )
            ->where($this->_db->quoteIdentifier('role_id') . ' IN (?)',          $roleMemberships)
            ->where($this->_db->quoteIdentifier('applications.status') . ' = ?', Tinebase_Application::ENABLED)
            ->order('order ASC');
        
        if ($_anyRight) {
            $select->where($this->_db->quoteIdentifier('role_rights.right') . " IS NOT NULL");
        } else {
            $select->where($this->_db->quoteIdentifier('role_rights.right') . ' = ?', Tinebase_Acl_Rights::RUN);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . $select);
        
        $stmt = $this->_db->query($select);
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Application', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        return $result;
    }

    /**
     * returns rights for given application and accountId
     *
     * @param   string $_application the name of the application
     * @param   int $_accountId the numeric account id
     * @return  array list of rights
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getApplicationRights($_application, $_accountId) 
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        
        if ($application->status !== Tinebase_Application::ENABLED) {
            throw new Tinebase_Exception_AccessDenied('User has no rights. the application is disabled.');
        }
        
        $roleMemberships = $this->getRoleMemberships($_accountId);
        
        $select = $this->_db->select()
            ->distinct()
            ->from(SQL_TABLE_PREFIX . 'role_rights', array('account_rights' => SQL_TABLE_PREFIX . 'role_rights.right'))
            ->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'role_rights.application_id') . ' = ?', $application->getId())
            ->where($this->_db->quoteIdentifier('role_id') . ' IN (?)', $roleMemberships);
        
        $stmt = $this->_db->query($select);

        return  $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
    }
        
    /**
     * Searches roles according to filter and paging
     * 
     * @param  Tinebase_Model_RoleFilter  $_filter
     * @param  Tinebase_Model_Pagination  $_paging
     * @return Tinebase_Record_RecordSet  Set of Tinebase_Model_Role
     */
    public function searchRoles($_filter, $_paging)
    {
        $select = $_filter->getSelect();
        
        $_paging->appendPaginationSql($select);
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_Role', $this->_db->fetchAssoc($select));
    }

    /**
     * Returns roles count
     * 
     * @param Tinebase_Model_RoleFilter $_filter
     * @return int
     */
    public function searchCount($_filter)
    {
        $select = $_filter->getSelect();
        
        $roles = new Tinebase_Record_RecordSet('Tinebase_Model_Role', $this->_db->fetchAssoc($select));
        return count($roles);
    }
    
    /**
     * Returns role identified by its id
     * 
     * @param   int  $_roleId
     * @return  Tinebase_Model_Role  
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_NotFound
     */
    public function getRoleById($_roleId)
    {
        $roleId = (int)$_roleId;
        if ($roleId != $_roleId && $roleId <= 0) {
            throw new Tinebase_Exception_InvalidArgument('$_roleId must be integer and greater than 0');
        }
        
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $roleId);
        if (!$row = $this->_rolesTable->fetchRow($where)) {
            throw new Tinebase_Exception_NotFound("role with id $roleId not found");
        }
        
        $result = new Tinebase_Model_Role($row->toArray());
        
        return $result;
    }
    
    /**
     * Returns role identified by its name
     * 
     * @param   string $_roleName
     * @return  Tinebase_Model_Role  
     * @throws  Tinebase_Exception_NotFound
     */
    public function getRoleByName($_roleName)
    {
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_roleName);

        if (!$row = $this->_rolesTable->fetchRow($where)) {
            throw new Tinebase_Exception_NotFound("Role $_roleName not found.");
        }
        
        $result = new Tinebase_Model_Role($row->toArray());
        
        return $result;
    }
    
    /**
     * Get multiple roles
     *
     * @param string|array $_ids Ids
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Role');
        
        if (! empty($_ids)) {
            $select = $this->_rolesTable->select();
            $select->where($this->_db->quoteIdentifier('id') . ' IN (?)', array_unique((array) $_ids));
            
            $rows = $this->_rolesTable->fetchAll($select);
            foreach ($rows as $row) {
                $result->addRecord(new Tinebase_Model_Role($row->toArray()));
            }
        }
        
        return $result;
    }
    
    /**
     * Creates a single role
     * 
     * @param  Tinebase_Model_Role
     * @return Tinebase_Model_Role
     */
    public function createRole(Tinebase_Model_Role $_role)
    {
        $data = $_role->toArray();
        if(is_object(Tinebase_Core::getUser())) {
            $data['created_by'] = Tinebase_Core::getUser()->getId();
        }
        $data['creation_time'] = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        try {
            $newId = $this->_rolesTable->insert($data);
            
            if ($newId === NULL) {
                $newId = $this->_db->lastSequenceId(substr(SQL_TABLE_PREFIX . 'roles', 0,25) . '_s');
            }            
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch(Exception $e){            
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }        
        
        $role = $this->getRoleById($newId);
        
        $this->resetClassCache();
        
        return $role;
    }
    
    /**
     * updates a single role
     * 
     * @param  Tinebase_Model_Role $_role
     * @return Tinebase_Model_Role
     */
    public function updateRole(Tinebase_Model_Role $_role)
    {
        $data = $_role->toArray();
        $data['last_modified_by'] = Tinebase_Core::getUser()->getId();
        $data['last_modified_time'] = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $_role->getId());
        $this->_rolesTable->update($data, $where);
        
        $this->resetClassCache();
        
        $role = $this->getRoleById($_role->getId());
        
        return $role;
    }
    
    /**
     * Deletes roles identified by their identifiers
     * 
     * @param   string|array $ids to delete
     * @return  void
     * @throws  Tinebase_Exception_Backend
     */
    public function deleteRoles($ids)
    {
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            // delete role acls/members first
            $this->_roleMembersTable->delete($this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' in (?)', (array) $ids));
            $this->_roleRightsTable->delete($this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' in (?)',  (array) $ids));
            // delete role
            $this->_rolesTable->delete($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' in (?)', (array) $ids));
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->resetClassCache();
            
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' error while deleting role ' . $e->__toString());
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
    }
    
    /**
     * Delete all Roles returned by {@see getRoles()} using {@see deleteRoles()}
     * @return void
     */
    public function deleteAllRoles()
    {
        $roleIds = array();
        $roles = $this->_rolesTable->fetchAll();
        foreach ($roles as $role) {
          $roleIds[] = $role->id;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($roles) .' roles');
        
        if (count($roles) > 0) {
            $this->deleteRoles($roleIds);
        }
    }
    
    /**
     * get list of role members 
     *
     * @param   int $_roleId
     * @return  array of array with account ids & types
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getRoleMembers($_roleId)
    {
        $roleId = (int)$_roleId;
        if ($roleId != $_roleId && $roleId <= 0) {
            throw new Tinebase_Exception_AccessDenied('$_roleId must be integer and greater than 0');
        }
        
        $members = array();
        
        $select = $this->_roleMembersTable->select();
        $select->where($this->_db->quoteIdentifier('role_id') . ' = ?', $_roleId);
        
        $rows = $this->_roleMembersTable->fetchAll($select)->toArray();
        
        return $rows;
    }

    /**
     * get list of role memberships
     *
     * @param   int $accountId
     * @param   string $type
     * @return  array of array with role ids
     * @throws  Tinebase_Exception_NotFound
     */
    public function getRoleMemberships($accountId, $type = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
            $accountId        = Tinebase_Model_User::convertUserIdToInt($accountId);
            $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
            if (empty($groupMemberships)) {
                throw new Tinebase_Exception_NotFound('Any account must belong to at least one group. The account with accountId ' . $accountId . ' does not belong to any group.');
            }
            
            $classCacheId = convertCacheId ($accountId . implode('', $groupMemberships) . $type);
        } else if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
            $accountId = Tinebase_Model_Group::convertGroupIdToInt($accountId);
            
            $classCacheId = convertCacheId ($accountId . $type);
        } else {
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $type);
        }
        
        if (isset($this->_classCache[__FUNCTION__][$classCacheId])) {
            return $this->_classCache[__FUNCTION__][$classCacheId];
        }
        
        $select = $this->_db->select()
            ->distinct()
            ->from(array('role_accounts' => SQL_TABLE_PREFIX . 'role_accounts'), array('role_id'))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . ' = ?', $accountId) . ' AND ' 
                . $this->_db->quoteInto($this->_db->quoteIdentifier('account_type') . ' = ?', $type));
        
        if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
            $select->orwhere($this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . ' IN (?)', $groupMemberships) . ' AND ' 
                .  $this->_db->quoteInto($this->_db->quoteIdentifier('account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP));
        }
        
        $stmt = $this->_db->query($select);
        
        $memberships = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        $this->_classCache[__FUNCTION__][$classCacheId] = $memberships;
        
        return $memberships;
    }

    /**
     * set role members 
     *
     * @param   int $_roleId
     * @param   array $_roleMembers with role members ("account_type" => account type, "account_id" => account id)
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setRoleMembers($_roleId, array $_roleMembers)
    {
        $roleId = (int)$_roleId;
        if ($roleId != $_roleId && $roleId > 0) {
            throw new Tinebase_Exception_InvalidArgument('$_roleId must be integer and greater than 0');
        }
        
        // remove old members
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' = ?', $roleId);
        $this->_roleMembersTable->delete($where);
              
        $validTypes = array( Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        foreach ($_roleMembers as $member) {
            if (!in_array($member['type'], $validTypes)) {
                throw new Tinebase_Exception_InvalidArgument('account_type must be one of ' . 
                    implode(', ', $validTypes) . ' (values given: ' . 
                    print_r($member, true) . ')');
            }
            
            $data = array(
                'role_id'       => $roleId,
                'account_type'  => $member['type'],
                'account_id'    => $member['id'],
            );
            $this->_roleMembersTable->insert($data);
        }
        
        $this->resetClassCache();
    }
    
    /**
     * set all roles an user is member of
     *
     * @param  array  $_account as role member ("account_type" => account type, "account_id" => account id)
     * @param  mixed  $_roleIds
     * @return array
     */
    public function setRoleMemberships($_account, $_roleIds)
    {
        if ($_roleIds instanceof Tinebase_Record_RecordSet) {
            $_roleIds = $_roleIds->getArrayOfIds();
        }
        
        if(count($_roleIds) === 0) {
            throw new Tinebase_Exception_InvalidArgument('user must belong to at least one role');
        }
        
        $validTypes = array( Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);

        if (! in_array($_account['type'], $validTypes)) {
            throw new Tinebase_Exception_InvalidArgument('account_type must be one of ' . 
                implode(', ', $validTypes) . ' (values given: ' . 
                print_r($_account, true) . ')');
        }
        
        $roleMemberships = $this->getRoleMemberships($_account['id']);
        
        $removeRoleMemberships = array_diff($roleMemberships, $_roleIds);
        $addRoleMemberships    = array_diff($_roleIds, $roleMemberships);
        
        foreach ($addRoleMemberships as $roleId) {
            $this->addRoleMember($roleId, $_account);
        }
        
        foreach ($removeRoleMemberships as $roleId) {
            $this->removeRoleMember($roleId, $_account);
        }
        
        return $this->getRoleMemberships($_account['id']);
    }
    
    /**
     * add a new member to a role
     *
     * @param  string  $_roleId
     * @param  array   $_account as role member ("account_type" => account type, "account_id" => account id)
     */
    public function addRoleMember($_roleId, $_account)
    {
        $roleId = (int)$_roleId;
        if ($roleId != $_roleId && $roleId > 0) {
            throw new Tinebase_Exception_InvalidArgument('$_roleId must be integer and greater than 0');
        }
        
        $validTypes = array(Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);

        if (! in_array($_account['type'], $validTypes)) {
            throw new Tinebase_Exception_InvalidArgument('account_type must be one of ' . 
                implode(', ', $validTypes) . ' (values given: ' . 
                print_r($_account, true) . ')');
        }
        
        $data = array(
            'role_id'       => $roleId,
            'account_type'  => $_account['type'],
            'account_id'    => $_account['id'],
        );
        
        try {
            $this->_roleMembersTable->insert($data);
        } catch (Zend_Db_Statement_Exception $e) {
            // account is already member of this group
        }
        
        $this->resetClassCache();
    }
    
    /**
     * remove one member from the role
     *
     * @param  mixed  $_groupId
     * @param  array   $_account as role member ("type" => account type, "id" => account id)
     */
    public function removeRoleMember($_roleId, $_account)
    {
        $roleId = (int)$_roleId;
        if ($roleId != $_roleId && $roleId > 0) {
            throw new Tinebase_Exception_InvalidArgument('$_roleId must be integer and greater than 0');
        }
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . '= ?', $roleId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('account_type') . '= ?', $_account['type']),
            $this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . '= ?', (string) $_account['id']),
        );
         
        $this->_roleMembersTable->delete($where);
        
        $this->resetClassCache();
    }
    
    /**
     * reset class cache
     * 
     * @param string $key
     * @return Tinebase_Acl_Roles
     */
    public function resetClassCache($key = null)
    {
        foreach ($this->_classCache as $cacheKey => $cacheValue) {
            if ($key === null || $key === $cacheKey) {
                $this->_classCache[$cacheKey] = array();
            }
        }
        
        return $this;
    }
    
    /**
     * get list of role rights
     *
     * @param   int $_roleId
     * @return  array of array with application ids & rights
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function getRoleRights($_roleId)
    {
        $roleId = (int)$_roleId;
        if ($roleId != $_roleId || $roleId <= 0) {
            throw new Tinebase_Exception_InvalidArgument('$_roleId must be integer and greater than 0');
        }
        
        $select = $this->_db->select()
            ->distinct()
            ->from(array('role_rights' => SQL_TABLE_PREFIX . 'role_rights'), array('application_id', 'right'))
            ->where($this->_db->quoteIdentifier('role_id') . ' = ?', $_roleId);
        
        $stmt = $this->_db->query($select);
        
        $rights = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return $rights;
    }

    /**
     * set role rights 
     *
     * @param   int    $roleId
     * @param   array  $_roleRights  with role rights array(("application_id" => app id, "right" => the right to set), (...))
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setRoleRights($roleId, array $roleRights)
    {
        if (!is_numeric($roleId) || ($roleId = (int)$roleId) === 0) {
            throw new Tinebase_Exception_InvalidArgument('$_roleId must be integer and greater than 0');
        }
        
        $currentRights = $this->getRoleRights($roleId);
        // change array key to string identifying right
        foreach ($currentRights as $id => $right) {
            $currentRights[$right['application_id'] . $right['right']] = $right;
            unset($currentRights[$id]);
        }
        
        // change array key to string identifying right
        foreach ($roleRights as $id => $right) {
            $roleRights[$right['application_id'] . $right['right']] = $right;
            unset($roleRights[$id]);
        }
        
        // compare array keys to calculate changes
        $rightsToBeDeleted = array_diff_key($currentRights, $roleRights);
        $rightsToBeAdded   = array_diff_key($roleRights, $currentRights);
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        
        foreach ($rightsToBeDeleted as $right) {
            $this->deleteRoleRight($roleId, $right['application_id'], $right['right']);
        }
        
        foreach ($rightsToBeAdded as $right) {
            $this->addRoleRight($roleId, $right['application_id'], $right['right']);
        }
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        $this->_invalidateRightsCache($roleId, array_merge($rightsToBeDeleted, $rightsToBeAdded));
    }
    
    /**
     * add one role right
     * 
     * @param unknown $roleId
     * @param unknown $applicationId
     * @param unknown $right
     */
    public function addRoleRight($roleId, $applicationId, $right)
    {
        $this->_roleRightsTable->insert(array(
            'role_id'           => $roleId,
            'application_id'    => $applicationId,
            'right'             => $right,
        ));
        
        $this->resetClassCache();
    }
    
    /**
     * remove one role right
     * 
     * @param unknown $roleId
     * @param unknown $applicationId
     * @param unknown $right
     */
    public function deleteRoleRight($roleId, $applicationId, $right)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' = ?',        $roleId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . ' = ?', $applicationId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('right') . ' = ?',          $right)
        );
        
        $this->_roleRightsTable->delete($where);
        
        $this->resetClassCache();
    }
    
    /**
     * invalidate rights cache
     * 
     * @param int   $roleId
     * @param array $roleRights  the role rights to purge from cache
     */
    protected function _invalidateRightsCache($roleId, $roleRights)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Invalidating rights cache for role id ' . $roleId);
        
        $rightsInvalidateCache = array();
        foreach ($roleRights as $right) {
            $rightsInvalidateCache[] = strtoupper($right['right']) . Tinebase_Application::getInstance()->getApplicationById($right['application_id'])->name;
        }
        
        // @todo can be further improved, by only selecting the users which are members of this role
        $userIds = Tinebase_User::getInstance()->getUsers()->getArrayOfIds();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($rightsInvalidateCache, TRUE));
        
        foreach ($rightsInvalidateCache as $rightData) {
            foreach ($userIds as $userId) {
                $cacheId = Tinebase_Helper::convertCacheId('checkRight' . $userId . $rightData);
                
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
                    Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Removing cache id ' . $cacheId);
                
                Tinebase_Core::getCache()->remove($cacheId);
            }
        }
        
        $this->resetClassCache();
    }
    
    /**
     * add single role rights 
     *
     * @param   int $_roleId
     * @param   int $_applicationId
     * @param   string $_right
     * 
     * @todo this function should be removed and setRoleRights should be used instead
     */
    public function addSingleRight($_roleId, $_applicationId, $_right)
    {
        // check if already in
        $select = $this->_roleRightsTable->select();
        $select->where($this->_db->quoteIdentifier('role_id') . ' = ?',        $_roleId)
               ->where($this->_db->quoteIdentifier('right') . ' = ?',          $_right)
               ->where($this->_db->quoteIdentifier('application_id') . ' = ?', $_applicationId);
        
        if (!$row = $this->_roleRightsTable->fetchRow($select)) {
            $this->addRoleRight($_roleId, $_applicationId, $_right);
        }
        
        $this->resetClassCache();
    }
    
    /**
     * Create initial Roles
     * 
     * @todo make hard coded role names ('user role' and 'admin role') configurable
     * 
     * @return void
     */
    public function createInitialRoles()
    {
        $groupsBackend = Tinebase_Group::getInstance();
        
        $adminGroup = $groupsBackend->getDefaultAdminGroup();
        $userGroup  = $groupsBackend->getDefaultGroup();
        
        // add roles and add the groups to the roles
        $adminRole = new Tinebase_Model_Role(array(
            'name'                  => 'admin role',
            'description'           => 'admin role for tine. this role has all rights per default.',
        ));
        $adminRole = $this->createRole($adminRole);
        $this->setRoleMembers($adminRole->getId(), array(
            array(
                'id'    => $adminGroup->getId(),
                'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 
            )
        ));
        
        $userRole = new Tinebase_Model_Role(array(
            'name'                  => 'user role',
            'description'           => 'userrole for tine. this role has only the run rights for all applications per default.',
        ));
        $userRole = $this->createRole($userRole);
        $this->setRoleMembers($userRole->getId(), array(
            array(
                'id'    => $userGroup->getId(),
                'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 
            )
        ));
        
        $this->resetClassCache();
    }
}
