<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        change exceptions to PermissionDeniedException?
 */

/**
 * Role Controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller_Role extends Admin_Controller_Abstract
{
    /**
     * fetch one role identified by $_roleId
     *
     * @param int $_roleId
     * @return Tinebase_Model_Role
     */
    public function getRole($_roleId)
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
    public function addRole(Tinebase_Model_Role $_role, array $_roleMembers, array $_roleRights)
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
    public function updateRole(Tinebase_Model_Role $_role, array $_roleMembers, array $_roleRights)
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
    public function deleteRoles($_roleIds)
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
        $members = Tinebase_Acl_Roles::getInstance()->getRoleRights($_roleId);
                
        return $members;
    }
}
