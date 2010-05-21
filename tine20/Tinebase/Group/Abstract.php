<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        add search count function
 */

/**
 * abstract class for all group backends
 *
 * @package     Tinebase
 * @subpackage  Group
 */
 
abstract class Tinebase_Group_Abstract
{
    /**
     * return all groups an account is member of
     *
     * @param mixed $_accountId the account as integer or Tinebase_Model_User
     * @return array
     */
    abstract public function getGroupMemberships($_accountId);
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array
     */
    abstract public function getGroupMembers($_groupId);
    
    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param int $_groupId
     * @param array $_groupMembers
     * @return unknown
     */
    abstract public function setGroupMembers($_groupId, $_groupMembers);

    /**
     * add a new groupmember to the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    abstract public function addGroupMember($_groupId, $_accountId);

    /**
     * remove one groupmember from the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    abstract public function removeGroupMember($_groupId, $_accountId);
    
    /**
     * create a new group
     *
     * @param string $_groupName
     * @return unknown
     */
    abstract public function addGroup(Tinebase_Model_Group $_group);
    
    /**
     * updates an existing group
     *
     * @param Tinebase_Model_Group $_account
     * @return Tinebase_Model_Group
     */
    abstract public function updateGroup(Tinebase_Model_Group $_group);

    /**
     * remove groups
     *
     * @param mixed $_groupId
     * 
     */
    abstract public function deleteGroups($_groupId);
    
    /**
     * get group by id
     *
     * @param int $_groupId
     * @return Tinebase_Model_Group
     */
    abstract public function getGroupById($_groupId);
    
    /**
     * get group by name
     *
     * @param string $_groupName
     * @return Tinebase_Model_Group
     */
    abstract public function getGroupByName($_groupName);

    /**
     * get default group
     *
     * @return Tinebase_Model_Group
     */
    public function getDefaultGroup()
    {
        return $this->_getDefaultGroup('Users');
    }
    
    /**
     * get default admin group
     *
     * @return Tinebase_Model_Group
     */
    public function getDefaultAdminGroup()
    {
        return $this->_getDefaultGroup('Administrators');
    }
    
    /**
     * Get multiple groups
     *
     * @param string|array $_ids Ids
     * @return Tinebase_Record_RecordSet
     */
    abstract public function getMultiple($_ids);
    
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
    abstract public function getGroups($_filter = NULL, $_sort = 'name', $_dir = 'ASC', $_start = NULL, $_limit = NULL);
    
    /**
     * get default group for users/admins
     * 
     * @param string $_name group name (Users|Administrators)
     * @return unknown_type
     */
    protected function _getDefaultGroup($_name = 'Users')
    {
        if (! in_array($_name, array('Users', 'Administrators'))) {
            throw new Tinebase_Exception_InvalidArgument('Wrong group name: ' . $_name);
        }
        
        $configKey = ($_name == 'Users') ? Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY : Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY;
        $defaultGroupName = Tinebase_User::getBackendConfiguration($configKey);
        if (empty($defaultGroupName)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $configKey . ' not found. Check your user backend configuration.');
            $defaultGroupName = $_name;
        }
        return $this->getGroupByName($defaultGroupName);        
    }
    
    /**
     * set all groups an account is member of
     *
     * @param mixed $_accountId the account as integer or Tinebase_Model_User
     * @param array $_groupIds
     * @return void
     */
    public function setGroupMemberships($_auccountId, array $_groupIds)
    {
        if (empty($_auccountId)) {
            throw new Tinebase_Exception_InvalidArgument('No accountId');
        }
        
        if (count($_groupIds) <= 0) {
            throw new Tinebase_Exception_InvalidArgument('No groupIds');
        }
        
        $user = Tinebase_User::getInstance()->getUserById($_auccountId);
        $groupMemberships = $user->getGroupMemberships();
        
        $removeGroupMemberships = array_diff($groupMemberships, $_groupIds);
        $addGroupMemberships = array_diff($_groupIds, $groupMemberships);
        
        foreach ($addGroupMemberships as $groupId) {
            $this->addGroupMember($groupId, $_auccountId);
        }
        
        foreach ($removeGroupMemberships as $groupId) {
            $this->removeGroupMember($groupId, $_auccountId);
        }
    }
 }
 