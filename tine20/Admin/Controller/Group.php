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
 * @todo        make it possible to change default groups
 * @todo        extend abstract record controller
 */

/**
 * Group Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Group extends Tinebase_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_Group
     */
    private static $_instance = NULL;
        
    /**
     * @var Tinebase_SambaSAM_Ldap
     */
    protected $_samBackend = NULL;

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
     * the singleton pattern
     *
     * @return Admin_Controller_Group
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Group;
        }
        
        return self::$_instance;
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
    public function search($filter = NULL, $sort = 'name', $dir = 'ASC', $start = NULL, $limit = NULL)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        return Tinebase_Group::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);
    }
   
    /**
     * count groups
     *
     * @param string $_filter string to search groups for
     * @return int total group count
     * 
     * @todo add checkRight again / but first fix Tinebase_Frontend_Json::searchGroups
     */
    public function searchCount($_filter)
    {
        //$this->checkRight('VIEW_ACCOUNTS');
        
        $groups = Tinebase_Group::getInstance()->getGroups($_filter);
        $result = count($groups);
        
        return $result;
    }
    
    /**
     * set all groups an user is member of
     *
     * @param  mixed  $_userId   the account as integer or Tinebase_Model_User
     * @param  mixed  $_groupIds
     * @return array
     */
    public function setGroupMemberships($_userId, $_groupIds)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        if ($_groupIds instanceof Tinebase_Record_RecordSet) {
            $_groupIds = $_groupIds->getArrayOfIds();
        }
        
        if (count($_groupIds) === 0) {
            throw new Tinebase_Exception_InvalidArgument('user must belong to at least one group');
        }
        
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
        
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($userId);
        
        $removeGroupMemberships = array_diff($groupMemberships, $_groupIds);
        $addGroupMemberships    = array_diff($_groupIds, $groupMemberships);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' current groupmemberships: ' . print_r($groupMemberships, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' new groupmemberships: ' . print_r($_groupIds, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' added groupmemberships: ' . print_r($addGroupMemberships, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' removed groupmemberships: ' . print_r($removeGroupMemberships, true));
        
        foreach ($addGroupMemberships as $groupId) {
            $this->addGroupMember($groupId, $userId);
        }
        
        foreach ($removeGroupMemberships as $groupId) {
            try {
                $this->removeGroupMember($groupId, $userId);
            } catch (Tinebase_Exception_Record_NotDefined $tern) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                    . ' Could not remove group member from group ' . $groupId . ': ' . $tern);
            }
        }
        
        return Tinebase_Group::getInstance()->getGroupMemberships($userId);
    }
    
    /**
     * fetch one group identified by groupid
     *
     * @param int $_groupId
     * @return Tinebase_Model_Group
     */
    public function get($_groupId)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $group = Tinebase_Group::getInstance()->getGroupById($_groupId);

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
    public function create(Tinebase_Model_Group $_group)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        // avoid forging group id, get's created in backend
        unset($_group->id);
        
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            $list = $this->createOrUpdateList($_group);
            $_group->list_id = $list->getId();
        }
        
        
        try {
            $group = Tinebase_Group::getInstance()->addGroup($_group);
        } catch (Exception $e) {
            // remove list again, if group creation fails
            if (isset($list)) {
                $listsBackend = new Addressbook_Backend_List();
                $listsBackend->delete($list);
            }
            throw $e;
        }
        
        if (!empty($_group['members']) ) {
            Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_group['members']);
        }
        
        $event = new Admin_Event_CreateGroup();
        $event->group = $group;
        Tinebase_Event::fireEvent($event);
        
        return $group;
    }  

   /**
     * update existing group
     *
     * @param Tinebase_Model_Group $_group
     * @return Tinebase_Model_Group
     */
    public function update(Tinebase_Model_Group $_group)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        // update default user group if name has changed
        $oldGroup = Tinebase_Group::getInstance()->getGroupById($_group->getId());
        
        $defaultGroupName = Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY);
        if ($oldGroup->name == $defaultGroupName && $oldGroup->name != $_group->name) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Updated default group name: ' . $oldGroup->name . ' -> ' . $_group->name
            );
            Tinebase_User::setBackendConfiguration($_group->name, Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY);
            Tinebase_User::saveBackendConfiguration();
        }
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            $_group->list_id = $oldGroup->list_id;
            $list = $this->createOrUpdateList($_group);
            $_group->list_id = $list->getId();
        }
        
        $group = Tinebase_Group::getInstance()->updateGroup($_group);
        
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_group->members);
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        $event = new Admin_Event_UpdateGroup();
        $event->group = $group;
        Tinebase_Event::fireEvent($event);
        
        return $group;
    }
    
    /**
     * add a new groupmember to a group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return void
     */
    public function addGroupMember($_groupId, $_userId)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        Tinebase_Group::getInstance()->addGroupMember($_groupId, $_userId);
        
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            $group = $this->get($_groupId);
            $user  = Tinebase_User::getInstance()->getUserById($_userId);
            
            if (! empty($user->contact_id) && ! empty($group->list_id)) {
                if (! Addressbook_Controller_List::getInstance()->exists($group->list_id)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                        . ' Could not add member to list ' . $group->list_id . ' (it does not exist)');
                } else {
                    $aclChecking = Addressbook_Controller_List::getInstance()->doContainerACLChecks(FALSE);
                    Addressbook_Controller_List::getInstance()->addListMember($group->list_id, $user->contact_id);
                    Addressbook_Controller_List::getInstance()->doContainerACLChecks($aclChecking);
                }
            }
        }
        
        $event = new Admin_Event_AddGroupMember();
        $event->groupId = $_groupId;
        $event->userId  = $_userId;
        Tinebase_Event::fireEvent($event);
    }
    
    /**
     * remove one groupmember from the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return void
     */
    public function removeGroupMember($_groupId, $_userId)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        Tinebase_Group::getInstance()->removeGroupMember($_groupId, $_userId);
        
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            $group = $this->get($_groupId);
            $user  = Tinebase_User::getInstance()->getUserById($_userId);
            
            if (!empty($user->contact_id) && !empty($group->list_id)) {
                try {
                    $aclChecking = Addressbook_Controller_List::getInstance()->doContainerACLChecks(FALSE);
                    Addressbook_Controller_List::getInstance()->removeListMember($group->list_id, $user->contact_id);
                    Addressbook_Controller_List::getInstance()->doContainerACLChecks($aclChecking);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) 
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' catched exception: ' . get_class($tenf));
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) 
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) 
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getTraceAsString());
                }
            }
        }
        
        $event = new Admin_Event_RemoveGroupMember();
        $event->groupId = $_groupId;
        $event->userId  = $_userId;
        Tinebase_Event::fireEvent($event);
        
    }
    
    /**
     * delete multiple groups
     *
     * @param   array $_groupIds
     * @return  void
     */
    public function delete($_groupIds)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        // check default user group / can't delete this group
        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        
        if (in_array($defaultUserGroup->getId(), $_groupIds)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Can\'t delete default group: ' . $defaultUserGroup->name
            );
            foreach ($_groupIds as $key => $value) {
                if ($value == $defaultUserGroup->getId()) {
                    unset($_groupIds[$key]);
                }
            }
        }
        
        if (empty($_groupIds)) {
            return;
        }
        
        $eventBefore = new Admin_Event_BeforeDeleteGroup();
        $eventBefore->groupIds = $_groupIds;
        Tinebase_Event::fireEvent($eventBefore);
        
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            $listIds = array();
            
            foreach ($_groupIds as $groupId) {
                $group = $this->get($groupId);
                if (!empty($group->list_id)) {
                    $listIds[] = $group->list_id;
                }
            }
            
            if (!empty($listIds)) {
                $listBackend = new Addressbook_Backend_List();
                $listBackend->delete($listIds);
            }
        }
        
        Tinebase_Group::getInstance()->deleteGroups($_groupIds);
        
        $event = new Admin_Event_DeleteGroup();
        $event->groupIds = $_groupIds;
        Tinebase_Event::fireEvent($event);
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
     * return all groups an account is member of
     *
     * @param mixed $_accountId the account as integer or Tinebase_Model_User
     * @return array
     */
    public function getGroupMemberships($_accountId)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $result = Tinebase_Group::getInstance()->getGroupMemberships($_accountId);
        
        return $result;
    }
    
    /**
     * create or update list in addressbook sql backend
     * 
     * @param  Tinebase_Model_Group  $group
     * @return Addressbook_Model_List
     */
    public function createOrUpdateList(Tinebase_Model_Group $group)
    {
        return Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($group);
    }
}
