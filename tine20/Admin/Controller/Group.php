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
    public function search($filter, $sort, $dir, $start, $limit)
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
        
        $group = Tinebase_Group::getInstance()->addGroup($_group);
        
        if (!empty($_group['members']) ) {
            Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_group['members']);
        }
        
        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->addGroup($group);
        }
        
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
        $defaultGroupConfig = Tinebase_Config::getInstance()->getConfig(Tinebase_Config::DEFAULT_USER_GROUP);
        if ($oldGroup->name == $defaultGroupConfig->value && $oldGroup->name != $_group->name) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Updated default group name: ' . $oldGroup->name . ' -> ' . $_group->name
            );
            $defaultGroupConfig->value = $_group->name;
            Tinebase_Config::getInstance()->setConfig($defaultGroupConfig);
        }
        
        $group = Tinebase_Group::getInstance()->updateGroup($_group);
        
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_group->members);
        
        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->updateGroup($group);
        }
        
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
        $defaultUserGroup = Tinebase_Group::getInstance()->getGroupByName(
            Tinebase_Config::getInstance()->getConfig(Tinebase_Config::DEFAULT_USER_GROUP)->value
        );
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
        
        if (! empty($_groupIds)) {
            Tinebase_Group::getInstance()->deleteGroups($_groupIds);
        }
        
        if ($this->_manageSAM) {
            $this->_samBackend->deleteGroups($_groupIds);
        }
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
