<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        extend Tinebase_Controller_Record_Abstract
 */

/**
 * Role Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Role extends Tinebase_Controller_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName = 'Admin';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_Role
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_Role
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Role;
        }
        
        return self::$_instance;
    }

    /**
     * search roles
     * 
     * @param Tinebase_Model_RoleFilter $_filter
     * @param Tinebase_Model_Pagination $_paging
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Role
     */
    public function search(Tinebase_Model_RoleFilter $_filter, Tinebase_Model_Pagination $_paging)
    {
        $this->checkRight('VIEW_ROLES');
       
        return Tinebase_Acl_Roles::getInstance()->searchRoles($_filter, $_paging);
    }
    
    /**
     * count roles
     *
     * @param Tinebase_Model_RoleFilter $_filter
     * @return int total roles count
     */
    public function searchCount(Tinebase_Model_RoleFilter $_filter)
    {
        $this->checkRight('VIEW_ROLES');
        
        return Tinebase_Acl_Roles::getInstance()->searchCount($_filter);
    }
    
    /**
     * fetch one role identified by $_roleId
     *
     * @param int $_roleId
     * @return Tinebase_Model_Role
     */
    public function get($_roleId)
    {
        $this->checkRight('VIEW_ROLES');
        
        $role = Tinebase_Acl_Roles::getInstance()->getRoleById($_roleId);

        return $role;
    }  
    
   /**
     * add new role
     *
     * @param   Tinebase_Model_Role $_role
     * @param   array role members
     * @param   array role rights
     * @return  Tinebase_Model_Role
     */
    public function create(Tinebase_Model_Role $_role, array $_roleMembers, array $_roleRights)
    {
        $this->checkRight('MANAGE_ROLES');
        
        $role = Tinebase_Acl_Roles::getInstance()->createRole($_role);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($role->getId(), $_roleMembers);
        Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $_roleRights);
        
        return $role;
    }  

   /**
     * update existing role
     *
     * @param  Tinebase_Model_Role $_role
     * @param   array role members
     * @param   array role rights
     * @return Tinebase_Model_Role
     */
    public function update(Tinebase_Model_Role $_role, array $_roleMembers, array $_roleRights)
    {
        $this->checkRight('MANAGE_ROLES');
        
        $role = Tinebase_Acl_Roles::getInstance()->updateRole($_role);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($role->getId(), $_roleMembers);
        Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $_roleRights);
        
        return $role;
    }  
    
    /**
     * delete multiple roles
     *
     * @param   array $_roleIds
     * @void
     */
    public function delete($_roleIds)
    {
        $this->checkRight('MANAGE_ROLES');
        
        Tinebase_Acl_Roles::getInstance()->deleteRoles($_roleIds);
    }

    /**
     * get list of role members
     *
     * @param int $_roleId
     * @return array of arrays (account id and type)
     * 
     */
    public function getRoleMembers($_roleId)
    {
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($_roleId);
                
        return $members;
    }
    
    /**
     * get list of role rights
     *
     * @param int $_roleId
     * @return array of arrays (application id and right)
     * 
     */
    public function getRoleRights($_roleId)
    {
        $rights = Tinebase_Acl_Roles::getInstance()->getRoleRights($_roleId);
                
        return $rights;
    }
}
