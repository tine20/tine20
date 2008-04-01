<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * primary class to handle groups
 *
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group
{
    /**
     * instance of the group backend
     *
     * @var Tinebase_Group_Interface
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Tinebase_Group_Factory::getBackend(Tinebase_Group_Factory::SQL);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Group
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Group
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Group;
        }
        
        return self::$_instance;
    }    

    /**
     * return all groups an account is member of
     *
     * @param mixed $_accountId the account as integer or Tinebase_Account_Model_Account
     * @return array
     */
    public function getGroupMemberships($_accountId)
    {
        $result = $this->_backend->getGroupMemberships($_accountId);
        
        return $result;
    }
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array
     */
    public function getGroupMembers($_groupId)
    {
        $result = $this->_backend->getGroupMembers($_groupId);
        
        return $result;
    }
    
    /**
     * replace all current groupmembers with the new groupmembers list
     *
     * @param int $_groupId
     * @param array $_groupMembers
     * @return unknown
     */
    public function setGroupMembers($_groupId, $_groupMembers)
    {
        $result = $this->_backend->setGroupMembers($_groupId, $_groupMembers);
        
        return $result;
    }

    /**
     * add a new groupmember to the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    public function addGroupMember($_groupId, $_accountId)
    {
        $result = $this->_backend->addGroupMember($_groupId, $_accountId);
        
        return $result;
    }

    /**
     * remove one groupmember from the group
     *
     * @param int $_groupId
     * @param int $_accountId
     * @return unknown
     */
    public function removeGroupMember($_groupId, $_accountId)
    {
        $result = $this->_backend->removeGroupMember($_groupId, $_accountId);
        
        return $result;
    }
    
    /**
     * create a new group
     *
     * @param string $_groupName
     * @return unknown
     */
    public function addGroup($_groupName)
    {
        $result = $this->_backend->addGroup($_groupName);
        
        return $result;
    }
    
    /**
     * updates an existing group
     *
     * @param Tinebase_Group_Model_Group $_account
     * @return Tinebase_Group_Model_Group
     */
    public function updateGroup(Tinebase_Group_Model_Group $_group)
    {
        $result = $this->_backend->updateGroup($_group);
        
        return $result;
    }

    /**
     * remove group
     *
     * @param int $_groupId
     * @return unknown
     * 
     * @deprecated
     * @todo    remove
     */
    public function deleteGroup($_groupId)
    {
        $result = $this->_backend->deleteGroup($_groupId);
        
        return $result;
    }
    
    /**
     * remove groups
     *
     * @param mixed $_groupId
     * 
     */
    public function deleteGroups($_groupId)
    {
        if(is_array($_groupId) or $_groupId instanceof Tinebase_Record_RecordSet) {
            foreach($_groupId as $groupId) {
                $this->deleteGroups($groupId);
            }
        } else {
            $this->_backend->deleteGroup($_groupId);
        }
    	
    }
    
    /**
     * get group by id
     *
     * @param int $_groupId
     * @return Tinebase_Group_Model_Group
     */
    public function getGroupById($_groupId)
    {
        $result = $this->_backend->getGroupById($_groupId);
        
        return $result;
    	
    }
    
    /**
     * get group by name
     *
     * @param string $_groupName
     * @return Tinebase_Group_Model_Group
     */
    public function getGroupByName($_groupName)
    {
        $result = $this->_backend->getGroupByName($_groupName);
        
        return $result;
    	
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
    public function getGroups($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
   		$result = $this->_backend->getGroups($_filter, $_sort, $_dir, $_start, $_limit);
        
        return $result;
    }
}