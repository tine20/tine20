<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo    change exceptions to PermissionDeniedException
 */

/**
 * controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Admin_Controller
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Admin_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Admin_Controller;
        }
        
        return self::$instance;
    }

    /**
     * get list of accounts
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_Account
     */
    public function getAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $backend = Tinebase_Account::getInstance();

        $result = $backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit);
        
        return $result;
    }

    /**
     * get list of full accounts
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_FullAccount
     */
    public function getFullAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $backend = Tinebase_Account::getInstance();

        $result = $backend->getFullAccounts($_filter, $_sort, $_dir, $_start, $_limit);
        
        return $result;
    }
    
    /**
     * get account
     *
     * @param   int $_accountId account id to get
     * @return  Tinebase_Account_Model_Account
     */
    public function getAccount($_accountId)
    {        
        return Tinebase_Account::getInstance()->getAccountById($_accountId);
    }
    

    /**
     * set account status
     *
     * @param   string $_accountId  account id
     * @param   string $_status     status to set
     * @return  array with success flag
     */
    public function setAccountStatus($_accountId, $_status)
    {
        $result = Tinebase_Account::getInstance()->setStatus($_accountId, $_status);
        
        return $result;
    }

    /**
     * set the password for a given account
     *
     * @param Tinebase_Account_Model_FullAccount $_account the account
     * @param string $_password1 the new password
     * @param string $_password2 the new password again
     * @return unknown
     */
    public function setAccountPassword(Tinebase_Account_Model_FullAccount $_account, $_password1, $_password2)
    {
        if($_password1 != $_password2) {
            throw new Exception("passwords don't match");
        }
        
        $result = Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password1, $_password2);
        
        return $result;
    }

    /**
     * save or update account
     *
     * @param Tinebase_Account_Model_FullAccount $_account the account
     * @param string $_password1 the new password
     * @param string $_password2 the new password again
     * @return Tinebase_Account_Model_FullAccount
     */
    public function updateAccount(Tinebase_Account_Model_FullAccount $_account, $_password1, $_password2)
    {
        $account = Tinebase_Account::getInstance()->updateAccount($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        // fire needed events
        $event = new Admin_Event_UpdateAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if(!empty($_password1) && !empty($_password2)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password1, $_password2);
        }
        
        return $account;
    }
    
    /**
     * save or update account
     *
     * @param Tinebase_Account_Model_FullAccount $_account the account
     * @param string $_password1 the new password
     * @param string $_password2 the new password again
     * @return Tinebase_Account_Model_FullAccount
     */
    public function addAccount(Tinebase_Account_Model_FullAccount $_account, $_password1, $_password2)
    {
        $account = Tinebase_Account::getInstance()->addAccount($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        $event = new Admin_Event_AddAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if(!empty($_password1) && !empty($_password2)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password1, $_password2);
        }
        
        return $account;
    }

    
    /**
     * delete accounts
     *
     * @param   array $_accountIds  array of account ids
     * @return  array with success flag
     */
    public function deleteAccounts(array $_accountIds)
    {
        return Tinebase_Account::getInstance()->deleteAccounts($_accountIds);
    }
    
    /**
     * get list of access log entries
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_RecordSet_AccessLog set of matching access log entries
     */
    public function getAccessLogEntries($_filter = NULL, $_sort = 'li', $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_from = NULL, $_to = NULL)
    {
        $tineAccessLog = Tinebase_AccessLog::getInstance();

        $result = $tineAccessLog->getEntries($_filter, $_sort, $_dir, $_start, $_limit, $_from, $_to);
        
        return $result;
    }

    /**
     * returns the total number of access logs
     * 
     * @param Zend_Date $_from the date from which to fetch the access log entries from
     * @param Zend_Date $_to the date to which to fetch the access log entries to
     * @param string $_filter OPTIONAL search parameter
     * 
     * @return int
     */
    public function getTotalAccessLogEntryCount($_from, $_to, $_filter)
    {
        return Tinebase_AccessLog::getInstance()->getTotalCount($_from, $_to, $_filter);
    }
    
    /**
     * delete access log entries
     *
     * @param   array $_logIds list of logIds to delete
     */
    public function deleteAccessLogEntries($_logIds)
    {
        Tinebase_AccessLog::getInstance()->deleteEntries($_logIds);
    }
    
    /**
     * get list of applications
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_RecordSet_Application
     */
    public function getApplications($filter, $sort, $dir, $start, $limit)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getApplications($filter, $sort, $dir, $start, $limit);
    }    

    /**
     * get application
     *
     * @param   int $_applicationId application id to get
     * @return  Tinebase_Model_Application
     */
    public function getApplication($_applicationId)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getApplicationById($_applicationId);
    }
    
    /**
     * returns the total number of applications installed
     * 
     * @param string $_filter
     * @return int
     */
    public function getTotalApplicationCount($_filter)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getTotalApplicationCount($_filter);
    }
    
    /**
     * set application state
     *
     * @param   array $_applicationIds  array of application ids
     * @param   string $_state           state to set
     */
    public function setApplicationState($_applicationIds, $_state)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->setApplicationState($_applicationIds, $_state);
    }
    
    /**
     * get application account rights
     *
     * @param   int $_applicationId  app id
     * @return  array with account rights for the application
     */
    public function getApplicationPermissions($_applicationId)
    {
        $permissions = Tinebase_Application::getInstance()->getApplicationPermissions($_applicationId);
        
        return $permissions;
    }
    
   /**
     * save application permissions
     *
     * @param int    $_applicationId    the application id for which the rights will be set
     * @param array  $_rights           array with rights. if empty, all rights will be removed for this application 
     * @return  int number of rights set
     */
    public function setApplicationPermissions($_applicationId, array $_rights = array ())
    {   
        if ( !Tinebase_Acl_Rights::getInstance()->hasRight('Admin', 
                Zend_Registry::get('currentAccount')->getId(), 
                Admin_Acl_Rights::MANAGE_APPS) && 
             !Tinebase_Acl_Rights::getInstance()->hasRight('Admin', 
                Zend_Registry::get('currentAccount')->getId(), 
                Tinebase_Acl_Rights::ADMIN) ) {
            throw new Exception('You are not allowed to change application permissions!');
        }        
        
        return Tinebase_Application::getInstance()->setApplicationPermissions($_applicationId, $_rights);
    }  
        
    /**
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Group_Model_Group
     */
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
   	    return Tinebase_Group::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);
    }
   
    /**
     * fetch one group identified by groupid
     *
     * @param int $_groupId
     * @return Tinebase_Group_Model_Group
     */
    public function getGroup($_groupId)
    {
        $group = Tinebase_Group::getInstance()->getGroupById($_groupId);

        /*if(!Zend_Registry::get('currentAccount')->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access to contact denied');
        }*/
        
        return $group;            
    }  

   /**
     * add new group
     *
     * @param Tinebase_Group_Model_Group $_group
     * @param array $_groupMembers
     * 
     * @return Tinebase_Group_Model_Group
     */
    public function AddGroup(Tinebase_Group_Model_Group $_group, array $_groupMembers = array ())
    {
        $group = Tinebase_Group::getInstance()->addGroup($_group);
        
        if ( !empty($_groupMembers) ) {
            Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_groupMembers);
        }

        return $group;            
    }  

   /**
     * update existing group
     *
     * @param Tinebase_Group_Model_Group $_group
     * @param array $_groupMembers
     * 
     * @return Tinebase_Group_Model_Group
     */
    public function UpdateGroup(Tinebase_Group_Model_Group $_group, array $_groupMembers = array ())
    {
        $group = Tinebase_Group::getInstance()->updateGroup($_group);
        
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_groupMembers);

        return $group;            
    }  
    
    /**
     * delete multiple groups
     *
     * @param   array $_groupIds
     * @return  array with success flag
     */
    public function deleteGroups($_groupIds)
    {        
        return Tinebase_Group::getInstance()->deleteGroups($_groupIds);
    }    
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array with Tinebase_Account_Model_Account arrays
     */
    public function getGroupMembers($_groupId)
    {
        $result = Tinebase_Group::getInstance()->getGroupMembers($_groupId);
        
        return $result;
    }
    
    /**
     * get list of tags
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Tags_Model_Tag
     */
    public function getTags($query, $sort, $dir, $start, $limit)
    {
        $filter = new Tinebase_Tags_Model_Filter(array(
            'name'        => $query,
            'type'        => Tinebase_Tags_Model_Tag::TYPE_SHARED
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        return Tinebase_Tags::getInstance()->searchTags($filter, $paging);
    }
   
    /**
     * fetch one tag identified by tagid
     *
     * @param int $_tagId
     * @return Tinebase_Tags_Model_Tag
     */
    public function getTag($_tagId)
    {
        $fullTag = Tinebase_Tags::getInstance()->getFullTag($_tagId);
        
        return $fullTag;        
    }  

   /**
     * add new tag
     *
     * @param  Tinebase_Tag_Model_Tag $_tag
     * @return Tinebase_Tags_Model_Tag
     */
    public function AddTag(Tinebase_Tags_Model_Tag $_tag)
    {
        $_tag->type = Tinebase_Tags_Model_Tag::TYPE_SHARED;
        $tag = Tinebase_Tags::getInstance()->createTag($_tag);
        

        return $tag;            
    }  

   /**
     * update existing tag
     *
     * @param  Tinebase_Tag_Model_Tag $_tag
     * @return Tinebase_Tags_Model_Tag
     */
    public function UpdateTag(Tinebase_Tags_Model_Tag $_tag)
    {
        $tag = Tinebase_Tags::getInstance()->updateTag($_tag);
        
        return $tag;            
    }  
    
    /**
     * delete multiple tags
     *
     * @param   array $_tagIds
     * @void
     */
    public function deleteTags($_tagIds)
    {        
        Tinebase_Tags::getInstance()->deleteTags($_tagIds);
    }

    /**
     * get list of roles
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Acl_Model_Role
     */
    public function getRoles($query, $sort, $dir, $start, $limit)
    {
       
        $filter = new Tinebase_Acl_Model_RoleFilter(array(
            'name'        => $query,
            'description' => $query
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        return Tinebase_Acl_Roles::getInstance()->searchRoles($filter, $paging);
        
    }
    
    /**
     * fetch one role identified by $_roleId
     *
     * @param int $_roleId
     * @return Tinebase_Acl_Model_Role
     */
    public function getRole($_roleId)
    {
        $role = Tinebase_Acl_Roles::getInstance()->getRoleById($_roleId);

        return $role;            
    }  
    
   /**
     * add new role
     *
     * @param   Tinebase_Acl_Model_Role $_role
     * @param   array role members
     * @param   array role rights
     * @return  Tinebase_Acl_Model_Role
     */
    public function AddRole(Tinebase_Acl_Model_Role $_role, array $_roleMembers, array $_roleRights)
    {
        if ( !Tinebase_Acl_Rights::getInstance()->hasRight('Admin', 
                Zend_Registry::get('currentAccount')->getId(), 
                Admin_Acl_Rights::MANAGE_ROLES) && 
             !Tinebase_Acl_Rights::getInstance()->hasRight('Admin', 
                Zend_Registry::get('currentAccount')->getId(), 
                Tinebase_Acl_Rights::ADMIN) ) {
            throw new Exception('You are not allowed to manage roles!');
        }        
        
        $role = Tinebase_Acl_Roles::getInstance()->createRole($_role);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($role->getId(),$_roleMembers);
        Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $_roleRights);
        
        return $role;            
    }  

   /**
     * update existing role
     *
     * @param  Tinebase_Acl_Model_Role $_role
     * @param   array role members
     * @param   array role rights
     * @return Tinebase_Acl_Model_Role
     */
    public function UpdateRole(Tinebase_Acl_Model_Role $_role, array $_roleMembers, array $_roleRights)
    {
        if ( !Tinebase_Acl_Rights::getInstance()->hasRight('Admin', 
                Zend_Registry::get('currentAccount')->getId(), 
                Admin_Acl_Rights::MANAGE_ROLES) && 
             !Tinebase_Acl_Rights::getInstance()->hasRight('Admin', 
                Zend_Registry::get('currentAccount')->getId(), 
                Tinebase_Acl_Rights::ADMIN) ) {
            throw new Exception('You are not allowed to manage roles!');
        }        
        
        $role = Tinebase_Acl_Roles::getInstance()->updateRole($_role);
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($role->getId(),$_roleMembers);
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
        if ( !Tinebase_Acl_Rights::getInstance()->hasRight('Admin', 
                Zend_Registry::get('currentAccount')->getId(), 
                Admin_Acl_Rights::MANAGE_ROLES) && 
             !Tinebase_Acl_Rights::getInstance()->hasRight('Admin', 
                Zend_Registry::get('currentAccount')->getId(), 
                Tinebase_Acl_Rights::ADMIN) ) {
            throw new Exception('You are not allowed to manage roles!');
        }        
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
