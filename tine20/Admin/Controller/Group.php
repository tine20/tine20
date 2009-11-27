<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        make it possible to change default groups
 * @todo        extend abstract record controller
 */

/**
 * Group Controller for Admin application
 *
 * @package     Admin
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
	 * @var bool
	 */
	protected $_manageSAM = false;
	
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
        $this->_currentAccount = Tinebase_Core::getUser();        
        $this->_applicationName = 'Admin';
        
        // manage samba sam?
		if(isset(Tinebase_Core::getConfig()->samba)) {
			$this->_manageSAM = Tinebase_Core::getConfig()->samba->get('manageSAM', false); 
			if ($this->_manageSAM) {
				$this->_samBackend = Tinebase_SambaSAM::getInstance();
			}
		}
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
        
        $group = Tinebase_Group::getInstance()->addGroup($_group);
        
        if (!empty($_group['members']) ) {
            Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_group['members']);
        }
        
        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->addGroup($group);
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
        
        $group = Tinebase_Group::getInstance()->updateGroup($_group);
        
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_group->members);
        
        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->updateGroup($group);
        }
        
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
        
        Tinebase_Group::getInstance()->deleteGroups($_groupIds);
        
        if ($this->_manageSAM) {
            $this->_samBackend->deleteGroups($_groupIds);
        }
        
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
    
}
