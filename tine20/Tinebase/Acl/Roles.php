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

        $this->_rolesTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'roles'));
        $this->_roleMembersTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'role_accounts'));
        $this->_roleRightsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'role_rights'));
        $this->_db = Zend_Registry::get('dbAdapter');
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
     * @param string $_application the name of the application
     * @param int $_accountId the numeric id of a user account
     * @param int $_right the right to check for
     * @return bool
     */
    public function hasRight($_application, $_accountId, $_right) 
    {        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        if ($application->status != 'enabled') {
            throw new Exception('user has no rights. the application is disabled.');
        }
        
        $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($_accountId);
        
        if ( empty($roleMemberships) ) {
            return false;
        }

        $select = $this->_roleRightsTable->select();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' IN (?)', $roleMemberships))
               ->where($this->_db->quoteInto($this->_db->quoteIdentifier('right') . ' = ?', $_right));
               
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());               
            
        if (!$row = $this->_roleRightsTable->fetchRow($select)) {
            $result = false;
        } else {
            $result = true;
        }
        
        return $result;
    }

    /**
     * returns list of applications the user is able to use
     *
     * this function takes group memberships into account. Applications the accounts is able to use
     * must have the 'run' right set and the application must be enabled
     * 
     * @param int $_accountId the numeric account id
     * @return array list of enabled applications for this account
     */
    public function getApplications($_accountId)
    {  
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);

        $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($_accountId);
        
        if ( empty($roleMemberships) ) {
            throw new Exception('user has no role memberships');
        }

        $rightIdentifier = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'role_rights.right');
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'role_rights', array())
            ->join(SQL_TABLE_PREFIX . 'applications', 
                $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'role_rights.application_id') . 
                ' = ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'applications.id'))            
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' IN (?)', $roleMemberships))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'role_rights.right') . ' = ?', Tinebase_Acl_Rights::RUN))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'applications.status') . ' = ?', Tinebase_Application::ENABLED))
            ->group(SQL_TABLE_PREFIX . 'role_rights.application_id');
            
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Application', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        return $result;
    }

    /**
     * returns rights for given application and accountId
     *
     * @param string $_application the name of the application
     * @param int $_accountId the numeric account id
     * @return array list of rights
     * @todo    add right group by to statement if possible or remove duplicates in result array
     */
    public function getApplicationRights($_application, $_accountId) 
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        
        if ($application->status != 'enabled') {
            throw new Exception('user has no rights. the application is disabled.');
        }
        
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);
        
        $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($_accountId);
                        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'role_rights', array('account_rights' => 'GROUP_CONCAT(' . SQL_TABLE_PREFIX . 'role_rights.right)'))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'role_rights.application_id') . ' = ?', $application->getId()))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' IN (?)', $roleMemberships))
            ->group(SQL_TABLE_PREFIX . 'role_rights.application_id');
            //->group(SQL_TABLE_PREFIX . 'role_rights.right');
            
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());            
            
        $stmt = $this->_db->query($select);

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if ($row === false) {
            return array();
        }

        $rights = explode(',', $row['account_rights']);
        
        // remove duplicates
        $result = array();
        foreach ( $rights as $right ) {
            if ( !in_array($right, $result) ) {
                $result[] = $right;
            }
        }    

        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . 'rights: ' . print_r($result, true));      
        
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
        if ($roleId != $_roleId && $roleId <= 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $roleId);
        if (!$row = $this->_rolesTable->fetchRow($where)) {
            throw new Exception("role with id $roleId not found");
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
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_roleName);

        if (!$row = $this->_rolesTable->fetchRow($where)) {
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
        $data = $_role->toArray();
        if(Zend_Registry::isRegistered('currentAccount')) {
            $data['created_by'] = Zend_Registry::get('currentAccount')->getId();
        }
        $data['creation_time'] = Zend_Date::now()->getIso();
                
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));
                        
        $newId = $this->_rolesTable->insert($data); 
        
        if ($newId === NULL) {
           $newId = $this->_db->lastSequenceId(substr(SQL_TABLE_PREFIX . 'roles', 0,26) . '_seq');
        }
        
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
        $data = $_role->toArray();
        $data['last_modified_by'] = Zend_Registry::get('currentAccount')->getId();
        $data['last_modified_time'] = Zend_Date::now()->getIso();
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));

        $where = $this->_db->quoteInto('id = ?', $_role->getId());
        $this->_rolesTable->update($data, $where); 
        
        $role = $this->getRoleById($_role->getId());
        return $role;
    }
    
    /**
     * Deletes roles identified by their identifiers
     * 
     * @param  string|array id(s) to delete
     * @return void
     */
    public function deleteRoles($_ids)
    {
        $ids = ( is_array($_ids) ) ? implode(",", $_ids) : $_ids;
        $this->_rolesTable->delete( "id in ( $ids )");
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
        if ($roleId != $_roleId && $roleId <= 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        $members = array();
        
        $select = $this->_roleMembersTable->select();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' = ?', $_roleId));
        
        $rows = $this->_roleMembersTable->fetchAll($select)->toArray();
        
        return $rows;
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
        
        $select = $this->_roleMembersTable->select();
        $select ->where($this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . ' = ?', $_accountId) . ' AND ' . $this->_db->quoteInto($this->_db->quoteIdentifier('account_type') . ' = ?', 'user'))
                ->orwhere($this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . ' IN (?)', $groupMemberships) . ' AND ' .  $this->_db->quoteInto($this->_db->quoteIdentifier('account_type') . ' = ?', 'group'));
            
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());            
        
        $rows = $this->_roleMembersTable->fetchAll($select)->toArray();
        
        foreach ($rows as $membership) {
            $memberships[] = $membership['role_id'];
        }

        return $memberships;
    }

    /**
     * set role members 
     *
     * @param   int $_roleId
     * @param   array $_roleMembers with role members ("account_type" => account type, "account_id" => account id)
     */
    public function setRoleMembers($_roleId, array $_roleMembers)
    {
        $roleId = (int)$_roleId;
        if ($roleId != $_roleId && $roleId > 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        // remove old members
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' = ?', $roleId);
        $this->_roleMembersTable->delete($where);
              
        $validTypes = array( 'user', 'group', 'anyone');
        foreach ( $_roleMembers as $member ) {
            if ( !in_array($member['account_type'], $validTypes) ) {
                throw new InvalidArgumentException('account_type must be one of ' . 
                    implode(', ', $validTypes) . ' (values given: ' . 
                    print_r($member, true) . ')');
            }
            
            $data = array(
                'role_id'       => $roleId,
                'account_type'  => $member['account_type'],
                'account_id'    => $member['account_id'],
            );
            $this->_roleMembersTable->insert($data); 
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
        if ($roleId != $_roleId && $roleId > 0) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        $rights = array();
        
        $select = $this->_roleRightsTable->select();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' = ?', $_roleId));
        
        $rows = $this->_roleRightsTable->fetchAll($select)->toArray();
        
        foreach ($rows as $right) {
            $rights[] = array ( 
                'application_id'    => $right['application_id'], 
                'right'             => $right['right']
            );
        }
        return $rights;
    }

    /**
     * set role rights 
     *
     * @param   int $_roleId
     * @param   array $_roleRights with role rights ("application_id" => app id, "right" => the right to set)
     */
    public function setRoleRights($_roleId, array $_roleRights)
    {
        $roleId = (int)$_roleId;
        if ( $roleId != $_roleId && $roleId > 0 ) {
            throw new InvalidArgumentException('$_roleId must be integer and greater than 0');
        }
        
        // remove old rights
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' = ?', $roleId);
        $this->_roleRightsTable->delete($where);
                
        foreach ( $_roleRights as $right ) {
            $data = array(
                'role_id'           => $roleId,
                'application_id'    => $right['application_id'],
                'right'             => $right['right'],
            );
            $this->_roleRightsTable->insert($data); 
        }
    }

    /**
     * add single role rights 
     *
     * @param   int $_roleId
     * @param   int $_applicationId
     * @param   string $_right
     */
    public function addSingleRight($_roleId, $_applicationId, $_right)
    {        
        // check if already in
        $select = $this->_roleRightsTable->select();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('role_id') . ' = ?', $_roleId))
               ->where($this->_db->quoteInto($this->_db->quoteIdentifier('right') . ' = ?', $_right))
               ->where($this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . ' = ?', $_applicationId));
               
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());               
            
        if (!$row = $this->_roleRightsTable->fetchRow($select)) {                        
            $data = array(
                'role_id'           => $_roleId,
                'application_id'    => $_applicationId,
                'right'             => $_right,
            );
            $this->_roleRightsTable->insert($data); 
        }
    }
    
}
