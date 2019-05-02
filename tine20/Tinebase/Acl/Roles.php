<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Tinebase_Acl_Roles extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * @var Tinebase_Backend_Sql
     */
    protected $_rolesBackend;
    
    protected $_classCache = array(
        'getRoleMemberships' => array(),
        'hasRight'           => array(),
    );

    protected static $injectRoleMembers;

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
     */
    protected function __construct()
    {
        $this->_applicationName = 'Tinebase';
        $this->_modelName = 'Tinebase_Model_Role';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Role',
            'tableName' => 'roles',
        ), $this->_getDb());
        //$this->_purgeRecords = TRUE;
        //$this->_resolveCustomFields = FALSE;
        $this->_updateMultipleValidateEachRecord = TRUE;
        $this->_doContainerACLChecks = FALSE;
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

    public static function unsetInstance()
    {
        self::$_instance = NULL;
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
        
        $classCacheId = Tinebase_Helper::convertCacheId(implode('', $roleMemberships));
        
        if (!isset($this->_classCache[__FUNCTION__][$classCacheId])) {
            $select = $this->_getDb()->select()
                ->distinct()
                ->from(array('role_rights' => SQL_TABLE_PREFIX . 'role_rights'), array('application_id', 'right'))
                ->where($this->_getDb()->quoteIdentifier('role_id') . ' IN (?)', $roleMemberships);
                
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
            
            $stmt = $this->_getDb()->query($select);
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
     * @return  Tinebase_Record_RecordSet list of enabled applications for this account
     * @throws  Tinebase_Exception_AccessDenied if user has no role memberships
     */
    public function getApplications($_accountId, $_anyRight = FALSE)
    {
        $roleMemberships = $this->getRoleMemberships($_accountId);
        
        if (empty($roleMemberships)) {
            return new Tinebase_Record_RecordSet('Tinebase_Model_Application');
        }
        
        $select = $this->_getDb()->select()
            ->distinct()
            ->from(array('role_rights' => SQL_TABLE_PREFIX . 'role_rights'), array())
            ->join(
                /* table  */ array('applications' => SQL_TABLE_PREFIX . 'applications'), 
                /* on     */ $this->_getDb()->quoteIdentifier('role_rights.application_id') . ' = ' . $this->_getDb()->quoteIdentifier('applications.id')
            )
            ->where($this->_getDb()->quoteIdentifier('role_id') . ' IN (?)',          $roleMemberships)
            ->where($this->_getDb()->quoteIdentifier('applications.status') . ' = ?', Tinebase_Application::ENABLED)
            ->order('order ASC');
        
        if ($_anyRight) {
            $select->where($this->_getDb()->quoteIdentifier('role_rights.right') . " IS NOT NULL");
        } else {
            $select->where($this->_getDb()->quoteIdentifier('role_rights.right') . ' = ?', Tinebase_Acl_Rights::RUN);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . $select);
        
        $stmt = $this->_getDb()->query($select);
        
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
            throw new Tinebase_Exception_AccessDenied('User has no rights. The application ' . $_application . ' is disabled.');
        }
        
        $roleMemberships = $this->getRoleMemberships($_accountId);
        
        $select = $this->_getDb()->select()
            ->distinct()
            ->from(SQL_TABLE_PREFIX . 'role_rights', array('account_rights' => SQL_TABLE_PREFIX . 'role_rights.right'))
            ->where($this->_getDb()->quoteIdentifier(SQL_TABLE_PREFIX . 'role_rights.application_id') . ' = ?', $application->getId())
            ->where($this->_getDb()->quoteIdentifier('role_id') . ' IN (?)', $roleMemberships);
        
        $stmt = $this->_getDb()->query($select);
        
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
        return $this->search($_filter, $_paging);
    }
    
    /**
     * Returns role identified by its id
     * 
     * @param   string  $_roleId
     * @return  Tinebase_Model_Role  
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_NotFound
     */
    public function getRoleById($_roleId)
    {
        /** @var Tinebase_Model_Role $role */
        $role = $this->_getRolesBackend()->get((string)$_roleId);
        return $role;
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
        /** @var Tinebase_Model_Role $role */
        $role = $this->_getRolesBackend()->getByProperty($_roleName, 'name');
        return $role;
    }
    
    /**
     * Get multiple roles
     *
     * @param string|array $_ids Ids
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids, $_ignoreACL = false, Tinebase_Record_Expander $_expander = null)
    {
        return $this->_getRolesBackend()->getMultiple($_ids, $_ignoreACL, $_expander);
    }
    
    /**
     * Creates a single role
     * 
     * @param  Tinebase_Model_Role $role
     * @return Tinebase_Model_Role
     */
    public function createRole(Tinebase_Model_Role $role)
    {
        $role = $this->create($role);
        
        $this->resetClassCache();
        
        return $role;
    }
    
    /**
     * updates a single role
     * 
     * @param  Tinebase_Model_Role $role
     * @return Tinebase_Model_Role
     */
    public function updateRole(Tinebase_Model_Role $role)
    {
        $role = $this->update($role);
        
        $this->resetClassCache();
        
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
            $this->delete($ids);
            
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
        $roleIds = $this->_getRolesBackend()
            ->getAll()
            ->getArrayOfIds();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($roleIds) .' roles');
        
        if (count($roleIds) > 0) {
            $this->deleteRoles($roleIds);
        }
    }
    
    /**
     * get list of role members 
     *
     * @param   string $_roleId
     * @return  array of array with account ids & types
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getRoleMembers($_roleId)
    {
        $select = $this->_getDb()->select()
            ->from(array('role_accounts' => SQL_TABLE_PREFIX . 'role_accounts'))
            ->where($this->_getDb()->quoteIdentifier('role_id') . ' = ?', (string)$_roleId);
        
        $stmt = $this->_getDb()->query($select);
        
        $members = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return $members;
    }

    /**
     * get list of role members account ids, resolves groups to account ids
     *
     * @param   string $_roleId
     * @return  array with account ids
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getRoleMembersAccounts($_roleId)
    {
        $accountIds = array();
        foreach ($this->getRoleMembers($_roleId) as $role) {
		    switch($role['account_type']) {
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                    $accountIds[] = $role['account_id'];
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                    $accountIds = array_merge($accountIds,
                        Tinebase_Group::getInstance()->getGroupMembers($role['account_id']));
                    break;
            }
        }
        return $accountIds;
    }

    public function injectRoleMemberships($roles, $accountId, $type = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
            $accountId        = Tinebase_Model_User::convertUserIdToInt($accountId);
            $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($accountId);

            $classCacheId = Tinebase_Helper::convertCacheId ($accountId . implode('', $groupMemberships) . $type);
        } else if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
            $accountId = Tinebase_Model_Group::convertGroupIdToInt($accountId);

            $classCacheId = Tinebase_Helper::convertCacheId ($accountId . $type);
        } else {
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $type);
        }

        if (isset($this->_classCache[__FUNCTION__][$classCacheId])) {
            unset($this->_classCache[__FUNCTION__][$classCacheId]);
        }

        static::$injectRoleMembers[$type][$accountId] = $roles;
    }

    public function unInjectRoleMemberships($accountId, $type = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
            $accountId        = Tinebase_Model_User::convertUserIdToInt($accountId);
            $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($accountId);

            $classCacheId = Tinebase_Helper::convertCacheId ($accountId . implode('', $groupMemberships) . $type);
        } else if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
            $accountId = Tinebase_Model_Group::convertGroupIdToInt($accountId);

            $classCacheId = Tinebase_Helper::convertCacheId ($accountId . $type);
        } else {
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $type);
        }

        if (isset($this->_classCache[__FUNCTION__][$classCacheId])) {
            unset($this->_classCache[__FUNCTION__][$classCacheId]);
        }

        unset(static::$injectRoleMembers[$type][$accountId]);
    }

    /**
     * get list of role memberships
     *
     * @param int|Tinebase_Model_User $accountId
     * @param string $type
     * @return array of array with role ids
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getRoleMemberships($accountId, $type = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $groupMemberships = null;

        if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
            $accountId        = Tinebase_Model_User::convertUserIdToInt($accountId);
            $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
            
            $classCacheId = Tinebase_Helper::convertCacheId ($accountId . implode('', $groupMemberships) . $type);
        } else if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
            $accountId = Tinebase_Model_Group::convertGroupIdToInt($accountId);
            
            $classCacheId = Tinebase_Helper::convertCacheId ($accountId . $type);
        } else {
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $type);
        }
        
        if (isset($this->_classCache[__FUNCTION__][$classCacheId])) {
            return $this->_classCache[__FUNCTION__][$classCacheId];
        }
        
        $select = $this->_getDb()->select()
            ->distinct()
            ->from(array('role_accounts' => SQL_TABLE_PREFIX . 'role_accounts'), array('role_id'))
            ->where($this->_getDb()->quoteInto($this->_getDb()->quoteIdentifier('account_id') . ' = ?', $accountId) . ' AND ' 
                . $this->_getDb()->quoteInto($this->_getDb()->quoteIdentifier('account_type') . ' = ?', $type));
        
        if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER && !empty($groupMemberships)) {
            $select->orWhere($this->_getDb()->quoteInto($this->_getDb()->quoteIdentifier('account_id') . ' IN (?)', $groupMemberships) . ' AND '
                .  $this->_getDb()->quoteInto($this->_getDb()->quoteIdentifier('account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP));
        }
        
        $stmt = $this->_getDb()->query($select);
        
        $memberships = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);

        if (isset(static::$injectRoleMembers[$type][$accountId])) {
            $memberships = array_unique(array_merge($memberships, static::$injectRoleMembers[$type][$accountId]));
        }
        
        $this->_classCache[__FUNCTION__][$classCacheId] = $memberships;
        
        return $memberships;
    }

    /**
     * set role members 
     *
     * @param   string $_roleId
     * @param   array $_roleMembers with role members ("account_type" => account type, "account_id" => account id)
     * @param   bool $_allowSetId
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setRoleMembers($_roleId, array $_roleMembers, $_allowSetId = false)
    {
        $_roleId = (string)$_roleId;
        /** @var Tinebase_Model_Role $oldRole */
        $oldRole = $this->get($_roleId);
        
        // remove old members
        $where = array(
            $this->_getDb()->quoteIdentifier('role_id') . ' = ?' => $_roleId
        );
        $this->_getDb()->delete(SQL_TABLE_PREFIX . 'role_accounts', $where);
        
        $validTypes = array( Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        foreach ($_roleMembers as $member) {
            if (!in_array($member['type'], $validTypes)) {
                throw new Tinebase_Exception_InvalidArgument('account_type must be one of ' . 
                    implode(', ', $validTypes) . ' (values given: ' . 
                    print_r($member, true) . ')');
            }
            
            $data = array(
                'role_id'       => $_roleId,
                'account_type'  => $member['type'],
                'account_id'    => $member['id'],
            );
            if (true === $_allowSetId && isset($member['dataId'])) {
                $data['id'] = $member['dataId'];
            } else {
                $data['id'] = Tinebase_Record_Abstract::generateUID();
            }
            $this->_getDb()->insert(SQL_TABLE_PREFIX . 'role_accounts', $data);
        }

        $this->_writeModLogForRole($oldRole);
        
        $this->resetClassCache();
    }

    /**
     * set all roles an user is member of
     *
     * @param  array $_account as role member ("account_type" => account type, "account_id" => account id)
     * @param  mixed $_roleIds
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
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
     * @param  string $_roleId
     * @param  array $_account as role member ("account_type" => account type, "account_id" => account id)
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Db_Adapter_Exception
     */
    public function addRoleMember($_roleId, $_account)
    {
        $validTypes = array(Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);

        if (! in_array($_account['type'], $validTypes)) {
            throw new Tinebase_Exception_InvalidArgument('account_type must be one of ' . 
                implode(', ', $validTypes) . ' (values given: ' . 
                print_r($_account, true) . ')');
        }

        /** @var Tinebase_Model_Role $oldRole */
        $oldRole = $this->get($_roleId);
        
        $data = array(
            'role_id'       => (string)$_roleId,
            'account_type'  => $_account['type'],
            'account_id'    => $_account['id'],
            'id'            => Tinebase_Record_Abstract::generateUID(),
        );
        
        try {
            $this->_getDb()->insert(SQL_TABLE_PREFIX . 'role_accounts', $data);

            $this->_writeModLogForRole($oldRole);
        } catch (Zend_Db_Statement_Exception $e) {
            // account is already member of this group
        }
        
        $this->resetClassCache();
    }

    protected function _writeModLogForRole(Tinebase_Model_Role $_oldRole)
    {
        $seq = intval($_oldRole->seq);
        $_oldRole->seq = $seq + 1;
        $this->_getRolesBackend()->update($_oldRole);

        $newRole = $this->get($_oldRole->getId());
        $this->_writeModLog($newRole, $_oldRole);
    }

    /**
     * remove one member from the role
     *
     * @param  mixed $_roleId
     * @param  array $_account as role member ("type" => account type, "id" => account id)
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function removeRoleMember($_roleId, $_account)
    {
        /** @var Tinebase_Model_Role $oldRole */
        $oldRole = $this->get($_roleId);
        
        $where = array(
            $this->_getDb()->quoteIdentifier('role_id') . ' = ?'      => (string) $_roleId,
            $this->_getDb()->quoteIdentifier('account_type') . ' = ?' => $_account['type'],
            $this->_getDb()->quoteIdentifier('account_id') . ' = ?'   => (string) $_account['id']
        );
        
        $this->_getDb()->delete(SQL_TABLE_PREFIX . 'role_accounts', $where);

        $this->_writeModLogForRole($oldRole);
        
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
     * @param   string $_roleId
     * @return  array of array with application ids & rights
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function getRoleRights($_roleId)
    {
        $select = $this->_getDb()->select()
            ->distinct()
            ->from(array('role_rights' => SQL_TABLE_PREFIX . 'role_rights'), array('application_id', 'right'))
            ->where($this->_getDb()->quoteIdentifier('role_id') . ' = ?', (string) $_roleId);
        
        $stmt = $this->_getDb()->query($select);
        
        $rights = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return $rights;
    }

    /**
     * set role rights 
     *
     * @param   string    $roleId
     * @param   array  $roleRights  with role rights array(("application_id" => app id, "right" => the right to set), (...))
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setRoleRights($roleId, array $roleRights)
    {
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
     * @param string $roleId
     * @param string $applicationId
     * @param string $right
     */
    public function addRoleRight($roleId, $applicationId, $right)
    {
        /** @var Tinebase_Model_Role $oldRole */
        $oldRole = $this->get($roleId);

        $data = array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'role_id'        => (string)$roleId,
            'application_id' => $applicationId,
            'right'          => $right,
        );
        
        $this->_getDb()->insert(SQL_TABLE_PREFIX . 'role_rights', $data);

        $this->_writeModLogForRole($oldRole);
        
        $this->resetClassCache();
    }
    
    /**
     * remove one role right
     * 
     * @param string $roleId
     * @param string $applicationId
     * @param string $right
     */
    public function deleteRoleRight($roleId, $applicationId, $right)
    {
        /** @var Tinebase_Model_Role $oldRole */
        $oldRole = $this->get($roleId);

        $where = array(
            $this->_getDb()->quoteIdentifier('role_id') . ' = ?'        => (string) $roleId,
            $this->_getDb()->quoteIdentifier('application_id') . ' = ?' => $applicationId,
            $this->_getDb()->quoteIdentifier('right') . ' = ?'          => $right
        );
        
        $this->_getDb()->delete(SQL_TABLE_PREFIX . 'role_rights', $where);

        $this->_writeModLogForRole($oldRole);
        
        $this->resetClassCache();
    }
    
    /**
     * invalidate rights cache
     * 
     * @param string   $roleId
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
     * @param   string $_roleId
     * @param   string $_applicationId
     * @param   string $_right
     * 
     * @todo this function should be removed and setRoleRights should be used instead
     */
    public function addSingleRight($_roleId, $_applicationId, $_right)
    {
        // check if already in
        $select = $this->_getDb()->select()
            ->from(array('role_rights' => SQL_TABLE_PREFIX . 'role_rights'), array('id'))
            ->where($this->_getDb()->quoteIdentifier('role_id')        . ' = ?', (string)$_roleId)
            ->where($this->_getDb()->quoteIdentifier('application_id') . ' = ?', $_applicationId)
            ->where($this->_getDb()->quoteIdentifier('right')          . ' = ?', $_right);
        
        $stmt = $this->_getDb()->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if (empty($rows)) {
            $this->addRoleRight($_roleId, $_applicationId, $_right);
        }
        
        $this->resetClassCache();
    }
    
    /**
     * Create initial Roles
     *
     * @return void
     */
    public function createInitialRoles()
    {
        $groupsBackend = Tinebase_Group::getInstance();
        /** @noinspection PhpUndefinedMethodInspection */
        $oldValue = $groupsBackend->modlogActive(false);
        
        $adminGroup         = $groupsBackend->getDefaultAdminGroup();
        $userGroup          = $groupsBackend->getDefaultGroup();
        $replicationGroup   = $groupsBackend->getDefaultReplicationGroup();

        $oldOmitModLog = $this->_omitModLog;
        $oldSetNotes = $this->_setNotes;
        $this->_omitModLog = true;
        $this->_setNotes = false;

        $userRoleName = Tinebase_Config::getInstance()->get(Tinebase_Config::DEFAULT_USER_ROLE_NAME);
        $adminRoleName = Tinebase_Config::getInstance()->get(Tinebase_Config::DEFAULT_ADMIN_ROLE_NAME);
        
        // add roles and add the groups to the roles
        $adminRole = new Tinebase_Model_Role(array(
            'name'                  => $adminRoleName,
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
            'name'                  => $userRoleName,
            'description'           => 'userrole for tine. this role has only the run rights for all applications per default.',
        ));
        $userRole = $this->createRole($userRole);
        $this->setRoleMembers($userRole->getId(), array(
            array(
                'id'    => $userGroup->getId(),
                'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 
            )
        ));

        $replicationRole = new Tinebase_Model_Role(array(
            'name'                  => 'replication role',
            'description'           => 'replication role for tine. this role has only the right to access replication data per default.',
        ));
        $replicationRole = $this->createRole($replicationRole);
        $this->addRoleRight($replicationRole->getId(), Tinebase_Core::getTinebaseId(), Tinebase_Acl_Rights::REPLICATION);
        $this->setRoleMembers($replicationRole->getId(), array(
            array(
                'id'    => $replicationGroup->getId(),
                'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            )
        ));

        /** @noinspection PhpUndefinedMethodInspection */
        $groupsBackend->modlogActive($oldValue);

        $this->_setNotes = $oldSetNotes;
        $this->_omitModLog = $oldOmitModLog;
        $this->resetClassCache();
    }
    
    /**
     * create db instance
     * 
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _getDb()
    {
        if (!$this->_db) {
            $this->_db = Tinebase_Core::getDb();
        }
        
        return $this->_db;
    }
    
    /**
     * create backend for roles table
     * 
     * @return Tinebase_Backend_Sql
     */
    protected function _getRolesBackend()
    {
        if (!$this->_rolesBackend) {
            $this->_rolesBackend = new Tinebase_Backend_Sql(array(
                'modelName' => 'Tinebase_Model_Role', 
                'tableName' => 'roles',
            ), $this->_getDb());
        }
        
        return $this->_rolesBackend;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @param bool         $_getRelatedData
     * @param bool $_getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE)
    {
        $result = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted);
        $modelName = $this->_modelName;
        /** @noinspection PhpUndefinedMethodInspection */
        $modelConf = $modelName::getConfiguration();
        $rs = new Tinebase_Record_RecordSet($this->_modelName, array($result));
        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs, $modelConf);
        return $rs->getFirstRecord();
    }

    /**
     * inspect update of one record (after setReleatedData)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        $modelName = $this->_modelName;
        /** @noinspection PhpUndefinedMethodInspection */
        $modelConf = $modelName::getConfiguration();
        $rs = new Tinebase_Record_RecordSet($this->_modelName, array($updatedRecord));
        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs, $modelConf);
    }

    /**
     * get dummy role record
     *
     * @param integer $_id [optional]
     * @return Tinebase_Model_Role
     */
    public function getNonExistentRole($_id = NULL)
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');

        $result = new Tinebase_Model_Role(array(
            'id'        => $_id,
            'name'      => $translate->_('unknown'),
        ), TRUE);

        return $result;
    }
}
