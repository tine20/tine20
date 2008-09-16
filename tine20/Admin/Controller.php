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
     * @var Tinebase_Model_User
     */
    protected $_currentAccount;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_currentAccount = Zend_Registry::get('currentAccount');        
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holdes the instance of the singleton
     *
     * @var Admin_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Admin_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * get list of accounts
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    public function getUsers($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $backend = Tinebase_User::getInstance();

        $result = $backend->getUsers($_filter, $_sort, $_dir, $_start, $_limit);
        
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
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_FullUser
     */
    public function getFullUsers($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $backend = Tinebase_User::getInstance();

        $result = $backend->getFullUsers($_filter, $_sort, $_dir, $_start, $_limit);
        
        return $result;
    }
    
    /**
     * get account
     *
     * @param   int $_accountId account id to get
     * @return  Tinebase_Model_User
     */
    public function getAccount($_accountId)
    {        
        $this->checkRight('VIEW_ACCOUNTS');
        
        return Tinebase_User::getInstance()->getUserById($_accountId);
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
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $result = Tinebase_User::getInstance()->setStatus($_accountId, $_status);
        
        return $result;
    }

    /**
     * set the password for a given account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return unknown
     */
    public function setAccountPassword(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        if ($_password != $_passwordRepeat) {
            throw new Exception("passwords don't match");
        }
        
        $result = Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password, $_passwordRepeat);
        
        return $result;
    }

    /**
     * save or update account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return Tinebase_Model_FullUser
     */
    public function updateUser(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $account = Tinebase_User::getInstance()->updateUser($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        // fire needed events
        $event = new Admin_Event_UpdateAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password, $_passwordRepeat);
        }
        
        return $account;
    }
    
    /**
     * save or update account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return Tinebase_Model_FullUser
     */
    public function addUser(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $account = Tinebase_User::getInstance()->addUser($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        $event = new Admin_Event_AddAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password, $_passwordRepeat);
        }
        
        return $account;
    }

    
    /**
     * delete accounts
     *
     * @param   array $_accountIds  array of account ids
     * @return  array with success flag
     */
    public function deleteUsers(array $_accountIds)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        return Tinebase_User::getInstance()->deleteUsers($_accountIds);
    }
    
    /**
     * get list of access log entries
     *
     * @param string $_filter string to search accounts for
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_RecordSet_AccessLog set of matching access log entries
     */
    public function getAccessLogEntries($_filter = NULL, $_pagination = NULL, $_from = NULL, $_to = NULL)
    {
        $this->checkRight('VIEW_ACCESS_LOG');        
        
        $tineAccessLog = Tinebase_AccessLog::getInstance();

        $result = $tineAccessLog->getEntries($_filter, $_pagination, $_from, $_to);
        
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
        $this->checkRight('MANAGE_ACCESS_LOG');        
        
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
        $this->checkRight('VIEW_APPS');        
        
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
        $this->checkRight('VIEW_APPS');        
        
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
        $this->checkRight('MANAGE_APPS');        
        
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->setApplicationState($_applicationIds, $_state);
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
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        return Tinebase_Group::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);
    }
   
    /**
     * fetch one group identified by groupid
     *
     * @param int $_groupId
     * @return Tinebase_Model_Group
     */
    public function getGroup($_groupId)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $group = Tinebase_Group::getInstance()->getGroupById($_groupId);

        /*if (!$this->_currentAccount->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access to contact denied');
        }*/
        
        return $group;            
    }  

   /**
     * add new group
     *
     * @param Tinebase_Model_Group $_group
     * @param array $_groupMembers
     * 
     * @return Tinebase_Model_Group
     */
    public function AddGroup(Tinebase_Model_Group $_group, array $_groupMembers = array ())
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $group = Tinebase_Group::getInstance()->addGroup($_group);
        
        if ( !empty($_groupMembers) ) {
            Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_groupMembers);
        }

        return $group;            
    }  

   /**
     * update existing group
     *
     * @param Tinebase_Model_Group $_group
     * @param array $_groupMembers
     * 
     * @return Tinebase_Model_Group
     */
    public function UpdateGroup(Tinebase_Model_Group $_group, array $_groupMembers = array ())
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
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
        $this->checkRight('MANAGE_ACCOUNTS');
        
        return Tinebase_Group::getInstance()->deleteGroups($_groupIds);
    }    
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array with Tinebase_Model_User arrays
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
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Tag
     */
    public function getTags($query, $sort, $dir, $start, $limit)
    {
        $filter = new Tinebase_Model_TagFilter(array(
            'name'        => '%' . $query . '%',
            'description' => '%' . $query . '%',
            'type'        => Tinebase_Model_Tag::TYPE_SHARED
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
     * @return Tinebase_Model_FullTag
     */
    public function getTag($_tagId)
    {
        $tag = Tinebase_Tags::getInstance()->getTagsById($_tagId);
        $fullTag = new Tinebase_Model_FullTag($tag[0]->toArray(), true);
        $fullTag->rights =  Tinebase_Tags::getInstance()->getRights($_tagId);
        $fullTag->contexts = Tinebase_Tags::getInstance()->getContexts($_tagId);
        
        return $fullTag;        
    }  

   /**
     * add new tag
     *
     * @param  Tinebase_Model_FullTag $_tag
     * @return Tinebase_Model_FullTag
     */
    public function AddTag(Tinebase_Model_FullTag $_tag)
    {
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        $_tag->type = Tinebase_Model_Tag::TYPE_SHARED;
        $newTag = Tinebase_Tags::getInstance()->createTag(new Tinebase_Model_Tag($_tag->toArray(), true));

        $_tag->rights->tag_id = $newTag->getId();
        Tinebase_Tags::getInstance()->setRights($_tag->rights);
        Tinebase_Tags::getInstance()->setContexts($_tag->contexts, $newTag->getId());
        
        return $this->getTag($newTag->getId());
    }  

   /**
     * update existing tag
     *
     * @param  Tinebase_Model_FullTag $_tag
     * @return Tinebase_Model_FullTag
     */
    public function UpdateTag(Tinebase_Model_FullTag $_tag)
    {
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        Tinebase_Tags::getInstance()->updateTag(new Tinebase_Model_Tag($_tag->toArray(), true));
        
        $_tag->rights->tag_id = $_tag->getId();
        Tinebase_Tags::getInstance()->purgeRights($_tag->getId());
        Tinebase_Tags::getInstance()->setRights($_tag->rights);
        
        Tinebase_Tags::getInstance()->purgeContexts($_tag->getId());
        Tinebase_Tags::getInstance()->setContexts($_tag->contexts, $_tag->getId());
        
        return $this->getTag($_tag->getId());
    }  
    
    /**
     * delete multiple tags
     *
     * @param   array $_tagIds
     * @void
     */
    public function deleteTags($_tagIds)
    {        
        $this->checkRight('MANAGE_SHARED_TAGS');
        
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
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Role
     */
    public function getRoles($query, $sort, $dir, $start, $limit)
    {
        $this->checkRight('VIEW_ROLES');
       
        $filter = new Tinebase_Model_RoleFilter(array(
            'name'        => '%' . $query . '%',
            'description' => '%' . $query . '%'
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
    public function AddRole(Tinebase_Model_Role $_role, array $_roleMembers, array $_roleRights)
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
    public function UpdateRole(Tinebase_Model_Role $_role, array $_roleMembers, array $_roleRights)
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

    /**
     * generic check admin rights function
     * rules: 
     * - ADMIN right includes all other rights
     * - MANAGE_* right includes VIEW_* right 
     * 
     * @param   string  $_right to check
     * @todo    think about moving that to Tinebase_Acl or Tinebase_Application
     */    
    protected function checkRight( $_right ) {
        
        // array with the rights that should be checked, ADMIN is in it per default
        $rightsToCheck = array ( Tinebase_Acl_Rights::ADMIN );
        
        if ( preg_match("/MANAGE_/", $_right) ) {
            $rightsToCheck[] = constant('Admin_Acl_Rights::' . $_right);
        }

        if ( preg_match("/VIEW_([A-Z_]*)/", $_right, $matches) ) {
            $rightsToCheck[] = constant('Admin_Acl_Rights::' . $_right);
            // manage right includes view right
            $rightsToCheck[] = constant('Admin_Acl_Rights::MANAGE_' . $matches[1]);
        }
        
        $hasRight = FALSE;
        
        foreach ( $rightsToCheck as $rightToCheck ) {
            if ( Tinebase_Acl_Roles::getInstance()->hasRight('Admin', $this->_currentAccount->getId(), $rightToCheck) ) {
                $hasRight = TRUE;
                break;    
            }
        }
        
        if ( !$hasRight ) {
            throw new Exception("You are not allowed to $_right !");
        }        
                
    }
    
}
