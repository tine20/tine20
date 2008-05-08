<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add more functions
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
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $_db;
    
    /**
     * the Zend_Dd_Table object
     *
     * @var Tinebase_Db_Table
     */
    protected $rolesTable;
    
    /**
     * the Zend_Dd_Table object for role members
     *
     * @var Tinebase_Db_Table
     */
    protected $roleMembersTable;

    /**
     * the Zend_Dd_Table object for role rights
     *
     * @var Tinebase_Db_Table
     */
    protected $roleRightsTable;
    
    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Acl_Roles
     */
    private static $instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() {}
    
    /**
     * the constructor
     *
     * disabled. use the singleton
     * temporarly the constructor also creates the needed tables on demand and fills them with some initial values
     */
    private function __construct() {

        $this->rolesTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'roles'));
        $this->roleMembersTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'role_accounts'));
        $this->roleRightsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'role_rights'));
        $this->_db = Zend_Registry::get('dbAdapter');
    }    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Acl_Roles
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Acl_Roles;
        }
        
        return self::$instance;
    }        

    /**
     * check if one of the roles the user is in has a given right for a given application
     *
     * @param int $_applicationId the application id
     * @param int $_accountId the numeric id of a user account
     * @param int $_right the right to check for
     * @return bool
     * 
     * @todo    implement
     */
    public function hasRight($_applicationId, $_accountId, $_right) 
    {        
        $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($_accountId);

        $select = $this->roleRightsTable->select();
        $select->where("role_id IN (?)", implode(',', $roleMemberships))
               ->where("`right` = ?", $_right);
            
        if(!$row = $this->roleRightsTable->fetchRow($select)) {
            $result = false;
        } else {
            $result = true;
        }
        
        return $result;
    }
    
    /**
     * Searches roles according to filter and paging
     * 
     * @param  Tinebase_Acl_Model_RoleFilter    $_filter
     * @param  Tinebase_Model_Pagination        $_paging
     * @return Tinebase_Record_RecordSet  Set of Tinebase_Acl_Model_Role
     */
    public function searchRoles($_filter, $_paging)
    {
        $select = $_filter->getSelect();
        
        $_paging->appendPagination($select);
        
        return new Tinebase_Record_RecordSet('Tinebase_Acl_Model_Role', $this->_db->fetchAssoc($select));
    }

    /**
     * Returns role identified by its id
     * 
     * @param  int  $_roleId
     * @return Tinebase_Acl_Model_Role  
     */
    public function getRoleById($_roleId)
    {
        $roleId = (int)$_roleId;
        if($roleId != $_roleId && $roleId > 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        $where = $this->rolesTable->getAdapter()->quoteInto('`id` = ?', $roleId);
        if(!$row = $this->rolesTable->fetchRow($where)) {
            throw new Exception("role with id $_roleId not found");
        }
        
        $result = new Tinebase_Acl_Model_Role($row->toArray());
        
        return $result;
        
    }

    /**
     * Returns role identified by its name
     * 
     * @param  string $_roleName
     * @return Tinebase_Acl_Model_Role  
     */
    public function getRoleByName($_roleName)
    {        
        $where = $this->rolesTable->getAdapter()->quoteInto('`name` = ?', $_roleName);

        if(!$row = $this->rolesTable->fetchRow($where)) {
            throw new Exception("role $_roleName not found");
        }
        
        $result = new Tinebase_Acl_Model_Role($row->toArray());
        
        return $result;
    }
    
    /**
     * Creates a single role
     * 
     * @param  Tinebase_Acl_Model_Role
     * @return Tinebase_Acl_Model_Role
     */
    public function createRole(Tinebase_Acl_Model_Role $_role)
    {
        $_role->created_by = Zend_Registry::get('currentAccount')->getId();
        $_role->creation_time = Zend_Date::now()->getIso();
        
        $data = $_role->toArray();
                
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));
                        
        $newId = $this->rolesTable->insert($data); 
        
        $role = $this->getRoleById($newId);
        return $role;
    }
    
    /**
     * updates a single role
     * 
     * @param  Tinebase_Acl_Model_Role $_role
     * @return Tinebase_Acl_Model_Role
     */
    public function updateRole(Tinebase_Acl_Model_Role $_role)
    {
        $_role->last_modified_by = Zend_Registry::get('currentAccount')->getId();
        $_role->last_modified_time = Zend_Date::now()->getIso();
        
        $data = $_role->toArray();
                
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));

        $where = $this->rolesTable->getAdapter()->quoteInto('`id` = ?', $_role->getId());
        $this->rolesTable->update($data, $where); 
        
        $role = $this->getRoleById($_role->getId());
        return $role;
    }
    
    /**
     * Deletes roles identified by their identifiers
     * @todo implement 
     * 
     * @param  string|array id(s) to delete
     * @return void
     */
    public function deleteRoles($_ids)
    {
        $ids = ( is_array($_ids) ) ? implode ( ",", $_ids) : $_ids;
        $this->rolesTable->delete( "id in ( $ids )");
    }
    
    /**
     * get list of role members 
     *
     * @param int $_roleId
     * @return array of array with account ids & types
     */
    public function getRoleMembers($_roleId)
    {
        $roleId = (int)$_roleId;
        if($roleId != $_roleId && $roleId > 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        $members = array();
        
        $select = $this->roleMembersTable->select();
        $select->where('role_id = ?', $_roleId);
        
        $rows = $this->roleMembersTable->fetchAll($select);
        
        foreach($rows as $member) {
            $members[] = array ( 
                "id" => $member->account_id, 
                "type" => $member->account_type 
            );
        }

        return $members;
    }

    /**
     * get list of role members 
     *
     * @param int $_accountId
     * @return array of array with account ids & types
     */
    public function getRoleMemberships($_accountId)
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($accountId);        
        
        $memberships = array();
        
        $select = $this->roleMembersTable->select();
        $select->where("account_id = ? and account_type='user'", $_accountId)
            ->orwhere("account_id IN (?) and account_type='group'", implode(',',$groupMemberships));
        
        $rows = $this->roleMembersTable->fetchAll($select);
        
        foreach($rows as $membership) {
            $memberships[] = $membership->role_id;
        }

        return $memberships;
    }

    /**
     * set role members 
     *
     * @param   int $_roleId
     * @param   array $_roleMembers with role members
     */
    public function setRoleMembers($_roleId, array $_roleMembers)
    {
        $roleId = (int)$_roleId;
        if($roleId != $_roleId && $roleId > 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }

        // remove old members
        $where = Zend_Registry::get('dbAdapter')->quoteInto('role_id = ?', $roleId);
        $this->roleMembersTable->delete($where);
                
        foreach ( $_roleMembers as $member ) {
            $data = array(
                "role_id"       => $roleId,
                "account_type"  => $member["type"],
                "account_id"    => $member["id"],
            );
            $this->roleMembersTable->insert($data); 
        }
    }
    
    /**
     * get list of role rights 
     *
     * @param int $_roleId
     * @return array of array with application ids & rights
     */
    public function getRoleRights($_roleId)
    {
        $roleId = (int)$_roleId;
        if($roleId != $_roleId && $roleId > 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        $rights = array();
        
        $select = $this->roleRightsTable->select();
        $select->where('role_id = ?', $_roleId);
        
        $rows = $this->roleRightsTable->fetchAll($select);
        
        foreach($rows as $right) {
            $rights[] = array ( 
                "application_id"    => $right->application_id, 
                "right"             => $right->right
            );
        }
        return $rights;
    }

    /**
     * set role rights 
     *
     * @param   int $_roleId
     * @param   array $_roleRights with role rights
     */
    public function setRoleRights($_roleId, array $_roleRights)
    {
        $roleId = (int)$_roleId;
        if($roleId != $_roleId && $roleId > 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        // remove old rights
        $where = Zend_Registry::get('dbAdapter')->quoteInto('role_id = ?', $roleId);
        $this->roleRightsTable->delete($where);
                
        foreach ( $_roleRights as $right ) {
            $data = array(
                "role_id"           => $roleId,
                "application_id"    => $right["application_id"],
                "right"             => $right["right"],
            );
            $this->roleRightsTable->insert($data); 
        }
    }
}
