<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param string $filter
     * @param string $sort
     * @param string $dir
     * @param int $start
     * @param int $limit
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
     * @param  mixed $_userId the account as integer or Tinebase_Model_User
     * @param  mixed $_groupIds
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
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
     * @return Tinebase_Model_Group
     * @throws Exception
     */
    public function create(Tinebase_Model_Group $_group)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        // avoid forging group id, get's created in backend
        unset($_group->id);

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                $this->createOrUpdateList($_group);
            }

            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_group, 'create');

            $group = Tinebase_Group::getInstance()->addGroup($_group);

            if (!empty($_group['members'])) {
                Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_group['members']);
            }

            $event = new Admin_Event_CreateGroup();
            $event->group = $group;
            Tinebase_Event::fireEvent($event);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
        
        return $group;
    }  

   /**
     * update existing group
     *
     * @param Tinebase_Model_Group $_group
     * @param boolean $_updateList
     * @return Tinebase_Model_Group
     */
    public function update(Tinebase_Model_Group $_group, $_updateList = true)
    {
        $this->checkRight('MANAGE_ACCOUNTS');

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
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

            if (true === $_updateList && Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                $_group->list_id = $oldGroup->list_id;
                $this->createOrUpdateList($_group);
            }

            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_group, 'update', $oldGroup);

            $group = Tinebase_Group::getInstance()->updateGroup($_group);

            Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_group->members);

            $event = new Admin_Event_UpdateGroup();
            $event->group = $group;
            Tinebase_Event::fireEvent($event);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return $group;
    }
    
    /**
     * add a new group member to a group
     *
     * @param int $_groupId
     * @param int $_userId
     * @param  boolean $_addToList
     * @return void
     */
    public function addGroupMember($_groupId, $_userId, $_addToList = true)
    {
        $this->checkRight('MANAGE_ACCOUNTS');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Adding user ' . $_userId . ' to group ' . $_groupId);

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            Tinebase_Group::getInstance()->addGroupMember($_groupId, $_userId);

            if (true === $_addToList && Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                $group = $this->get($_groupId);
                $user  = Tinebase_User::getInstance()->getUserById($_userId);

                if (! empty($user->contact_id) && ! empty($group->list_id)) {
                    if (! Addressbook_Controller_List::getInstance()->exists($group->list_id)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' Could not add member to list ' . $group->list_id . ' (it does not exist)');
                    } else {
                        $aclChecking = Addressbook_Controller_List::getInstance()->doContainerACLChecks(FALSE);
                        Addressbook_Controller_List::getInstance()->addListMember($group->list_id, $user->contact_id, false);
                        Addressbook_Controller_List::getInstance()->doContainerACLChecks($aclChecking);
                    }
                }
            }

            $event = new Admin_Event_AddGroupMember();
            $event->groupId = $_groupId;
            $event->userId  = $_userId;
            Tinebase_Event::fireEvent($event);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }
    
    /**
     * remove one groupmember from the group
     *
     * @param int $_groupId
     * @param int $_userId
     * @param  boolean $_removeFromList
     * @return void
     */
    public function removeGroupMember($_groupId, $_userId, $_removeFromList = true)
    {
        $this->checkRight('MANAGE_ACCOUNTS');

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            Tinebase_Group::getInstance()->removeGroupMember($_groupId, $_userId);

            if (true === $_removeFromList && Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                $group = $this->get($_groupId);
                $user  = Tinebase_User::getInstance()->getUserById($_userId);

                if (!empty($user->contact_id) && !empty($group->list_id)) {
                    try {
                        $aclChecking = Addressbook_Controller_List::getInstance()->doContainerACLChecks(FALSE);
                        Addressbook_Controller_List::getInstance()->removeListMember($group->list_id, $user->contact_id, false);
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

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
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

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {

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
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                $transactionId = null;
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

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array with Tinebase_Model_User ids
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

    /**
     * repair script for synchronizing groups
     * - iterate lists
     *  get last modlog with members
     *  compare with current members
     *
     * -> restore list members! (respect system only)
     * -> check group members (vergleich mit den listen)
     * 
     * @param boolean $dryRun
     * @return integer number of repaired groups/lists
     */
    public function synchronizeGroupAndListMembers($dryRun = false)
    {
        Admin_Controller_Group::getInstance()->checkRight('MANAGE_ACCOUNTS');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . " Synchronizing group and list memberships");

        // deactivate acl
        Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);
        Addressbook_Controller_List::getInstance()->doContainerACLChecks(false);

        $groupUpdateCount = 0;
        $groups = Admin_Controller_Group::getInstance()->search();
        foreach ($groups as $group) {
            // get matching list
            try {
                $list = Addressbook_Controller_List::getInstance()->get($group->list_id);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // skipping list
                continue;
            }
            if (! $list->getId()) {
                // skipping empty list
                continue;
            }
            $groupOrListUpdate = false;
            $modlogs = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications(
                'Addressbook',
                $list->getId(),
                Addressbook_Model_List::class,
                'Sql',
                NULL,
                NULL,
                NULL,
                NULL,
                'updated');
            $modlogs->sort('modification_time', 'DESC');

            $lastdiffMembers = [];
            foreach ($modlogs as $modlog) {
                // check if members have been changed
                $diff = Tinebase_Helper::jsonDecode($modlog->new_value);
                if (isset($diff['diff']['members'])) {
                    $lastdiffMembers = $diff['diff']['members'];
                    break;
                }
            }

            // contacts might have already been removed - check this
            $lastdiffMembers = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Addressbook_Model_Contact::class, [
                    ['field' => 'id', 'operator' => 'in', 'value' => $lastdiffMembers],
                ]
            ), null, false, true);
            // remove empty members
            $lastdiffMembers = array_filter($lastdiffMembers, function($value) {
                return !is_null($value) && $value !== '';
            });

            if (count($lastdiffMembers) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . " Member update found in list: " . $list->name . " ... ");
                // check with current members
                $missingMembers = array_diff($lastdiffMembers, $list->members);
                if (count($missingMembers) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . " Found missing members - restoring list members ...");
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . " missing members: " . print_r($missingMembers, true));
                    if (! $dryRun) {
                        Addressbook_Controller_List::getInstance()->addListMember($list, $missingMembers, false);
                    }
                    $groupOrListUpdate = true;
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . " members matching, nothing to do");
                }
            }

            // now we check the system users in list + group
            $systemcontacts = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Addressbook_Model_Contact::class, [
                    ['field' => 'id', 'operator' => 'in', 'value' => $list->members],
                    ['field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CONTACTTYPE_USER],
                ]
            ));
            $listMembers = $systemcontacts->account_id;
            // remove empty members
            $listMembers = array_filter($listMembers, function($value) {
                return !is_null($value) && $value !== '';
            });
            $groupMembers = Admin_Controller_Group::getInstance()->getGroupMembers($group->getId());
            // remove hidden and disabled users
            foreach ($groupMembers as $index => $accountId) {
                $user = Tinebase_User::getInstance()->getFullUserById($accountId);
                if ($user->visibility === Tinebase_Model_User::VISIBILITY_HIDDEN || $user->accountStatus === Tinebase_Model_User::ACCOUNT_STATUS_DISABLED) {
                    unset($groupMembers[$index]);
                }
            }
            sort($listMembers);
            sort($groupMembers);

            if ($listMembers != $groupMembers) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . " Group/List members mismatch found in group " . $list->name);
                $addToGroup = array_diff($listMembers, $groupMembers);
                if (count($addToGroup) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . " Adding missing list members to group");
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . " add to group: " . print_r($addToGroup, true));
                    if (!$dryRun) {
                        foreach ($addToGroup as $userId) {
                            Admin_Controller_Group::getInstance()->addGroupMember($group->getId(), $userId, false);
                        }
                    }
                    $groupOrListUpdate = true;
                }

                $addToList = array_diff($groupMembers, $listMembers);
                if (count($addToList) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . " Adding missing group members to list");
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . " add to list: " . print_r($addToList, true));
                    if (!$dryRun) {
                        $missingMembers = [];
                        foreach ($addToList as $userId) {
                            try {
                                $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($userId);
                            } catch (Addressbook_Exception_NotFound $tenf) {
                                // create user contact
                                $user = Admin_Controller_User::getInstance()->get($userId);
                                Admin_Controller_User::getInstance()->update($user);
                                $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($userId);
                            }
                            $missingMembers[] = $contact->getId();
                        }
                        Addressbook_Controller_List::getInstance()->addListMember($list, $missingMembers, false);
                    }
                    $groupOrListUpdate = true;
                }
            }

            if ($groupOrListUpdate) {
                $groupUpdateCount++;
            }
        }

        return $groupUpdateCount;
    }
}
